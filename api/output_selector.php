<?php
/**
 * API: Получение данных еженедельных отчётов (selector)
 * Заменяет запросы к БД на работу с JSON-файлом
 */
session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/storage.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Неверный метод запроса']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action   = $input['action'] ?? null;

// ── Действие: получить весь справочник ──────────────────────────────────────
if ($action === 'fetch_all') {
    $items = loadJson('text_selector.json');
    echo json_encode(['success' => true, 'data' => $items]);
    exit;
}

// ── Получение данных за период ───────────────────────────────────────────────
$startDate = $input['start_date'] ?? '';
$endDate   = $input['end_date']   ?? '';
$garnizon  = $input['garnizon']   ?? null;

if (empty($startDate) || empty($endDate) || $garnizon === null) {
    echo json_encode(['success' => true, 'data' => [], 'period_count' => 0, 'has_empty_days' => true]);
    exit;
}

// Проверка формата дат
if (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $startDate) || !preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $endDate)) {
    echo json_encode(['success' => true, 'data' => [], 'period_count' => 0, 'has_empty_days' => true]);
    exit;
}

$startDt = DateTime::createFromFormat('d.m.Y', $startDate);
$endDt   = DateTime::createFromFormat('d.m.Y', $endDate);
if (!$startDt || !$endDt || $startDt > $endDt) {
    echo json_encode(['success' => true, 'data' => [], 'period_count' => 0, 'has_empty_days' => true]);
    exit;
}

$startSql = $startDt->format('Y-m-d');
$endSql   = $endDt->format('Y-m-d');
$garnizon = (string)$garnizon;

// ── Справочник пунктов ───────────────────────────────────────────────────────
$textItems = loadJson('text_selector.json');
$textMap   = [];
foreach ($textItems as $item) {
    $textMap[$item['id']] = $item;
}

// ── Записи данных ────────────────────────────────────────────────────────────
$allRecords = loadJson('itog_selector.json');

// Фильтрация: запись пересекается с запрашиваемым периодом и соответствует гарнизону
$filtered = array_filter($allRecords, function ($rec) use ($startSql, $endSql, $garnizon) {
    $recStart = $rec['data_start'] ?? '';
    $recEnd   = $rec['data_end']   ?? '';
    $recGar   = (string)($rec['garnizon'] ?? '');

    if ($garnizon === '88') {
        // МВД — суммируем по всем гарнизонам
        if (!in_array($recGar, ['6','5','31','10','13','29','17'])) return false;
    } else {
        if ($recGar !== $garnizon) return false;
    }

    // Пересечение периодов: [recStart..recEnd] ∩ [startSql..endSql] ≠ ∅
    return $recStart <= $endSql && $recEnd >= $startSql
        && ($rec['kolichestvo'] !== null || $rec['kolichestvo_fl'] !== null);
});

// ── Агрегация по id_svodki ────────────────────────────────────────────────────
$aggregated = [];
foreach ($filtered as $rec) {
    $idSvodki = $rec['id_svodki'];
    $gar      = $garnizon === '88' ? '88' : (string)($rec['garnizon'] ?? '');

    $key = $idSvodki . '|' . $rec['data_start'] . '|' . $rec['data_end'] . '|' . $rec['podrazdel'] . '|' . $gar;

    $kol = $rec['kolichestvo'] ?? $rec['kolichestvo_fl'] ?? null;

    if (!isset($aggregated[$key])) {
        $aggregated[$key] = [
            'id_svodki'   => $idSvodki,
            'garnizon'    => $gar,
            'podrazdel'   => $rec['podrazdel'] ?? '',
            'data_start'  => $rec['data_start'],
            'data_end'    => $rec['data_end'],
            'kolichestvo_sum' => 0,
            'kolichestvo_fl'  => null,
            'is_fl'       => ($rec['kolichestvo_fl'] !== null && $rec['kolichestvo'] === null),
        ];
    }

    if ($aggregated[$key]['is_fl']) {
        $aggregated[$key]['kolichestvo_fl'] = (float)$kol;
    } else {
        $aggregated[$key]['kolichestvo_sum'] += (int)$kol;
    }
}

// ── Формируем ответ ───────────────────────────────────────────────────────────
$responseData = [];
foreach ($aggregated as $row) {
    $item = $textMap[$row['id_svodki']] ?? null;
    if (!$item) continue;

    $kol = $row['is_fl'] ? $row['kolichestvo_fl'] : $row['kolichestvo_sum'];
    // Форматируем: убираем лишние нули
    if ($kol !== null) {
        $kol_float = (float)$kol;
        if ($kol_float == (int)$kol_float) {
            $kol = (string)(int)$kol_float;
        } else {
            $kol = rtrim(rtrim(sprintf('%.15g', $kol_float), '0'), '.');
        }
    }

    $dsFormatted = DateTime::createFromFormat('Y-m-d', $row['data_start']);
    $deFormatted = DateTime::createFromFormat('Y-m-d', $row['data_end']);

    $responseData[] = [
        'id_svodki'   => $row['id_svodki'],
        'number_naim' => (string)$item['number_naim'],
        'naimenov'    => $item['naimenov'],
        'garnizon'    => $row['garnizon'],
        'podrazdel'   => $row['podrazdel'],
        'data_start'  => $dsFormatted ? $dsFormatted->format('d.m.Y') : $row['data_start'],
        'data_end'    => $deFormatted ? $deFormatted->format('d.m.Y') : $row['data_end'],
        'kolichestvo' => (string)($kol ?? ''),
    ];
}

// Сортируем по number_naim
usort($responseData, fn($a, $b) => version_compare($a['number_naim'], $b['number_naim']));

// ── Подсчёт периодов и пустых дней ───────────────────────────────────────────
$uniquePeriods = [];
foreach ($filtered as $rec) {
    $key = $rec['data_start'] . '|' . $rec['data_end'];
    $uniquePeriods[$key] = true;
}
$periodCount = count($uniquePeriods);

// Проверяем, есть ли дни без данных в запрашиваемом периоде
$hasEmptyDays = false;
$current = clone $startDt;
while ($current <= $endDt) {
    $checkDate = $current->format('Y-m-d');
    $covered = false;
    foreach ($allRecords as $rec) {
        $recGar = (string)($rec['garnizon'] ?? '');
        $matchGar = $garnizon === '88'
            ? in_array($recGar, ['6','5','31','10','13','29','17'])
            : $recGar === $garnizon;
        if ($matchGar && $rec['data_start'] <= $checkDate && $rec['data_end'] >= $checkDate
            && ($rec['kolichestvo'] !== null || $rec['kolichestvo_fl'] !== null)) {
            $covered = true;
            break;
        }
    }
    if (!$covered) {
        $hasEmptyDays = true;
        break;
    }
    $current->modify('+1 day');
}

echo json_encode([
    'success'      => true,
    'data'         => $responseData,
    'period_count' => $periodCount,
    'has_empty_days' => $hasEmptyDays,
]);
