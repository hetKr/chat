<?php
/**
 * index.php — FRONT CONTROLLER (jeden punkt wejścia do aplikacji)
 * ---------------------------------------------------------------
 * Każde żądanie trafia tutaj. Na podstawie parametru ?page=... wybieramy
 * odpowiedni kontroler i metodę. To jest prosty router aplikacji.
 *
 * Przepływ:  przeglądarka -> index.php -> Kontroler -> Model -> Widok
 */

require __DIR__ . '/config.php';

// Jeśli baza jeszcze nie istnieje, poproś o uruchomienie setup.php.
if (!is_file(DB_PATH)) {
    exit('Baza danych nie istnieje. Uruchom najpierw w terminalu: php setup.php');
}

/**
 * MAPA TRAS (routing).
 * Klucz = wartość ?page=...   Wartość = [Kontroler, metoda, wymagany dostęp].
 * access: 'guest' = bez logowania, 'auth' = zalogowany, 'admin' = administrator.
 */
$routes = [
    // --- Uwierzytelnianie (dostępne bez logowania) ---
    'login'        => [AuthController::class,    'login',     'guest'],
    'do_login'     => [AuthController::class,    'doLogin',   'guest'],
    'register'     => [AuthController::class,    'register',  'guest'],
    'do_register'  => [AuthController::class,    'doRegister','guest'],
    'verify'       => [AuthController::class,    'verify',    'guest'],
    'social'       => [AuthController::class,    'social',    'guest'],
    'logout'       => [AuthController::class,    'logout',    'auth'],

    // --- Wiadomości (główny zasób, pełny CRUD) ---
    'messages'        => [MessageController::class, 'index',  'auth'],
    'message_create'  => [MessageController::class, 'create', 'auth'],
    'message_edit'    => [MessageController::class, 'edit',   'auth'],
    'message_save'    => [MessageController::class, 'save',   'auth'],
    'message_delete'  => [MessageController::class, 'delete', 'auth'],
    'export'          => [MessageController::class, 'export', 'auth'],

    // --- Rozmowy ---
    'conversation_create' => [ConversationController::class, 'create', 'auth'],
    'conversation_save'   => [ConversationController::class, 'save',   'auth'],

    // --- Kontakty ---
    'contacts'        => [ContactController::class, 'index',  'auth'],
    'contact_add'     => [ContactController::class, 'add',    'auth'],
    'contact_delete'  => [ContactController::class, 'delete', 'auth'],

    // --- Preferencje ---
    'toggle_theme'    => [SettingsController::class, 'toggleTheme', 'auth'],

    // --- Panel administratora ---
    'admin'           => [AdminController::class, 'dashboard',  'admin'],
    'admin_users'     => [AdminController::class, 'users',      'admin'],
    'admin_set_role'  => [AdminController::class, 'setRole',    'admin'],
    'admin_delete'    => [AdminController::class, 'deleteUser', 'admin'],
];

// Domyślna strona: lista wiadomości dla zalogowanych, logowanie dla gości.
$page = $_GET['page'] ?? (Auth::check() ? 'messages' : 'login');

if (!isset($routes[$page])) {
    show_error(404, 'Nie znaleziono strony.');
}

[$controllerClass, $method, $access] = $routes[$page];

// Kontrola dostępu w jednym miejscu.
if ($access === 'auth') {
    Auth::requireLogin();
} elseif ($access === 'admin') {
    Auth::requireAdmin();
}

// Wywołanie wybranego kontrolera. try/catch zamienia nieoczekiwane
// błędy na czytelną stronę 500 zamiast surowego komunikatu PHP.
try {
    $controller = new $controllerClass();
    $controller->$method();
} catch (Throwable $ex) {
    show_error(500, 'Wystąpił wewnętrzny błąd serwera.');
}
