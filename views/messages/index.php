<?php
/**
 * Widok: lista wiadomości (tabela + filtry + podsumowanie + paginacja).
 * Zmienne: $result, $summary, $filters, $conversations, $types.
 */

// Pomocnik: buduje adres URL zachowując aktualne filtry, nadpisując wybrane.
$buildUrl = function (array $override) use ($filters): string {
    $params = array_merge([
        'page'            => 'messages',
        'search'          => $filters['search'],
        'type'            => $filters['type'],
        'conversation_id' => $filters['conversation_id'],
        'important'       => $filters['important'],
        'sort'            => $filters['sort'],
        'dir'             => $filters['dir'],
        'p'               => $filters['page'],
    ], $override);
    // Klucz strony paginacji w URL to 'page' rozmowy? Uwaga: 'page' to router.
    return 'index.php?' . http_build_query($params);
};

// Nagłówek kolumny z możliwością sortowania.
$sortLink = function (string $column, string $label) use ($filters, $buildUrl): string {
    $isActive = $filters['sort'] === $column;
    $nextDir  = ($isActive && $filters['dir'] === 'asc') ? 'desc' : 'asc';
    $arrow    = $isActive ? ($filters['dir'] === 'asc' ? ' ▲' : ' ▼') : '';
    $url = $buildUrl(['sort' => $column, 'dir' => $nextDir, 'page' => 'messages']);
    // Uwaga: paginacja używa parametru ?page liczbowego — patrz niżej w linkach stron.
    return '<a href="' . e($url) . '">' . e($label) . $arrow . '</a>';
};
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
    <a class="btn btn-ghost" href="<?= e($buildUrl(['page' => 'export', 'format' => 'csv'])) ?>">CSV</a>
    <a class="btn btn-ghost" href="<?= e($buildUrl(['page' => 'export', 'format' => 'json'])) ?>">JSON</a>
</div>

<!-- TABELA REKORDÓW -->
<table class="table">
    <thead>
        <tr>
            <th><?= $sortLink('type', 'Typ') ?></th>
            <th>Rozmowa</th>
            <th>Nadawca</th>
            <th>Treść</th>
            <th><?= $sortLink('priority', 'Priorytet') ?></th>
            <th><?= $sortLink('is_important', 'Ważna') ?></th>
            <th><?= $sortLink('created_at', 'Data') ?></th>
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
            <?php
            $url = 'index.php?' . http_build_query([
                'page'            => 'messages',
                'search'          => $filters['search'],
                'type'            => $filters['type'],
                'conversation_id' => $filters['conversation_id'],
                'important'       => $filters['important'],
                'sort'            => $filters['sort'],
                'dir'             => $filters['dir'],
            ]) . '&p=' . $p;
            ?>
            <a class="page-link <?= $result['page'] === $p ? 'active' : '' ?>" href="<?= e($url) ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>
<?php endif; ?>
