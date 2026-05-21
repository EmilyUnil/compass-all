<?php
// Demo data generator — run once to fill 2 months of data (2026-03-21..2026-05-20)
// Then delete or restrict access to this file.

mt_srand(42);

function rnd(int $min, int $max): int {
    if ($min >= $max) return max(0, $min);
    return mt_rand($min, $max);
}
function vary(float $base, float $pct = 0.25): int {
    $d = max(1, (int)round($base * $pct));
    return max(0, rnd((int)round($base) - $d, (int)round($base) + $d));
}

// 8 garrisons, weight = relative city size
$garrisons = [
    ['code'=>'6',  'w'=>5.0],  // Тирасполь
    ['code'=>'5',  'w'=>3.0],  // Бендеры
    ['code'=>'93', 'w'=>2.0],  // ОРОВД
    ['code'=>'31', 'w'=>1.0],  // Слободзея
    ['code'=>'10', 'w'=>1.0],  // Григориополь
    ['code'=>'13', 'w'=>1.0],  // Дубоссары
    ['code'=>'29', 'w'=>1.0],  // Рыбница
    ['code'=>'17', 'w'=>1.0],  // Каменка
];

// Weekly periods
$weeks = [
    ['s'=>'2026-03-21','e'=>'2026-03-27'],
    ['s'=>'2026-03-28','e'=>'2026-04-03'],
    ['s'=>'2026-04-04','e'=>'2026-04-10'],
    ['s'=>'2026-04-11','e'=>'2026-04-17'],
    ['s'=>'2026-04-18','e'=>'2026-04-24'],
    ['s'=>'2026-04-25','e'=>'2026-05-01'],
    ['s'=>'2026-05-02','e'=>'2026-05-08'],
    ['s'=>'2026-05-09','e'=>'2026-05-15'],
    ['s'=>'2026-05-16','e'=>'2026-05-20'],
];

// Selector items: [id_svodki, number_naim, base_value(Тирасполь/week), is_float]
$selItems = [
    [1,  '1',    45, false],
    [2,  '2',    12, false],
    [3,  '2.1',  4,  false],
    [4,  '2.2',  3,  false],
    [5,  '2.3',  1,  false],
    [6,  '2.4',  2,  false],
    [7,  '2.5',  0,  false],
    [8,  '2.6',  5,  false],
    [9,  '2.7',  2,  false],
    [10, '2.8',  3,  false],
    [11, '3',    8,  false],
    [12, '3.1',  3,  false],
    [13, '4',    18, false],
    [14, '5',    65, true ],  // Раскрываемость % — float
    [15, '6',    10, false],
    [16, '7',    0,  false],
    [17, '8',    80, false],
    [18, '9',    30, false],
    [19, '10',   4,  false],
    [20, '10.1', 0,  false],
    [21, '10.2', 3,  false],
    [22, '11',   2,  false],
    [23, '12',   0,  false],
    [24, '13',   1,  false],
];

// Svodki items: [id_svodki, number_naim, pole, base_neraskr(Тирасполь/day), base_raskr]
// pole=1 → stored in kolichestvo; pole=2 → stored in neraskr/raskr
$svItems = [
    [1,  '1',   1, 8,  0],   // Заявлений → kolichestvo
    [2,  '2',   2, 3,  1],   // Зарегистрировано: neraskr=total, raskr=cleared
    [3,  '2.1', 2, 1,  0],   // Тяжкие
    [4,  '2.3', 2, 0,  0],   // Убийства (rare)
    [5,  '2.4', 2, 0,  0],   // Тяжкий вред (rare)
    [6,  '2.5', 2, 0,  0],   // Изнасилования (very rare)
    [7,  '2.6', 2, 1,  0],   // Кражи
    [8,  '2.7', 2, 0,  0],   // Грабежи (rare)
    [9,  '3',   1, 1,  0],   // Раскрыто → kolichestvo
    [10, '4',   1, 4,  0],   // Нераскрытые → kolichestvo
    [11, '5',   1, 2,  0],   // Задержано → kolichestvo
    [12, '6',   1, 0,  0],   // Оружие → kolichestvo
    [13, '7',   1, 15, 0],   // Адм. правонарушения → kolichestvo
];

// ── Generate SELECTOR (weekly) ──────────────────────────────────────────────
$selData = [];
$id = 1;

