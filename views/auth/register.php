<?php /** Widok: formularz rejestracji. Zmienne: $errors, $old. */ ?>
<div class="card card-narrow">
    <h1>Rejestracja</h1>

    <form method="post" action="index.php?page=do_register">
        <?= csrf_field() ?>

        <label>Nazwa wyświetlana
            <input type="text" name="name" value="<?= e($old['name'] ?? '') ?>" required>
        </label>
        <?php if (!empty($errors['name'])): ?><span class="error"><?= e($errors['name']) ?></span><?php endif; ?>

        <label>E-mail
            <input type="email" name="email" value="<?= e($old['email'] ?? '') ?>" required>
        </label>
        <?php if (!empty($errors['email'])): ?><span class="error"><?= e($errors['email']) ?></span><?php endif; ?>

        <label>Hasło (min. 6 znaków)
            <input type="password" name="password" required>
        </label>
        <?php if (!empty($errors['password'])): ?><span class="error"><?= e($errors['password']) ?></span><?php endif; ?>

        <button class="btn btn-primary" type="submit">Utwórz konto</button>
    </form>

    <p class="muted">Masz już konto? <a href="index.php?page=login">Zaloguj się</a></p>
</div>
