<?php
/**
 * config.php
 * ----------
 * Centralny plik startowy aplikacji ("bootstrap"). Robi 4 rzeczy:
 *   1. Ustawia stałe i ścieżki.
 *   2. Włącza autoloader klas (automatyczne ładowanie plików z /src).
 *   3. Startuje sesję.
 *   4. Definiuje zestaw prostych funkcji pomocniczych używanych w całej aplikacji.
 *
 * Plik dołączany jest na samym początku index.php.
 */

declare(strict_types=1);

// --- 1. Ścieżki i stałe -------------------------------------------------
define('BASE_PATH', __DIR__);                       // katalog główny projektu
define('DB_PATH',   BASE_PATH . '/data/chat.sqlite'); // plik bazy SQLite
define('UPLOAD_DIR', BASE_PATH . '/uploads');       // katalog na wgrane pliki
define('UPLOAD_URL', 'uploads');                    // adres URL katalogu uploads
define('PER_PAGE',  5);                             // ile wierszy na stronę (paginacja)
// Adres bazowy aplikacji — używany w linkach wysyłanych e-mailem (muszą być pełne).
// Dostosuj, jeśli uruchamiasz serwer na innym porcie niż 8000.
define('BASE_URL', 'http://localhost:8000');

// --- Konfiguracja poczty (SMTP) ----------------------------------------
// Dane serwera, przez który wysyłamy maile (np. link aktywacyjny).
// Uzupełnij 'user' i 'pass' danymi ze swojego konta Mailtrap
// (panel Mailtrap → Inbox → SMTP Settings → Show Credentials).
define('MAIL', [
    'host'      => 'sandbox.smtp.mailtrap.io',
    'port'      => 2525,
    'user'      => '924bb46140da44',          // login SMTP z Mailtrap
    'pass'      => '93ae45ba8183bd',          // hasło SMTP z Mailtrap
    'from'      => 'php@chat.pl',     // adres nadawcy
    'from_name' => 'PHPchat',             // nazwa nadawcy
]);

// --- 2. Autoloader ------------------------------------------------------
// Autoloader Composera — ładuje biblioteki zewnętrzne z katalogu vendor/
// (m.in. PHPMailer używany przez klasę Mailer). Plik powstaje po wykonaniu
// `composer install`. Sprawdzamy jego istnienie, by aplikacja działała
// nawet zanim ktoś zainstaluje zależności.
$composerAutoload = BASE_PATH . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
}

// Gdy w kodzie użyjemy klasy (np. new Message()), PHP samo doczyta plik
// o tej samej nazwie z jednego z poniższych katalogów.
spl_autoload_register(function (string $class): void {
    $folders = ['src', 'src/Models', 'src/Controllers'];
    foreach ($folders as $folder) {
        $file = BASE_PATH . "/$folder/$class.php";
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

// --- 3. Sesja -----------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 4. Funkcje pomocnicze ---------------------------------------------

/** Bezpieczne wypisanie tekstu w HTML (ochrona przed XSS). */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Przekierowanie i natychmiastowe zakończenie skryptu (wzorzec PRG). */
function redirect(string $url): void
{
    header('Location: ' . $url, true, 303);
    exit;
}

/** Zapisanie jednorazowego komunikatu (flash), pokazywanego po przekierowaniu. */
function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

/** Pobranie i jednoczesne skasowanie komunikatu flash. */
function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Renderowanie widoku wewnątrz wspólnego szablonu (layout.php).
 * $template np. 'messages/index', $data to dane przekazane do widoku.
 */
function view(string $template, array $data = []): void
{
    extract($data);                                  // zmienia klucze tablicy w zmienne
    ob_start();                                      // przechwyć HTML widoku do bufora
    require BASE_PATH . "/views/$template.php";
    $content = ob_get_clean();                       // gotowy HTML treści strony
    require BASE_PATH . "/views/layout.php";         // wstaw treść w ramkę layoutu
}

/** Wyświetlenie strony błędu i zakończenie (np. 404, 403, 500). */
function show_error(int $code, string $message): void
{
    http_response_code($code);
    view('errors/error', ['code' => $code, 'message' => $message]);
    exit;
}

// --- Ochrona CSRF (proste, jednoplikowe zabezpieczenie formularzy) ------

/** Zwraca ukryte pole <input> z tokenem CSRF do wstawienia w formularzu. */
function csrf_field(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}

/** Sprawdza token CSRF z formularza; przy niezgodności przerywa działanie. */
function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        show_error(403, 'Nieprawidłowy token formularza. Odśwież stronę i spróbuj ponownie.');
    }
}
