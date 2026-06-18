<?php
/**
 * Auth
 * ----
 * Obsługa logowania, wylogowania i kontroli dostępu. Stan zalogowania
 * trzymamy w sesji (tylko ID użytkownika), a dane konta dociągamy z bazy.
 */
class Auth
{
    /**
     * Próba logowania e-mailem i hasłem.
     * Zwraca tablicę: ['ok' => bool, 'error' => ?string].
     */
    public static function attempt(string $email, string $password): array
    {
        $user = User::findByEmail($email);

        // password_verify porównuje hasło z hashem z bazy.
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['ok' => false, 'error' => 'Nieprawidłowy e-mail lub hasło.'];
        }
        if ((int) $user['is_verified'] === 0) {
            return ['ok' => false, 'error' => 'Konto nie zostało jeszcze potwierdzone. Sprawdź link aktywacyjny.'];
        }

        self::login((int) $user['id']);
        return ['ok' => true, 'error' => null];
    }

    /** Zapisuje zalogowanego użytkownika w sesji i ustawia status online. */
    public static function login(int $userId): void
    {
        session_regenerate_id(true);        // ochrona przed przejęciem sesji
        $_SESSION['user_id'] = $userId;
        User::setOnline($userId, true);
    }

    /** Wylogowanie — ustawia offline i czyści sesję. */
    public static function logout(): void
    {
        if (self::check()) {
            User::setOnline((int) $_SESSION['user_id'], false);
        }
        session_destroy();
    }

    /** Czy ktoś jest zalogowany. */
    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /** Zwraca dane zalogowanego użytkownika (lub null). */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return User::find((int) $_SESSION['user_id']);
    }

    /** Czy zalogowany użytkownik jest administratorem. */
    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user !== null && $user['role'] === 'admin';
    }

    /** Wymusza zalogowanie — w przeciwnym razie przekierowuje na logowanie. */
    public static function requireLogin(): void
    {
        // Brak sesji LUB konto z sesji już nie istnieje (np. zostało usunięte)
        // — czyścimy nieaktualną sesję i prosimy o ponowne zalogowanie.
        if (!self::check() || self::user() === null) {
            unset($_SESSION['user_id']);
            flash('Musisz się zalogować, aby korzystać z aplikacji.', 'error');
            redirect('index.php?page=login');
        }
    }

    /** Wymusza rolę administratora — inaczej pokazuje stronę 403. */
    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            show_error(403, 'Brak uprawnień. Ta sekcja jest dostępna tylko dla administratora.');
        }
    }
}
