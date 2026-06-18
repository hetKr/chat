<?php
/**
 * User (model)
 * ------------
 * Reprezentuje użytkownika i odpowiada za wszystkie operacje na tabeli `users`.
 * Hasła są zawsze zapisywane jako hash (password_hash), nigdy jawnie.
 */
class User
{
    /**
     * Tworzy nowego użytkownika. Zwraca jego ID.
     * Hasło hashujemy algorytmem bcrypt przez password_hash().
     */
    public static function create(string $email, string $password, string $name, string $provider = 'local'): int
    {
        // Konta założone przez "konto społecznościowe" są od razu zweryfikowane.
        $isVerified = $provider === 'local' ? 0 : 1;
        $token      = $provider === 'local' ? bin2hex(random_bytes(16)) : null;

        Database::run(
            'INSERT INTO users (email, password_hash, display_name, auth_provider, is_verified, verification_token)
             VALUES (:email, :hash, :name, :provider, :verified, :token)',
            [
                ':email'    => $email,
                ':hash'     => password_hash($password, PASSWORD_BCRYPT),
                ':name'     => $name,
                ':provider' => $provider,
                ':verified' => $isVerified,
                ':token'    => $token,
            ]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    /** Znajduje użytkownika po e-mailu (lub null). */
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::run('SELECT * FROM users WHERE email = :email', [':email' => $email]);
        return $stmt->fetch() ?: null;
    }

    /** Znajduje użytkownika po ID (lub null). */
    public static function find(int $id): ?array
    {
        $stmt = Database::run('SELECT * FROM users WHERE id = :id', [':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Znajduje użytkownika po tokenie weryfikacyjnym (lub null). */
    public static function findByToken(string $token): ?array
    {
        $stmt = Database::run('SELECT * FROM users WHERE verification_token = :t', [':t' => $token]);
        return $stmt->fetch() ?: null;
    }

    /** Oznacza konto jako zweryfikowane (po kliknięciu linka aktywacyjnego). */
    public static function verify(int $id): void
    {
        Database::run(
            'UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = :id',
            [':id' => $id]
        );
    }

    /** Zapisuje preferencję motywu użytkownika (light/dark). */
    public static function setTheme(int $id, string $theme): void
    {
        Database::run('UPDATE users SET theme = :t WHERE id = :id', [':t' => $theme, ':id' => $id]);
    }

    /** Ustawia status online/offline. */
    public static function setOnline(int $id, bool $online): void
    {
        Database::run('UPDATE users SET is_online = :o WHERE id = :id', [':o' => $online ? 1 : 0, ':id' => $id]);
    }

    /** Zmienia rolę użytkownika (panel administratora). */
    public static function setRole(int $id, string $role): void
    {
        Database::run('UPDATE users SET role = :r WHERE id = :id', [':r' => $role, ':id' => $id]);
    }

    /** Usuwa użytkownika (kaskadowo usuwa też jego wiadomości i kontakty). */
    public static function delete(int $id): void
    {
        Database::run('DELETE FROM users WHERE id = :id', [':id' => $id]);
    }

    /** Lista wszystkich użytkowników (dla panelu admina i list wyboru). */
    public static function all(): array
    {
        return Database::run('SELECT * FROM users ORDER BY display_name')->fetchAll();
    }

    /** Wszyscy użytkownicy poza jednym (np. poza zalogowanym) — do wyboru uczestników. */
    public static function allExcept(int $id): array
    {
        return Database::run(
            'SELECT id, display_name, email FROM users WHERE id != :id ORDER BY display_name',
            [':id' => $id]
        )->fetchAll();
    }

    /** Statystyki użytkowników do panelu administratora. */
    public static function stats(): array
    {
        $pdo = Database::pdo();
        return [
            'total'    => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'admins'   => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn(),
            'verified' => (int) $pdo->query('SELECT COUNT(*) FROM users WHERE is_verified = 1')->fetchColumn(),
            'online'   => (int) $pdo->query('SELECT COUNT(*) FROM users WHERE is_online = 1')->fetchColumn(),
        ];
    }
}
