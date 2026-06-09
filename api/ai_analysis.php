<?php
header('Content-Type: application/json; charset=UTF-8');
ini_set('default_charset', 'UTF-8');
require_once __DIR__ . '/storage.php';

// --- API key ---
$configFile = __DIR__ . '/../config/ai_config.php';
if (file_exists($configFile)) require_once $configFile;
$groqKey   = defined('GROQ_API_KEY')   ? GROQ_API_KEY   : '';
$geminiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
if (!empty($groqKey) && $groqKey !== 'ВСТАВЬТЕ_СЮДА_КЛЮЧ_GROQ') {
    $provider = 'groq'; $apiKey = $groqKey;
} elseif (!empty($geminiKey) && $geminiKey !== 'ВСТАВЬТЕ_СЮДА_ВАШ_КЛЮЧ') {
    $provider = 'gemini'; $apiKey = $geminiKey;
} else {
    echo json_encode(['success' => false, 'error' => 'API ключ не настроен. Добавьте ключ Groq (console.groq.com) в config/ai_config.php.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Input ---
$input     = json_decode(file_get_contents('php://input'), true) ?? [];
$garnizon  = $input['garnizon']   ?? '';
$startDate = $input['start_date'] ?? date('Y-m-01');
$endDate   = $input['end_date']   ?? date('Y-m-d');
$source    = $input['source']     ?? 'svodki'; // 'selector' | 'svodki'

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) $startDate = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate))   $endDate   = date('Y-m-d');

$garnizonMap = [
    '88' => 'МВД (все гарнизоны)', '6' => 'Тирасполь', '5' => 'Бендеры',
    '31' => 'Слободзея', '10' => 'Григориополь', '13' => 'Дубоссары',
    '29' => 'Рыбница', '17' => 'Каменка',
];
$isMvd       = ($garnizon === '' || $garnizon === '88');
$garnizonName = $isMvd ? 'МВД (все гарнизоны)' : ($garnizonMap[$garnizon] ?? 'Гарнизон');
$startFmt    = date('d.m.Y', strtotime($startDate));
$endFmt      = date('d.m.Y', strtotime($endDate));

// --- Field names per source ---
$selectorFields = [
    '1'    => 'Поступило заявлений и сообщений',
    '2'    => 'Зарегистрировано преступлений',
    '2.1'  => '  тяжкие и особо тяжкие',
    '2.2'  => '  преступления против личности',
    '2.3'  => '  убийства (покушения)',
    '2.4'  => '  причинение тяжкого вреда здоровью',
    '2.5'  => '  изнасилования (покушения)',
    '2.6'  => '  кражи',
    '2.7'  => '  грабежи (разбои)',
    '2.8'  => '  мошенничество',
    '3'    => 'Раскрыто преступлений (всего)',
    '3.1'  => '  раскрыто тяжких и особо тяжких',
    '4'    => 'Нераскрытые преступления (остаток)',
    '5'    => 'Раскрываемость %',
    '6'    => 'Задержано подозреваемых',
    '7'    => 'Изъято оружия (единиц)',
    '8'    => 'Административные правонарушения',
    '9'    => 'Нарушения общественного порядка',
    '10'   => 'ДТП',
    '10.1' => '  погибло в ДТП',
    '10.2' => '  ранено в ДТП',
    '11'   => 'Пожары',
    '12'   => 'Происшествия на водных объектах',
    '13'   => 'Суицидальные проявления',
];
$svodkiFields = [
    '1'   => 'Поступило заявлений',
    '2'   => 'Зарегистрировано преступлений',
    '2.1' => '  тяжкие и особо тяжкие',
    '2.3' => '  убийства',
    '2.4' => '  тяжкий вред здоровью',
    '2.5' => '  изнасилования',
    '2.6' => '  кражи',
    '2.7' => '  грабежи',
    '3'   => 'Раскрыто преступлений',
    '4'   => 'Нераскрытые преступления',
    '5'   => 'Задержано лиц',
];
$fieldNames = $source === 'selector' ? $selectorFields : $svodkiFields;
$dataFile   = $source === 'selector' ? 'itog_selector.json' : 'itog_sel_deg.json';
$sourceLabel = $source === 'selector' ? 'ЕЖЕНЕДЕЛЬНЫЕ ОТЧЁТЫ (Селектор)' : 'СУТОЧНЫЕ СВОДКИ';

// --- Load data ---
$tableData = loadJson($dataFile);
$incidents = loadJson('incidents.json');

// --- Helpers ---

