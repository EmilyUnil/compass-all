<?php
/**
 * API: Данные для графиков (обе таблицы)
 * mode: 'selector' | 'svodki'
 */
session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/storage.php';

$input      = $GLOBALS['__chart_payload_override'] ?? json_decode(file_get_contents('php://input'), true);
$startDate  = $input['startDate']   ?? date('Y-m-d', strtotime('-30 days'));
$endDate    = $input['endDate']     ?? date('Y-m-d', strtotime('-1 day'));
$garnizon   = (string)($input['garnizon']   ?? '');
$numberNaim = $input['numberNaim']  ?? null;
$mode       = $input['mode']        ?? 'selector';

if (!DateTime::createFromFormat('Y-m-d', $startDate) || !DateTime::createFromFormat('Y-m-d', $endDate)) {
    echo json_encode(['success' => false, 'error' => 'Неверный формат дат']);
    exit;
}

// ── Цвета для графика (фиксированная палитра) ─────────────────────────────────
$palette = [
    '#4682B4','#FF6B6B','#2ECC71','#F39C12','#9B59B6',
    '#1ABC9C','#E74C3C','#3498DB','#E67E22','#27AE60',
    '#8E44AD','#16A085','#D35400','#2980B9','#C0392B',
];

function getColor(int $idx, array $palette): string {
    return $palette[$idx % count($palette)];
}

// ── Загружаем справочник ──────────────────────────────────────────────────────
if ($mode === 'svodki') {
    $textItems = loadJson('svodki_select.json');
    $dataFile  = 'itog_sel_deg.json';
} else {
    $textItems = loadJson('text_selector.json');
    $dataFile  = 'itog_selector.json';
}

$textMap = [];
foreach ($textItems as $item) {
    $textMap[(int)$item['id']] = $item;
}

$allRecords = loadJson($dataFile);
$mvdGarnizons = ['6','5','31','10','13','29','17'];

// ── Фильтрация записей ─────────────────────────────────────────────────────────
$filtered = array_filter($allRecords, function ($rec) use ($startDate, $endDate, $garnizon, $mvdGarnizons, $numberNaim, $textMap, $mode) {
    $recGar   = (string)($rec['garnizon'] ?? '');
    $recStart = $rec['data_start'] ?? '';
    $recEnd   = $rec['data_end']   ?? '';

    if (!($recStart >= $startDate && $recEnd <= $endDate)) return false;

    if ($garnizon === '88') {
        if (!in_array($recGar, $mvdGarnizons)) return false;
    } elseif ($garnizon === '0') {
        if ($recGar !== '6') return false;
    } else {
        if ($recGar !== $garnizon) return false;
    }

    if ($numberNaim !== null) {
        $idSvodki = (int)$rec['id_svodki'];
        $item = $textMap[$idSvodki] ?? null;
        if (!$item) return false;
        if ((string)$item['number_naim'] !== (string)$numberNaim) {
            return false;
        }
    }

    return true;
});

// ── Группировка по дате и пункту ──────────────────────────────────────────────
$byDate     = [];
$byItem     = [];
$colorIdx   = 0;
$itemColors = [];
$pctTracker = []; // date -> ['n' => neraskr_sum, 'r' => raskr_sum] for svodki % line

foreach ($filtered as $rec) {
    $idSvodki = (int)$rec['id_svodki'];
    $item     = $textMap[$idSvodki] ?? null;
    if (!$item) continue;

    $num  = (string)$item['number_naim'];
    $date = $rec['data_end'] ?? $rec['data_start'];
    $pole = (int)($item['pole'] ?? 1);

    // Значение
    $val = 0;
    if ($mode === 'svodki') {
        $neraskr = (int)($rec['neraskr'] ?? 0);
        $raskr   = (int)($rec['raskr']   ?? 0);
        if ($pole === 2) {
            $val = $neraskr;
            // Track % Раскрываемости: use item '2' when showing all, or any pole=2 when filtered to specific item
            if ($numberNaim !== null || $num === '2') {
                if (!isset($pctTracker[$date])) $pctTracker[$date] = ['n' => 0, 'r' => 0];
                $pctTracker[$date]['n'] += $neraskr;
                $pctTracker[$date]['r'] += $raskr;
            }
        } else {
            $val = (int)($rec['kolichestvo'] ?? 0);
        }
    } else {
        $val = $rec['kolichestvo'] !== null
            ? (float)$rec['kolichestvo']
            : (float)($rec['kolichestvo_fl'] ?? 0);
    }

    if (!isset($byDate[$date])) $byDate[$date] = [];
    if (!isset($byDate[$date][$num])) $byDate[$date][$num] = 0;
    $byDate[$date][$num] += $val;

    if (!isset($byItem[$num])) {
        $byItem[$num] = [
            'number_naim' => $num,
            'naimenov'    => $item['naimenov'],
            'color'       => getColor($colorIdx++, $palette),
            'data'        => [],
        ];
        $itemColors[$num] = $byItem[$num]['color'];
    }
}

ksort($byDate);

// ── Формируем datasets для Chart.js ───────────────────────────────────────────
$labels   = array_keys($byDate);
$datasets = [];

foreach ($byItem as $num => $itemInfo) {
    $values = [];
    foreach ($labels as $date) {
        $values[] = $byDate[$date][$num] ?? 0;
    }
    $datasets[] = [
        'label'           => $num . '. ' . $itemInfo['naimenov'],
        'number_naim'     => $num,
        'data'            => $values,
        'borderColor'     => $itemInfo['color'],
        'backgroundColor' => $itemInfo['color'] . '33',
        'fill'            => false,
        'tension'         => 0.3,
    ];
}

// ── % Раскрываемости (только svodki) ─────────────────────────────────────────
if ($mode === 'svodki' && !empty($pctTracker)) {
    $pctValues = [];
    foreach ($labels as $date) {
        $n = $pctTracker[$date]['n'] ?? 0;
        $r = $pctTracker[$date]['r'] ?? 0;
        $pctValues[] = $n > 0 ? round(($r / $n) * 100, 1) : 0;
    }
    $datasets[] = [
        'label'           => '% Раскрываемости',
        'data'            => $pctValues,
        'borderColor'     => '#E74C3C',
        'backgroundColor' => '#E74C3C33',
        'fill'            => false,
        'tension'         => 0.3,
        'borderDash'      => [6, 3],
    ];
}

// Форматируем метки дат для отображения
$labelsFormatted = array_map(function ($d) {
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt ? $dt->format('d.m.Y') : $d;
}, $labels);

echo json_encode([
    'success'  => true,
    'labels'   => $labelsFormatted,
    'datasets' => $datasets,
]);
