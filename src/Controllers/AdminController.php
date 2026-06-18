<?php
/**
 * AdminController
 * ---------------
 * Panel administratora: zarządzanie użytkownikami i analiza aktywności.
 * Dostęp tylko dla roli 'admin' (sprawdzane w routerze przez Auth::requireAdmin).
 */
class AdminController
{
    /** GET: pulpit z zagregowanymi danymi (statystyki użytkowników i wiadomości). */
    public function dashboard(): void
    {
        view('admin/dashboard', [
            'userStats'    => User::stats(),
            'messageStats' => Message::aggregate(null),   // null = wszystkie wiadomości
        ]);
    }

    /** GET: lista wszystkich użytkowników z akcjami. */
    public function users(): void
    {
        view('admin/users', ['users' => User::all(), 'myId' => (int) Auth::user()['id']]);
    }

    /** POST: zmiana roli użytkownika (admin <-> user). */
    public function setRole(): void
    {
        csrf_check();
        $id   = (int) ($_POST['id'] ?? 0);
        $role = $_POST['role'] === 'admin' ? 'admin' : 'user';
        $me   = Auth::user();

        // Administrator nie może odebrać uprawnień samemu sobie
        // (inaczej mógłby przypadkiem zablokować sobie dostęp do panelu).
        if ($id === (int) $me['id'] && $role !== 'admin') {
            flash('Nie możesz odebrać uprawnień administratora samemu sobie.', 'error');
        } else {
            User::setRole($id, $role);
            flash('Zmieniono rolę użytkownika.');
        }
        redirect('index.php?page=admin_users');
    }

    /** POST: usunięcie użytkownika (nie można usunąć samego siebie). */
    public function deleteUser(): void
    {
        csrf_check();
        $id = (int) ($_POST['id'] ?? 0);
        $me = Auth::user();

        if ($id === (int) $me['id']) {
            flash('Nie możesz usunąć własnego konta.', 'error');
        } else {
            User::delete($id);
            flash('Użytkownik został usunięty.');
        }
        redirect('index.php?page=admin_users');
    }
}
