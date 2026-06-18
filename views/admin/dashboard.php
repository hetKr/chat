<?php /** Widok: pulpit administratora. Zmienne: $userStats, $messageStats. */ ?>
<div class="page-head"><h1>Panel administratora</h1></div>

<h2>Użytkownicy</h2>
<div class="summary">
    <div class="stat"><span class="stat-num"><?= (int) $userStats['total'] ?></span> użytkowników</div>
    <div class="stat"><span class="stat-num"><?= (int) $userStats['admins'] ?></span> administratorów</div>
    <div class="stat"><span class="stat-num"><?= (int) $userStats['verified'] ?></span> zweryfikowanych</div>
    <div class="stat"><span class="stat-num"><?= (int) $userStats['online'] ?></span> online</div>
</div>

<h2>Aktywność — wiadomości</h2>
<div class="summary">
    <div class="stat"><span class="stat-num"><?= (int) $messageStats['total'] ?></span> wiadomości łącznie</div>
    <div class="stat"><span class="stat-num"><?= (int) $messageStats['important'] ?></span> ważnych</div>
    <?php foreach ($messageStats['by_type'] as $t): ?>
        <div class="stat"><span class="stat-num"><?= (int) $t['c'] ?></span> typu „<?= e($t['type']) ?>”</div>
    <?php endforeach; ?>
</div>

<p><a class="btn btn-primary" href="index.php?page=admin_users">Zarządzaj użytkownikami</a></p>
