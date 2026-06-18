<?php
/**
 * Widok: formularz dodawania / edycji wiadomości.
 * Zmienne: $message (wartości pól), $errors, $id, $conversations, $types.
 *
 * Pola zależne od typu (treść / adres URL / załącznik) pokazują się i chowają
 * w zależności od zaznaczonego typu — realizuje to czysty CSS (selektor
 * ":checked ~"), dlatego radia i te pola są rodzeństwem w <div class="type-fields">.
 */
$val = fn(string $key, $default = '') => e((string) ($message[$key] ?? $default));
$isEdit = $id !== null;
$type   = $message['type'] ?? 'tekst';
?>
<div class="card">
    <h1><?= $isEdit ? 'Edycja wiadomości' : 'Nowa wiadomość' ?></h1>

    <?php if (empty($conversations)): ?>
        <div class="flash flash-error">
            Nie należysz jeszcze do żadnej rozmowy.
            <a href="index.php?page=conversation_create">Utwórz najpierw rozmowę</a>, aby wysyłać wiadomości.
        </div>
    <?php endif; ?>

    <form method="post" action="index.php?page=message_save" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $id ?>">
        <?php endif; ?>

        <!-- POLE: lista wyboru (select) -->
        <label>Rozmowa
            <select name="conversation_id">
                <option value="">— wybierz —</option>
                <?php foreach ($conversations as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"
                        <?= (string) ($message['conversation_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php if (!empty($errors['conversation_id'])): ?><span class="error"><?= e($errors['conversation_id']) ?></span><?php endif; ?>

        <!-- TYP + POLA ZALEŻNE OD TYPU (przełączane czystym CSS) -->
        <div class="type-fields">
            <p class="field-label">Typ wiadomości</p>

            <?php foreach ($types as $t): ?>
                <input type="radio" id="type-<?= e($t) ?>" name="type" value="<?= e($t) ?>"
                       class="type-radio" <?= $type === $t ? 'checked' : '' ?>>
                <label for="type-<?= e($t) ?>" class="type-chip"><?= e(ucfirst($t)) ?></label>
            <?php endforeach; ?>
            <?php if (!empty($errors['type'])): ?><span class="error"><?= e($errors['type']) ?></span><?php endif; ?>

            <!-- tekst -> treść -->
            <div class="field field-body">
                <label>Treść wiadomości
                    <textarea name="body" rows="4" placeholder="Wpisz treść (możesz użyć emotikonów 😀)"><?= $val('body') ?></textarea>
                </label>
                <?php if (!empty($errors['body'])): ?><span class="error"><?= e($errors['body']) ?></span><?php endif; ?>
            </div>

            <!-- link -> adres URL -->
            <div class="field field-link">
                <label>Adres URL
                    <input type="text" name="link_url" value="<?= $val('link_url') ?>" placeholder="https://...">
                </label>
                <?php if (!empty($errors['link_url'])): ?><span class="error"><?= e($errors['link_url']) ?></span><?php endif; ?>
            </div>

            <!-- obraz / film / plik -> załącznik -->
            <div class="field field-file">
                <label>Załącznik
                    <input type="file" name="file">
                </label>
                <?php if ($isEdit && !empty($message['file_path'])): ?>
                    <p class="muted">Aktualny plik: <a href="<?= e($message['file_path']) ?>" target="_blank">podgląd</a>
                        (wgranie nowego zastąpi obecny)</p>
                <?php endif; ?>
                <?php if (!empty($errors['file'])): ?><span class="error"><?= e($errors['file']) ?></span><?php endif; ?>
            </div>
        </div>

        <!-- POLE: liczbowe — priorytet (zawsze widoczne) -->
        <label>Priorytet (1–5)
            <input type="number" name="priority" min="1" max="5" value="<?= $val('priority', '1') ?>">
        </label>
        <?php if (!empty($errors['priority'])): ?><span class="error"><?= e($errors['priority']) ?></span><?php endif; ?>

        <!-- POLE: checkbox — oznaczenie ważności -->
        <label class="check">
            <input type="checkbox" name="is_important" value="1" <?= !empty($message['is_important']) ? 'checked' : '' ?>>
            Oznacz jako ważną
        </label>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Zapisz zmiany' : 'Wyślij wiadomość' ?></button>
            <a class="btn btn-ghost" href="index.php?page=messages">Anuluj</a>
        </div>
    </form>
</div>
