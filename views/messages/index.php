<?php
/**
 * Widok: lista wiadomości (tabela + filtry + podsumowanie + paginacja).
 * Zmienne: $result, $summary, $filters, $conversations, $types.
 */

/**
 * Buduje adres listy wiadomości zachowując aktualne filtry.
 * $override pozwala nadpisać wybrane parametry (np. numer strony albo sortowanie).
 */
function messages_url(array $filters, array $override = []): string
{
    $params = [
        'page'            => 'messages',
        'search'          => $filters['search'],
        'type'            => $filters['type'],
        'conversation_id' => $filters['conversation_id'],
        'important'       => $filters['important'],
        'sort'            => $filters['sort'],
        'dir'             => $filters['dir'],
        'p'               => $filters['page'],   // 'p' = numer strony (parametr 'page' to router)
    ];
    return 'index.php?' . http_build_query(array_merge($params, $override));
}

/** Zwraca nagłówek kolumny jako odnośnik zmieniający sortowanie (ze strzałką kierunku). */
function sort_header(array $filters, string $column, string $label): string
{
    $isActive = $filters['sort'] === $column;
    $nextDir  = ($isActive && $filters['dir'] === 'asc') ? 'desc' : 'asc';
    $arrow    = $isActive ? ($filters['dir'] === 'asc' ? ' ▲' : ' ▼') : '';
    $url = messages_url($filters, ['sort' => $column, 'dir' => $nextDir]);
    return '<a href="' . e($url) . '">' . e($label) . $arrow . '</a>';
}
?>

<div class="page-head">
    <h1>Wiadomości</h1>
    <div class="head-actions">
        <a class="btn btn-ghost" href="index.php?page=conversation_create">+ Nowa rozmowa</a>
        <a class="btn btn-primary" href="index.php?page=message_create">+ Nowa wiadomość</a>
    </div>
</div>

<!-- PANEL PODSUMOWANIA (dane zagregowane) -->
<div class="summary">
    <div class="stat"><span class="stat-num"><?= (int) $summary['total'] ?></span> wiadomości łącznie</div>
    <div class="stat"><span class="stat-num"><?= (int) $summary['important'] ?></span> oznaczonych jako ważne</div>
    <?php foreach ($summary['by_type'] as $t): ?>
        <div class="stat"><span class="stat-num"><?= (int) $t['c'] ?></span> typu „<?= e($t['type']) ?>”</div>
    <?php endforeach; ?>
</div>

<!-- FORMULARZ WYSZUKIWANIA I FILTRÓW (3 niezależne kryteria) -->
<form class="filters" method="get" action="index.php">
    <input type="hidden" name="page" value="messages">
    <input type="text" name="search" placeholder="Szukaj w treści lub nadawcy..."
           value="<?= e($filters['search']) ?>">

    <select name="type">
        <option value="">— typ —</option>
        <?php foreach ($types as $t): ?>
            <option value="<?= e($t) ?>" <?= $filters['type'] === $t ? 'selected' : '' ?>><?= e($t) ?></option>
        <?php endforeach; ?>
    </select>

    <select name="conversation_id">
        <option value="">— rozmowa —</option>
        <?php foreach ($conversations as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (string) $filters['conversation_id'] === (string) $c['id'] ? 'selected' : '' ?>>
                <?= e($c['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label class="check">
        <input type="checkbox" name="important" value="1" <?= $filters['important'] ? 'checked' : '' ?>>
        tylko ważne
    </label>

    <button class="btn" type="submit">Filtruj</button>
    <a class="btn btn-ghost" href="index.php?page=messages">Wyczyść</a>
</form>

<!-- PRZYCISKI EKSPORTU -->
<div class="export-bar">
    Eksport widocznych danych:
    <a class="btn btn-ghost" href="<?= e(messages_url($filters, ['page' => 'export', 'format' => 'csv'])) ?>">CSV</a>
    <a class="btn btn-ghost" href="<?= e(messages_url($filters, ['page' => 'export', 'format' => 'json'])) ?>">JSON</a>
</div>

<!-- TABELA REKORDÓW -->
<table class="table">
    <thead>
        <tr>
            <th><?= sort_header($filters, 'type', 'Typ') ?></th>
            <th>Rozmowa</th>
            <th>Nadawca</th>
            <th>Treść</th>
            <th><?= sort_header($filters, 'priority', 'Priorytet') ?></th>
            <th><?= sort_header($filters, 'is_important', 'Ważna') ?></th>
            <th><?= sort_header($filters, 'created_at', 'Data') ?></th>
            <th>Akcje</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($result['rows'])): ?>
        <tr><td colspan="8" class="empty">Brak wiadomości spełniających kryteria.</td></tr>
    <?php else: ?>
        <?php foreach ($result['rows'] as $m): ?>
            <tr>
                <td><span class="badge"><?= e($m['type']) ?></span></td>
                <td><?= e($m['conversation_name']) ?></td>
                <td><?= e($m['author']) ?></td>
                <td>
                    <?= e(mb_strimwidth((string) $m['body'], 0, 60, '...')) ?>
                    <?php if (!empty($m['file_path'])): ?>
                        <br><a href="<?= e($m['file_path']) ?>" target="_blank">📎 załącznik</a>
                    <?php endif; ?>
                    <?php if (!empty($m['link_url'])): ?>
                        <br><a href="<?= e($m['link_url']) ?>" target="_blank">🔗 link</a>
                    <?php endif; ?>
                </td>
                <td><?= (int) $m['priority'] ?></td>
                <td><?= $m['is_important'] ? '⭐' : '—' ?></td>
                <td><?= e($m['created_at']) ?></td>
                <td class="actions">
                    <a class="btn-sm" href="index.php?page=message_edit&id=<?= (int) $m['id'] ?>">Edytuj</a>
                    <form method="post" action="index.php?page=message_delete" class="inline"
                          onsubmit="return confirm('Usunąć tę wiadomość?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                        <button class="btn-sm btn-danger" type="submit">Usuń</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

<!-- PAGINACJA -->
<?php if ($result['pages'] > 1): ?>
    <div class="pagination">
        <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
            <a class="page-link <?= $result['page'] === $p ? 'active' : '' ?>"
               href="<?= e(messages_url($filters, ['p' => $p])) ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>
<?php endif; ?>
