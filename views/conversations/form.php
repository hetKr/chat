<?php /** Widok: formularz nowej rozmowy. Zmienne: $errors, $old, $people. */ ?>
<div class="card">
    <h1>Nowa rozmowa</h1>

    <form method="post" action="index.php?page=conversation_save">
        <?= csrf_field() ?>

        <label>Nazwa rozmowy
            <input type="text" name="name" value="<?= e($old['name'] ?? '') ?>" required>
        </label>
        <?php if (!empty($errors['name'])): ?><span class="error"><?= e($errors['name']) ?></span><?php endif; ?>

        <label class="check">
            <input type="checkbox" name="is_group" value="1" <?= !empty($old['is_group']) ? 'checked' : '' ?>>
            Rozmowa grupowa
        </label>

        <label>Uczestnicy (przytrzymaj Ctrl, aby zaznaczyć kilku)
            <select name="members[]" multiple size="5">
                <?php foreach ($people as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"><?= e($p['display_name']) ?> (<?= e($p['email']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Utwórz rozmowę</button>
            <a class="btn btn-ghost" href="index.php?page=messages">Anuluj</a>
        </div>
    </form>
</div>
