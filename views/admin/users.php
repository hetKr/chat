<?php /** Widok: zarządzanie użytkownikami (admin). Zmienne: $users. */ ?>
<div class="page-head"><h1>Zarządzanie użytkownikami</h1></div>

<table class="table">
    <thead>
        <tr><th>ID</th><th>Nazwa</th><th>E-mail</th><th>Rola</th><th>Status</th><th>Utworzono</th><th>Akcje</th></tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= (int) $u['id'] ?></td>
            <td><?= e($u['display_name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><span class="badge"><?= e($u['role']) ?></span></td>
            <td><?= $u['is_verified'] ? '✔ zweryfikowany' : '✖ niezweryfikowany' ?></td>
            <td><?= e($u['created_at']) ?></td>
            <td class="actions">
                <!-- Zmiana roli -->
                <form method="post" action="index.php?page=admin_set_role" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                    <input type="hidden" name="role" value="<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>">
                    <button class="btn-sm" type="submit">
                        <?= $u['role'] === 'admin' ? 'Odbierz admina' : 'Nadaj admina' ?>
                    </button>
                </form>
                <!-- Usunięcie -->
                <form method="post" action="index.php?page=admin_delete" class="inline"
                      onsubmit="return confirm('Usunąć użytkownika?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                    <button class="btn-sm btn-danger" type="submit">Usuń</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
