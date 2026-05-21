<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/storage.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Неверный метод запроса']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$startDate = $input['start_date'] ?? '';
$endDate = $input['end_date'] ?? '';
$garnizon = isset($input['garnizon']) ? (string)$input['garnizon'] : null;

if ($startDate === '' || $endDate === '' || $garnizon === null) {
    echo json_encode(['success' => false, 'error' => 'Нет данных для обработки']);
    exit;
}

$startDt = DateTime::createFromFormat('Y-m-d', $startDate) ?: DateTime::createFromFormat('d.m.Y', $startDate);
$endDt = DateTime::createFromFormat('Y-m-d', $endDate) ?: DateTime::createFromFormat('d.m.Y', $endDate);
if (!$startDt || !$endDt) {
    echo json_encode(['success' => false, 'error' => 'Неверный формат дат']);
    exit;
}

$startSql = $startDt->format('Y-m-d');
$endSql = $endDt->format('Y-m-d');
if ($startSql > $endSql) {
    echo json_encode(['success' => false, 'error' => 'Начальная дата не может быть позже конечной']);
    exit;
}

$svodkiItems = loadJson('svodki_select.json');
$svodkiMap = [];
$svodkiByNumber = [];
foreach ($svodkiItems as $item) {
    $id = (int)($item['id'] ?? 0);
    $number = (string)($item['number_naim'] ?? '');
    $svodkiMap[$id] = $item;
    if ($number !== '') {
        $svodkiByNumber[$number] = $item;
    }
}

$allRecords = loadJson('itog_sel_deg.json');
$mvdGarnizons = ['6', '5', '31', '10', '13', '29', '17'];

$filtered = array_filter($allRecords, function ($rec) use ($startSql, $endSql, $garnizon, $mvdGarnizons) {
    $recStart = (string)($rec['data_start'] ?? '');
    $recEnd = (string)($rec['data_end'] ?? '');
    $recGar = (string)($rec['garnizon'] ?? '');

    if (!($recStart <= $endSql && $recEnd >= $startSql)) {
        return false;
    }

    if ($garnizon === '88') {
        return in_array($recGar, $mvdGarnizons, true);
    }

    return $recGar === $garnizon;
});

$aggregated = [];
foreach ($filtered as $rec) {
    $idSvodki = (int)($rec['id_svodki'] ?? 0);
    if ($idSvodki <= 0) {
        $fallbackItem = $svodkiByNumber[(string)($rec['number_naim'] ?? '')] ?? null;
        $idSvodki = (int)($fallbackItem['id'] ?? 0);
    }
    if ($idSvodki <= 0) {
        continue;
    }

    $neraskr = ($rec['neraskr'] === null || $rec['neraskr'] === '') ? 0 : (int)$rec['neraskr'];
    $raskr = ($rec['raskr'] === null || $rec['raskr'] === '') ? 0 : (int)$rec['raskr'];
    $value = $rec['kolichestvo'] ?? null;
    if ($value === null || $value === '') {
        $value = $neraskr + $raskr;
    }
    $value = ($value === null || $value === '') ? 0 : (int)$value;
    if ($value === 0 && $neraskr === 0 && $raskr === 0) {
        continue;
    }

    $keyGarnizon = $garnizon === '88' ? '88' : (string)($rec['garnizon'] ?? '');
    $key = $idSvodki . '|' . ($rec['data_start'] ?? '') . '|' . ($rec['data_end'] ?? '') . '|' . $keyGarnizon;

    if (!isset($aggregated[$key])) {
        $aggregated[$key] = [
            'id_zapisi' => $garnizon === '88' ? null : ($rec['id'] ?? null),
            'id_svodki' => $idSvodki,
            'garnizon' => $keyGarnizon,
            'data_start' => (string)($rec['data_start'] ?? ''),
            'data_end' => (string)($rec['data_end'] ?? ''),
            'kolichestvo' => 0,
            'neraskr' => 0,
            'raskr' => 0,
        ];
    }

    $aggregated[$key]['kolichestvo'] += $value;
    $aggregated[$key]['neraskr'] += $neraskr;
    $aggregated[$key]['raskr'] += $raskr;
}

$response = [];
foreach ($aggregated as $row) {
    $item = $svodkiMap[(int)$row['id_svodki']] ?? null;
    if (!$item) {
        continue;
    }

    $dsFmt = DateTime::createFromFormat('Y-m-d', $row['data_start']);
    $deFmt = DateTime::createFromFormat('Y-m-d', $row['data_end']);

    $response[] = [
        'id_zapisi' => $row['id_zapisi'],
        'id_svodki' => $row['id_svodki'],
        'number_naim' => (string)($item['number_naim'] ?? ''),
        'naimenov' => (string)($item['naimenov'] ?? ''),
        'pole' => (int)($item['pole'] ?? 1),
        'garnizon' => $row['garnizon'],
        'data_start' => $dsFmt ? $dsFmt->format('d.m.Y') : $row['data_start'],
        'data_end' => $deFmt ? $deFmt->format('d.m.Y') : $row['data_end'],
        'kolichestvo' => $row['kolichestvo'],
        'neraskr' => $row['neraskr'],
        'raskr' => $row['raskr']
    ];
}

usort($response, fn($a, $b) => version_compare((string)$a['number_naim'], (string)$b['number_naim']));

echo json_encode(['success' => true, 'data' => $response]);
