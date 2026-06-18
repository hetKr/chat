<?php
/**
 * SettingsController
 * ------------------
 * Preferencje zalogowanego użytkownika. Na razie jedna: motyw (jasny/ciemny),
 * zapisywany w bazie (kolumna users.theme), więc jest pamiętany między wizytami.
 */
class SettingsController
{
    /** POST: przełączenie motywu light <-> dark. */
    public function toggleTheme(): void
    {
        csrf_check();
        $user  = Auth::user();
        $theme = $user['theme'] === 'dark' ? 'light' : 'dark';
        User::setTheme((int) $user['id'], $theme);
        // Wracamy na stronę, z której przyszliśmy.
        redirect($_POST['return'] ?? 'index.php?page=messages');
    }
}
