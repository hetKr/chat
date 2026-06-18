<?php /** Widok: formularz logowania. Zmienne: $errors, $old. */ ?>
<div class="card card-narrow">
    <h1>Logowanie</h1>

    <?php if (!empty($errors['email'])): ?>
        <div class="flash flash-error"><?= e($errors['email']) ?></div>
    <?php endif; ?>

    <form method="post" action="index.php?page=do_login">
        <?= csrf_field() ?>
        <label>E-mail
            <input type="email" name="email" value="<?= e($old['email'] ?? '') ?>" required>
        </label>
        <label>Hasło
            <input type="password" name="password" required>
        </label>
        <button class="btn btn-primary" type="submit">Zaloguj się</button>
    </form>

    <p class="muted">Nie masz konta? <a href="index.php?page=register">Zarejestruj się</a></p>
</div>
