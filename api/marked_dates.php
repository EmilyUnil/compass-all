<?php
/**
 * API: Отмеченные даты для календаря (обе таблицы)
 */
session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/storage.php';

$input   = json_decode(file_get_contents('php://input'), true);
$garnizon= isset($input['garnizon']) ? (string)$input['garnizon'] : null;
$mode    = $input['mode'] ?? 'selector'; // 'selector' или 'svodki'

if ($garnizon === null) {
    echo json_encode(['error' => 'Не указан garnizon']);
    exit;
}

$dataFile = $mode === 'svodki' ? 'itog_sel_deg.json' : 'itog_selector.json';
$records  = loadJson($dataFile);

$colors     = ['#30BA8F', '#FF9218', '#42AAFF', '#FF2B2B'];
$colorIndex = 0;
$markedDates= [];
$seenPeriods= [];

$mvdGarnizons = ['6','5','31','10','13','29','17'];

foreach ($records as $rec) {
    $recGar   = (string)($rec['garnizon'] ?? '');
    $recStart = $rec['data_start'] ?? '';
    $recEnd   = $rec['data_end']   ?? '';

    if (!$recStart || !$recEnd) continue;

    // Фильтр по гарнизону
    if ($garnizon === '88') {
        if (!in_array($recGar, $mvdGarnizons)) continue;
        $periodKey = $recGar . '|' . $recStart . '|' . $recEnd;
    } elseif ($garnizon === '0') {
        if ($recGar !== '6') continue;
        $periodKey = '6|' . $recStart . '|' . $recEnd;
    } else {
        if ($recGar !== $garnizon) continue;
        $periodKey = $garnizon . '|' . $recStart . '|' . $recEnd;
    }

    if (isset($seenPeriods[$periodKey])) continue;
    $seenPeriods[$periodKey] = true;

    $markedDates[] = [
        'period_id' => $periodKey,
        'start'     => $recStart,
        'end'       => $recEnd,
        'color'     => $colors[$colorIndex % count($colors)],
        'garnizon'  => $recGar,
    ];
    $colorIndex++;
}

// Ключ ответа зависит от режима (совместимость с фронтендом)
$key = $mode === 'svodki' ? 'dates' : 'periods';
echo json_encode(['success' => true, $key => $markedDates]);
