<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/storage.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Неверный метод запроса']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    echo json_encode(['success' => false, 'error' => 'Неверный JSON']);
    exit;
}

$insertRows = [];
$updateRows = [];
$deleteRows = [];

if (array_is_list($payload)) {
    $insertRows = $payload;
} else {
    $insertRows = is_array($payload['insert'] ?? null) ? $payload['insert'] : [];
    $updateRows = is_array($payload['update'] ?? null) ? $payload['update'] : [];
    $deleteRows = is_array($payload['delete'] ?? null) ? $payload['delete'] : [];
}

$svodkiItems = loadJson('svodki_select.json');
$svodkiByNumber = [];
foreach ($svodkiItems as $item) {
    $number = (string)($item['number_naim'] ?? '');
    if ($number !== '') {
        $svodkiByNumber[$number] = $item;
    }
}

$records = loadJson('itog_sel_deg.json');
$savedCount = 0;
$errors = [];

function normSelDate($value): ?string {
    if (!is_string($value) && !is_numeric($value)) return null;
    $value = trim((string)$value);
    if ($value === '') return null;
    $dt = DateTime::createFromFormat('d.m.Y', $value) ?: DateTime::createFromFormat('Y-m-d', $value);
    return $dt ? $dt->format('Y-m-d') : null;
}

function findSelRecordIndex(array $records, int $idSvodki, string $garnizon, string $start, string $end): ?int {
    foreach ($records as $index => $rec) {
        if ((int)($rec['id_svodki'] ?? 0) !== $idSvodki) continue;
        if ((string)($rec['garnizon'] ?? '') !== $garnizon) continue;
        if ((string)($rec['data_start'] ?? '') !== $start) continue;
        if ((string)($rec['data_end'] ?? '') !== $end) continue;
        return $index;
    }
    return null;
}

foreach ($deleteRows as $row) {
    $id = (int)($row['id_zapisi'] ?? 0);
    if ($id <= 0) continue;
    $before = count($records);
    $records = array_values(array_filter($records, fn($rec) => (int)($rec['id'] ?? 0) !== $id));
    if (count($records) !== $before) $savedCount++;
}

foreach ($updateRows as $row) {
    $id = (int)($row['id_zapisi'] ?? 0);
    if ($id <= 0) continue;
    foreach ($records as &$rec) {
        if ((int)($rec['id'] ?? 0) !== $id) continue;
        $hasSplit = array_key_exists('neraskr', $row) || array_key_exists('raskr', $row);
        $neraskr = ($row['neraskr'] ?? '') === '' ? null : (int)$row['neraskr'];
        $raskr = ($row['raskr'] ?? '') === '' ? null : (int)$row['raskr'];
        $value = ($row['kolichestvo'] ?? '') === '' ? null : (int)$row['kolichestvo'];
        $rec['kolichestvo'] = $value;
        $rec['neraskr'] = $hasSplit ? $neraskr : ($rec['neraskr'] ?? null);
        $rec['raskr'] = $hasSplit ? $raskr : ($rec['raskr'] ?? null);
        $rec['data_sozdan'] = date('Y-m-d H:i:s');
        $savedCount++;
        break;
    }
    unset($rec);
}

foreach ($insertRows as $row) {
    if (!is_array($row)) {
        $errors[] = 'Получена неверная строка вставки';
        continue;
    }

    $number = trim((string)($row['number_naim'] ?? ''));
    $garnizon = trim((string)($row['garnizon'] ?? ''));
    $start = normSelDate($row['data_start'] ?? null);
    $end = normSelDate($row['data_end'] ?? null);
    $neraskr = ($row['neraskr'] ?? '') === '' ? null : (int)$row['neraskr'];
    $raskr = ($row['raskr'] ?? '') === '' ? null : (int)$row['raskr'];
    $value = ($row['kolichestvo'] ?? '') === '' ? null : (int)$row['kolichestvo'];

    if ($number === '' || $garnizon === '' || !$start || !$end) {
        $errors[] = 'Отсутствуют обязательные поля для сохранения сводки';
        continue;
    }

    $item = $svodkiByNumber[$number] ?? null;
    if (!$item) {
        $errors[] = "Пункт {$number} не найден в справочнике";
        continue;
    }

    $idSvodki = (int)($item['id'] ?? 0);
    if ($idSvodki <= 0) {
        $errors[] = "Для пункта {$number} не найден id_svodki";
        continue;
    }

    $index = findSelRecordIndex($records, $idSvodki, $garnizon, $start, $end);
    $hasAnyValue = ($value !== null && $value !== 0) || ($neraskr !== null && $neraskr !== 0) || ($raskr !== null && $raskr !== 0);
    if (!$hasAnyValue) {
        if ($index !== null) {
            array_splice($records, $index, 1);
            $savedCount++;
        }
        continue;
    }

    $record = [
        'id_svodki' => $idSvodki,
        'number_naim' => $number,
        'garnizon' => $garnizon,
        'data_start' => $start,
        'data_end' => $end,
        'kolichestvo' => $value,
        'neraskr' => $neraskr,
        'raskr' => $raskr,
        'data_sozdan' => date('Y-m-d H:i:s')
    ];

    if ($index !== null) {
        $records[$index] = array_merge($records[$index], $record);
    } else {
        $record['id'] = nextId($records);
        $records[] = $record;
    }
    $savedCount++;
}

if (!saveJson('itog_sel_deg.json', array_values($records))) {
    echo json_encode(['success' => false, 'error' => 'Ошибка записи файла данных']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Сохранено записей: ' . $savedCount,
    'saved_count' => $savedCount,
    'error_count' => count($errors),
    'errors' => $errors
]);