foreach ($weeks as $week) {
    foreach ($garrisons as $gar) {
        foreach ($selItems as [$idSv, $naim, $base, $isFloat]) {
            $scaled = $base * $gar['w'] / 5.0;

            if ($isFloat) {
                // Раскрываемость % — 58..75 with ±3 variation per garrison
                $center = 58 + ($gar['w'] / 5.0) * 12;  // bigger city = better clearance
                $val_f  = round(mt_rand((int)(($center - 3) * 10), (int)(($center + 3) * 10)) / 10, 1);
                $selData[] = [
                    'id'           => $id++,
                    'id_svodki'    => $idSv,
                    'number_naim'  => $naim,
                    'garnizon'     => $gar['code'],
                    'podrazdel'    => '',
                    'data_start'   => $week['s'],
                    'data_end'     => $week['e'],
                    'kolichestvo'  => null,
                    'kolichestvo_fl' => $val_f,
                    'value_text'   => (string)$val_f,
                    'data_sozdan'  => $week['e'].' 09:00:00',
                ];
            } else {
                $v = vary($scaled);
                // For zero-base rare items, allow small chance at larger garrisons
                if ($base === 0) $v = ($gar['w'] >= 3 && mt_rand(0,4) === 0) ? 1 : 0;
                $selData[] = [
                    'id'           => $id++,
                    'id_svodki'    => $idSv,
                    'number_naim'  => $naim,
                    'garnizon'     => $gar['code'],
                    'podrazdel'    => '',
                    'data_start'   => $week['s'],
                    'data_end'     => $week['e'],
                    'kolichestvo'  => $v,
                    'kolichestvo_fl' => null,
                    'value_text'   => (string)$v,
                    'data_sozdan'  => $week['e'].' 09:00:00',
                ];
            }
        }
    }
}

// ── Generate SVODKI (daily) ──────────────────────────────────────────────────
$svData = [];
$id2   = 1;
$cur   = new DateTime('2026-03-21');
$end   = new DateTime('2026-05-20');

while ($cur <= $end) {
    $ds = $cur->format('Y-m-d');

    foreach ($garrisons as $gar) {
        foreach ($svItems as [$idSv, $naim, $pole, $baseN, $baseR]) {
            $sN = $baseN * $gar['w'] / 5.0;
            $sR = $baseR * $gar['w'] / 5.0;

            if ($pole === 1) {
                $v = vary($sN, 0.5);
                if ($baseN === 0) $v = ($gar['w'] >= 3 && mt_rand(0,6) === 0) ? 1 : 0;
                $svData[] = [
                    'id'          => $id2++,
                    'id_svodki'   => $idSv,
                    'number_naim' => $naim,
                    'garnizon'    => $gar['code'],
                    'data_start'  => $ds,
                    'data_end'    => $ds,
                    'kolichestvo' => $v,
                    'neraskr'     => null,
                    'raskr'       => null,
                    'data_sozdan' => $ds.' 08:30:00',
                ];
            } else {
                $neraskr = vary($sN, 0.5);
                $raskr   = vary($sR, 0.5);
                if ($baseN === 0) { $neraskr = ($gar['w'] >= 3 && mt_rand(0,5) === 0) ? 1 : 0; }
                $raskr = min($raskr, $neraskr);
                $svData[] = [
                    'id'          => $id2++,
                    'id_svodki'   => $idSv,
                    'number_naim' => $naim,
                    'garnizon'    => $gar['code'],
                    'data_start'  => $ds,
                    'data_end'    => $ds,
                    'kolichestvo' => null,
                    'neraskr'     => $neraskr,
                    'raskr'       => $raskr,
                    'data_sozdan' => $ds.' 08:30:00',
                ];
            }
        }
    }
    $cur->modify('+1 day');
}

// ── Write files ──────────────────────────────────────────────────────────────
$dataDir = __DIR__ . '/data';
file_put_contents($dataDir.'/itog_selector.json', json_encode($selData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
file_put_contents($dataDir.'/itog_sel_deg.json',  json_encode($svData,  JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header('Content-Type: text/plain; charset=UTF-8');
echo "Done!\n";
echo "Selector records : ".count($selData)."\n";
echo "Svodki records   : ".count($svData)."\n";
echo "Period           : 2026-03-21 .. 2026-05-20\n";
echo "Garrisons        : 6,5,93,31,10,13,29,17\n";
