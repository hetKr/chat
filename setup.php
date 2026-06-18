<?php
/**
 * setup.php — INSTALATOR BAZY DANYCH (uruchamiany raz, z terminala)
 * -----------------------------------------------------------------
 * Tworzy plik bazy SQLite, wykonuje schemat z database/schema.sql
 * i wypełnia bazę przykładowymi danymi (konta, rozmowy, wiadomości).
 *
 * Uruchomienie:  php setup.php
 */

require __DIR__ . '/config.php';

// Upewnij się, że istnieją katalogi na bazę i pliki.
@mkdir(BASE_PATH . '/data', 0777, true);
@mkdir(UPLOAD_DIR, 0777, true);

// Zabezpieczenie przed utratą danych: jeśli baza już istnieje, NIE kasujemy jej
// automatycznie. Aby utworzyć ją od nowa (usuwając wszystkie konta i wiadomości),
// uruchom świadomie:  php setup.php --reset
$force = in_array('--reset', $argv ?? [], true);
if (is_file(DB_PATH) && !$force) {
    exit("Baza danych już istnieje (" . DB_PATH . ") — pozostawiam ją bez zmian.\n"
        . "Jeśli na pewno chcesz wyczyścić wszystkie dane i utworzyć ją od nowa, uruchom:\n"
        . "    php setup.php --reset\n");
}
if (is_file(DB_PATH)) {
    unlink(DB_PATH);
}

$pdo = Database::pdo();

// 1. Utwórz tabele ze schematu.
$schema = file_get_contents(BASE_PATH . '/database/schema.sql');
$pdo->exec($schema);
echo "Utworzono tabele.\n";

// 2. Konta użytkowników (hasła hashowane przez User::create).
$adminId = User::create('admin@chat.pl', 'admin123', 'Administrator');
$aniaId  = User::create('ania@chat.pl',  'haslo123', 'Ania Nowak');
$janId   = User::create('jan@chat.pl',   'haslo123', 'Jan Kowalski');

// Konta przykładowe od razu zatwierdzamy (pomijamy weryfikację e-mail).
foreach ([$adminId, $aniaId, $janId] as $id) {
    User::verify($id);
}
User::setRole($adminId, 'admin');
echo "Utworzono użytkowników (admin@chat.pl / admin123, ania@chat.pl / haslo123).\n";

// 3. Rozmowy + relacja many-to-many (kto należy do której rozmowy).
$conv1 = Conversation::create('Projekt zaliczeniowy', true);
$conv2 = Conversation::create('Ania ↔ Jan', false);

Conversation::addMember($conv1, $adminId);
Conversation::addMember($conv1, $aniaId);
Conversation::addMember($conv1, $janId);
Conversation::addMember($conv2, $aniaId);
Conversation::addMember($conv2, $janId);
echo "Utworzono rozmowy i przypisano uczestników (many-to-many).\n";

// 4. Przykładowe wiadomości różnych typów.
$samples = [
    ['conversation_id' => $conv1, 'user_id' => $adminId, 'type' => 'tekst', 'body' => 'Witajcie w grupie projektowej!', 'priority' => 3, 'is_important' => 1],
    ['conversation_id' => $conv1, 'user_id' => $aniaId,  'type' => 'tekst', 'body' => 'Cześć, kiedy oddajemy projekt?', 'priority' => 2],
    ['conversation_id' => $conv1, 'user_id' => $janId,   'type' => 'link',  'body' => 'Przydatny poradnik PHP', 'link_url' => 'https://www.php.net/manual/pl/', 'priority' => 1],
    ['conversation_id' => $conv2, 'user_id' => $aniaId,  'type' => 'tekst', 'body' => 'Hej Jan, masz notatki z wykładu?', 'priority' => 2],
    ['conversation_id' => $conv2, 'user_id' => $janId,   'type' => 'tekst', 'body' => 'Tak, wyślę Ci je wieczorem.', 'priority' => 1],
    ['conversation_id' => $conv1, 'user_id' => $adminId, 'type' => 'tekst', 'body' => 'Pamiętajcie o testach jednostkowych.', 'priority' => 4, 'is_important' => 1],
];
foreach ($samples as $m) {
    Message::create($m);
}
echo "Dodano przykładowe wiadomości.\n";

// 5. Kontakty.
Contact::add($aniaId, $janId);
Contact::add($janId, $aniaId);
echo "Dodano przykładowe kontakty.\n";

echo "\nGotowe! Uruchom serwer:  php -S localhost:8000\n";
echo "Następnie otwórz w przeglądarce:  http://localhost:8000\n";
