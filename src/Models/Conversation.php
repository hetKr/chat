<?php
/**
 * Conversation (model)
 * --------------------
 * Rozmowy oraz relacja many-to-many z użytkownikami (tabela conversation_user).
 */
class Conversation
{
    /** Tworzy rozmowę i zwraca jej ID. */
    public static function create(string $name, bool $isGroup): int
    {
        Database::run(
            'INSERT INTO conversations (name, is_group) VALUES (:name, :group)',
            [':name' => $name, ':group' => $isGroup ? 1 : 0]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    /** Dodaje użytkownika do rozmowy (wpis w tabeli łączącej). */
    public static function addMember(int $conversationId, int $userId): void
    {
        Database::run(
            'INSERT OR IGNORE INTO conversation_user (conversation_id, user_id) VALUES (:c, :u)',
            [':c' => $conversationId, ':u' => $userId]
        );
    }

    /**
     * Rozmowy, do których należy dany użytkownik.
     * To zapytanie korzysta z relacji many-to-many (JOIN przez tabelę łączącą).
     */
    public static function forUser(int $userId): array
    {
        return Database::run(
            'SELECT c.* FROM conversations c
             JOIN conversation_user cu ON cu.conversation_id = c.id
             WHERE cu.user_id = :u
             ORDER BY c.name',
            [':u' => $userId]
        )->fetchAll();
    }

    /** Zwraca listę ID rozmów użytkownika (używane do filtrowania wiadomości). */
    public static function idsForUser(int $userId): array
    {
        $rows = Database::run(
            'SELECT conversation_id FROM conversation_user WHERE user_id = :u',
            [':u' => $userId]
        )->fetchAll();
        return array_map(static fn($r) => (int) $r['conversation_id'], $rows);
    }
}
