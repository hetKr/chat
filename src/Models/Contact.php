<?php
/**
 * Contact (model)
 * ---------------
 * Lista kontaktów użytkownika. Tabela `contacts` łączy dwóch użytkowników
 * (user_id = właściciel listy, contact_id = osoba na liście).
 */
class Contact
{
    /** Zwraca kontakty danego użytkownika wraz z danymi osoby. */
    public static function forUser(int $userId): array
    {
        return Database::run(
            'SELECT c.id, u.id AS user_id, u.display_name, u.email, u.is_online
             FROM contacts c
             JOIN users u ON u.id = c.contact_id
             WHERE c.user_id = :u
             ORDER BY u.display_name',
            [':u' => $userId]
        )->fetchAll();
    }

    /** Dodaje kontakt (ignoruje duplikaty dzięki UNIQUE w schemacie). */
    public static function add(int $userId, int $contactId): void
    {
        Database::run(
            'INSERT OR IGNORE INTO contacts (user_id, contact_id) VALUES (:u, :c)',
            [':u' => $userId, ':c' => $contactId]
        );
    }

    /** Usuwa kontakt — tylko jeśli należy do danego użytkownika. */
    public static function delete(int $contactRowId, int $userId): void
    {
        Database::run(
            'DELETE FROM contacts WHERE id = :id AND user_id = :u',
            [':id' => $contactRowId, ':u' => $userId]
        );
    }

    /** Użytkownicy, których można jeszcze dodać (nie są już kontaktem i to nie my). */
    public static function addable(int $userId): array
    {
        return Database::run(
            'SELECT id, display_name, email FROM users
             WHERE id != :u
               AND id NOT IN (SELECT contact_id FROM contacts WHERE user_id = :u2)
             ORDER BY display_name',
            [':u' => $userId, ':u2' => $userId]
        )->fetchAll();
    }
}