// Extract numeric value from a row depending on source
function rowVal(array $row, string $src): ?float {
    if ($src === 'selector') {
        return isset($row['kolichestvo']) && $row['kolichestvo'] !== null
            ? (float)$row['kolichestvo'] : null;
    }
    // svodki: rows with kolichestvo use it; crime count rows (neraskr/raskr) use neraskr only
    // (table displays neraskr as "Зарегистрировано", matching output_sel_deg.php buildRow logic)
    if (isset($row['kolichestvo']) && $row['kolichestvo'] !== null) return (float)$row['kolichestvo'];
    $n = isset($row['neraskr']) && $row['neraskr'] !== null ? (float)$row['neraskr'] : null;
    if ($n !== null) return $n;
    return null;
}

// Aggregate table rows for given garrison/period
function aggregateRows(array $rows, string $src, string $g, bool $mvd, string $s, string $e): array {
    $agg = []; $trend = [];
    foreach ($rows as $row) {
        $date    = $row['data_start'] ?? '';
        $dateEnd = $row['data_end']   ?? $date;
        // Overlap check: report period [data_start, data_end] intersects query [s, e]
        if ($dateEnd < $s || $date > $e) continue;
        // Garnizon 93 is a special subdivision not shown in svodki table
        if ($src === 'svodki' && ($row['garnizon'] ?? '') === '93') continue;
        if (!$mvd && ($row['garnizon'] ?? '') !== $g) continue;
        $nn  = $row['number_naim'] ?? '';
        $val = rowVal($row, $src);
        if ($val !== null) {
            $agg[$nn] = ($agg[$nn] ?? 0) + $val;
            // Trend: group by month for preступления (nn=2)
            if ($nn === '2') {
                $month = substr($date, 0, 7);
                $trend[$month] = ($trend[$month] ?? 0) + $val;
            }
        }
    }
    ksort($trend);
    // Compute real clearance rate (overrides summed %)
    if ($src === 'selector' && !empty($agg['2']) && $agg['2'] > 0) {
        $agg['5'] = round(($agg['3'] ?? 0) / $agg['2'] * 100, 1);
    }
    return ['agg' => $agg, 'trend' => $trend];
}

// Format aggregated stats as text block
function statsText(array $agg, array $fields, string $src): string {
    $out = '';
    foreach ($fields as $nn => $name) {
        if (!array_key_exists($nn, $agg)) continue;
        $val = ($nn === '5') ? $agg[$nn] . '%' : (int)$agg[$nn];
        $out .= "  {$name}: {$val}\n";
    }
    if (empty($out)) return "  Нет данных за период.\n";
    // Validate: заявлений (nn=1) must be >= зарегистрировано (nn=2)
    if (isset($agg['1'], $agg['2']) && $agg['1'] < $agg['2']) {
        $out .= "  [!] Поступило заявлений меньше зарегистрированных — возможна ошибка учёта\n";
    }
    return $out;
}

// Format trend as text
function trendText(array $trend): string {
    if (empty($trend)) return "  Нет данных.\n";
    $out = '';
    foreach ($trend as $month => $cnt) $out .= "  {$month}: {$cnt} зарег. преступлений\n";
    return $out;
}

// Incident cards summary (from incidents.json)
function incidentsBlock(array $incidents, string $g, bool $mvd, string $s, string $e): string {
    $byType = [];
    foreach ($incidents as $row) {
        $date = $row['data_proicsh'] ?? '';
        if ($date < $s || $date > $e) continue;
        if (!$mvd && ($row['garnizon'] ?? '') !== $g) continue;
        $t = $row['type_proicsh_name'] ?? 'Прочее';
        $byType[$t] = ($byType[$t] ?? 0) + 1;
    }
    if (empty($byType)) return '';
    arsort($byType);
    $total = array_sum($byType);
    $out = "Конкретные происшествия (фабулы), всего {$total}:\n";
    foreach ($byType as $t => $c) $out .= "  - {$t}: {$c}\n";
    return $out;
}

// Common prompt footer format
$sectionFmt = <<<FMT

Ответ строго в следующем формате (заголовки в квадратных скобках — точно как написано):

[АНАЛИЗ ОБСТАНОВКИ]
2-3 предложения: тенденции, раскрываемость, динамика.

[ОСНОВНЫЕ УГРОЗЫ]
- Угроза 1 (цифры из данных)
- Угроза 2
- Угроза 3

[РЕКОМЕНДАЦИИ]
- Мера 1
- Мера 2
- Мера 3
- Мера 4

[ПРОГНОЗ НА СЛЕДУЮЩИЙ ПЕРИОД]
На основе динамики — прогноз на следующий период:
- Показатель: X (тренд: рост / снижение / стабильно)
Итого зарегистрированных преступлений ожидается: X.
FMT;

