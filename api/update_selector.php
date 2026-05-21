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
    $insertRows = is_array($payload['insert'] ?? null) ? $payload['insert'] : (is_array($payload['rows'] ?? null) ? $payload['rows'] : []);
    $updateRows = is_array($payload['update'] ?? null) ? $payload['update'] : [];
    $deleteRows = is_array($payload['delete'] ?? null) ? $payload['delete'] : [];
}

$textItems = loadJson('text_selector.json');
$records = loadJson('itog_selector.json');

$byNumber = [];
foreach ($textItems as $item) {
    $num = (string)($item['number_naim'] ?? '');
    if ($num === '') continue;
    if (!isset($byNumber[$num])) $byNumber[$num] = [];
    $byNumber[$num][] = $item;
}
foreach ($byNumber as &$items) {
    usort($items, fn($a, $b) => ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0)));
}
unset($items);

function normalizeDateForDb($value): ?string {
    if (!is_string($value) && !is_numeric($value)) return null;
    $value = trim((string)$value);
    if ($value === '') return null;
    $dt = DateTime::createFromFormat('d.m.Y', $value) ?: DateTime::createFromFormat('Y-m-d', $value);
    return $dt ? $dt->format('Y-m-d') : null;
}

function tokenToNumber($token): ?array {
    if ($token === null) return null;
    $token = trim((string)$token);
    if ($token === '') return null;

    $norm = str_replace(',', '.', $token);
    if (!preg_match('/^-?\d+(?:\.\d+)?$/', $norm)) {
        return null;
    }

    if (str_contains($norm, '.')) {
        return ['int' => null, 'float' => (float)$norm, 'text' => $token];
    }

    return ['int' => (int)$norm, 'float' => null, 'text' => $token];
}

function matchRecord(array $rec, int $idSvodki, string $garnizon, string $start, string $end, string $podrazdel): bool {
    return (int)($rec['id_svodki'] ?? 0) === $idSvodki
        && (string)($rec['garnizon'] ?? '') === $garnizon
        && (string)($rec['data_start'] ?? '') === $start
        && (string)($rec['data_end'] ?? '') === $end
        && (string)($rec['podrazdel'] ?? '') === $podrazdel;
}

$savedCount = 0;
$errors = [];

foreach ($insertRows as $idx => $row) {
    if (!is_array($row)) {
        $errors[] = "Строка $idx: неверный формат";
        continue;
    }

    $numberNaim = (string)($row['number_naim'] ?? '');
    $garnizon = trim((string)($row['garnizon'] ?? ''));
    $start = normalizeDateForDb($row['data_start'] ?? null);
    $end = normalizeDateForDb($row['data_end'] ?? null);
    $rawValue = trim((string)($row['kolichestvo'] ?? ''));

    if ($numberNaim === '' || $garnizon === '' || !$start || !$end) {
        $errors[] = "Строка $idx: отсутствуют обязательные поля";
        continue;
    }

    $items = $byNumber[$numberNaim] ?? [];
    if (!$items) {
        $errors[] = "Строка $idx: неизвестный number_naim={$numberNaim}";
        continue;
    }

    $parts = array_map('trim', explode('/', $rawValue));
    if (count($parts) === 1 && count($items) > 1) {
        // Одно значение для составной строки — пишем в первый подпункт.
        $parts = [$parts[0], ''];
    }

    // На каждый подпункт делаем upsert.
    foreach ($items as $partIdx => $item) {
        $token = $parts[$partIdx] ?? '';
        $num = tokenToNumber($token);

        $idSvodki = (int)($item['id'] ?? 0);
        $podrazdel = (string)($item['podrazdel'] ?? '');

        if (!$idSvodki) {
            continue;
        }

        if ($num === null) {
            $before = count($records);
            $records = array_values(array_filter($records, function ($rec) use ($idSvodki, $garnizon, $start, $end, $podrazdel) {
                return !matchRecord($rec, $idSvodki, $garnizon, $start, $end, $podrazdel);
            }));
            if (count($records) !== $before) $savedCount++;
            continue;
        }

        $updated = false;
        foreach ($records as &$rec) {
            if (matchRecord($rec, $idSvodki, $garnizon, $start, $end, $podrazdel)) {
                $rec['number_naim'] = $numberNaim;
                $rec['kolichestvo'] = $num['int'];
                $rec['kolichestvo_fl'] = $num['float'];
                $rec['value_text'] = $num['text'];
                $rec['data_sozdan'] = date('Y-m-d H:i:s');
                $updated = true;
                $savedCount++;
                break;
            }
        }
        unset($rec);

        if (!$updated) {
            $records[] = [
                'id' => nextId($records),
                'id_svodki' => $idSvodki,
                'number_naim' => $numberNaim,
                'garnizon' => $garnizon,
                'podrazdel' => $podrazdel,
                'data_start' => $start,
                'data_end' => $end,
                'kolichestvo' => $num['int'],
                'kolichestvo_fl' => $num['float'],
                'value_text' => $num['text'],
                'data_sozdan' => date('Y-m-d H:i:s'),
            ];
            $savedCount++;
        }
    }
}

// Поддержка legacy update по id записи
foreach ($updateRows as $row) {
    if (!is_array($row)) continue;
    $id = (int)($row['id_zapisi'] ?? 0);
    if ($id <= 0) continue;

    $num = tokenToNumber($row['kolichestvo'] ?? null);
    if ($num === null && isset($row['kolichestvo'])) {
        continue;
    }

    foreach ($records as &$rec) {
        if ((int)($rec['id'] ?? 0) === $id) {
            if ($num !== null) {
                $rec['kolichestvo'] = $num['int'];
                $rec['kolichestvo_fl'] = $num['float'];
                $rec['value_text'] = $num['text'];
            }
            $rec['data_sozdan'] = date('Y-m-d H:i:s');
            $savedCount++;
            break;
        }
    }
    unset($rec);
}

// Поддержка legacy delete
if (!empty($deleteRows)) {
    $deleteIds = [];
    foreach ($deleteRows as $row) {
        if (is_array($row) && isset($row['id_zapisi'])) {
            $deleteIds[] = (int)$row['id_zapisi'];
        }
    }
    if ($deleteIds) {
        $before = count($records);
        $records = array_values(array_filter($records, function ($rec) use ($deleteIds) {
            return !in_array((int)($rec['id'] ?? 0), $deleteIds, true);
        }));
        $savedCount += max(0, $before - count($records));
    }
}

if (!saveJson('itog_selector.json', array_values($records))) {
    echo json_encode(['success' => false, 'error' => 'Ошибка записи файла данных']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Сохранено записей: ' . $savedCount,
    'saved_count' => $savedCount,
    'error_count' => count($errors),
    'errors' => $errors,
]);
