<?php /** Widok: kontakty. Zmienne: $contacts, $addable. */ ?>
<div class="page-head"><h1>Moje kontakty</h1></div>

<div class="card">
    <h2>Dodaj kontakt</h2>
    <form method="post" action="index.php?page=contact_add" class="filters">
        <?= csrf_field() ?>
        <select name="contact_id" required>
            <option value="">— wybierz użytkownika —</option>
            <?php foreach ($addable as $u): ?>
                <option value="<?= (int) $u['id'] ?>"><?= e($u['display_name']) ?> (<?= e($u['email']) ?>)</option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit">Dodaj</button>
    </form>
</div>

<table class="table">
    <thead>
        <tr><th>Nazwa</th><th>E-mail</th><th>Status</th><th>Akcje</th></tr>
    </thead>
    <tbody>
    <?php if (empty($contacts)): ?>
        <tr><td colspan="4" class="empty">Nie masz jeszcze żadnych kontaktów.</td></tr>
    <?php else: ?>
        <?php foreach ($contacts as $c): ?>
            <tr>
                <td><?= e($c['display_name']) ?></td>
                <td><?= e($c['email']) ?></td>
                <td><?= $c['is_online'] ? '🟢 online' : '⚪ offline' ?></td>
                <td>
                    <form method="post" action="index.php?page=contact_delete" class="inline"
                          onsubmit="return confirm('Usunąć kontakt?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                        <button class="btn-sm btn-danger" type="submit">Usuń</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