// Prompt header helper
function promptHeader(string $sourceLabel, string $garnizonName, string $startFmt, string $endFmt): string {
    return "Ты аналитик правоохранительных органов ПМР.\n"
        . "Источник данных: {$sourceLabel}\n"
        . "Регион: {$garnizonName}\n"
        . "Период: {$startFmt} — {$endFmt}\n"
        . "ПРАВИЛО ДАННЫХ: Поступило заявлений (п.1) всегда должно быть >= Зарегистрировано преступлений (п.2).\n"
        . "Если это нарушено — это признак ошибки учёта, укажи это в анализе.\n\n";
}

// =============================================================
if ($isMvd) {
    // ---- МВД: brief (общая республика) + detailed (по гарнизонам) ----

    $overall   = aggregateRows($tableData, $source, '', true, $startDate, $endDate);
    $incAll    = ($source === 'selector') ? '' : incidentsBlock($incidents, '', true, $startDate, $endDate);

    $briefPrompt = promptHeader($sourceLabel, 'Республика ПМР (все гарнизоны)', $startFmt, $endFmt)
        . "СВОДНАЯ СТАТИСТИКА ПО РЕСПУБЛИКЕ:\n"
        . statsText($overall['agg'], $fieldNames, $source)
        . "\nДИНАМИКА ПО МЕСЯЦАМ:\n"
        . trendText($overall['trend'])
        . ($incAll ? "\n" . $incAll : '')
        . $sectionFmt;

    // Detailed: per garrison
    $garrisonBlock = '';
    foreach ($garnizonMap as $gId => $gName) {
        if ($gId === '88') continue;
        $gData = aggregateRows($tableData, $source, $gId, false, $startDate, $endDate);
        if (empty($gData['agg'])) continue;
        $gInc  = ($source === 'selector') ? '' : incidentsBlock($incidents, $gId, false, $startDate, $endDate);
        $garrisonBlock .= "\n=== {$gName} ===\n"
            . statsText($gData['agg'], $fieldNames, $source)
            . ($gInc ? $gInc : '');
    }

    $detailPrompt = "Ты аналитик правоохранительных органов ПМР. "
        . "Дай отдельный подробный анализ по каждому гарнизону.\n"
        . "Источник: {$sourceLabel}. Период: {$startFmt} — {$endFmt}\n\n"
        . "ДАННЫЕ ПО ГАРНИЗОНАМ:{$garrisonBlock}\n\n"
        . "Для каждого гарнизона с данными — строго в формате:\n\n"
        . "[ГАРНИЗОН: Название]\n"
        . "Ситуация: 1-2 предложения (укажи раскрываемость если есть).\n"
        . "Ключевые угрозы: конкретные виды с цифрами.\n"
        . "Рекомендации: 2-3 меры.\n"
        . "Прогноз: ожидаемые показатели с трендом.\n";

    echo json_encode([
        'success'         => true,
        'mode'            => 'mvd',
        'source'          => $source,
        'provider'        => $provider,
        'api_key'         => $apiKey,
        'prompt_brief'    => $briefPrompt,
        'prompt_detailed' => $detailPrompt,
        'stats'           => [
            'garnizon' => $garnizonName,
            'period'   => "{$startFmt} — {$endFmt}",
            'total'    => (int)($overall['agg']['2'] ?? 0),
        ],
    ], JSON_UNESCAPED_UNICODE);

} else {
    // ---- Конкретный гарнизон ----

    $data    = aggregateRows($tableData, $source, $garnizon, false, $startDate, $endDate);
    $incText = ($source === 'selector') ? '' : incidentsBlock($incidents, $garnizon, false, $startDate, $endDate);

    $prompt = promptHeader($sourceLabel, $garnizonName, $startFmt, $endFmt)
        . "СТАТИСТИКА:\n"
        . statsText($data['agg'], $fieldNames, $source)
        . "\nДИНАМИКА ПО МЕСЯЦАМ:\n"
        . trendText($data['trend'])
        . ($incText ? "\n" . $incText : '')
        . $sectionFmt;

    echo json_encode([
        'success'  => true,
        'mode'     => 'garrison',
        'source'   => $source,
        'provider' => $provider,
        'api_key'  => $apiKey,
        'prompt'   => $prompt,
        'stats'    => [
            'garnizon' => $garnizonName,
            'period'   => "{$startFmt} — {$endFmt}",
            'total'    => (int)($data['agg']['2'] ?? 0),
        ],
    ], JSON_UNESCAPED_UNICODE);
}
