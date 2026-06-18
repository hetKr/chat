<?php
/**
 * layout.php — wspólna ramka strony (nagłówek, menu, stopka).
 * Widoki przekazują gotowy HTML w zmiennej $content (patrz funkcja view()).
 */
$currentUser = Auth::user();
$theme = $currentUser['theme'] ?? 'light';
$flash = take_flash();
?>
<!DOCTYPE html>
<html lang="pl" data-theme="<?= e($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Komunikator — aplikacja chatowa</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="index.php?page=messages">💬 Komunikator</a>

    <?php if ($currentUser): ?>
        <nav class="nav">
            <a href="index.php?page=messages">Wiadomości</a>
            <a href="index.php?page=contacts">Kontakty</a>
            <?php if ($currentUser['role'] === 'admin'): ?>
                <a href="index.php?page=admin">Panel admina</a>
            <?php endif; ?>
        </nav>
        <div class="user-box">
            <span class="online-dot" title="online"></span>
            <span><?= e($currentUser['display_name']) ?></span>
            <form method="post" action="index.php?page=toggle_theme" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="return" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                <button class="btn-link" title="Zmień motyw">
                    <?= $theme === 'dark' ? '☀️' : '🌙' ?>
                </button>
            </form>
            <a class="btn-link" href="index.php?page=logout">Wyloguj</a>
        </div>
    <?php endif; ?>
</header>

<main class="container">
    <?php if ($flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <?= $content ?>
</main>

<footer class="footer">
    Aplikacja chatowa — projekt zaliczeniowy PHP &middot; PJATK
</footer>
</body>
</html>
