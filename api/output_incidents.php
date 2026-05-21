<?php
/**
 * API: Происшествия (svodki — карточки с текстом)
 * GET  → плоский массив всех происшествий (для fetchAllRows)
 * POST → чтение / запись / удаление записей
 */
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/storage.php';

$garnizonNames = [
    '88' => 'МВД',
    '6' => 'Тирасполь',
    '5' => 'Бендеры',
    '31' => 'Слободзея',
    '10' => 'Григориополь',
    '13' => 'Дубоссары',
    '29' => 'Рыбница',
    '17' => 'Каменка',
    '93' => 'ОРОВД',
];

// ── GET: справочник типов происшествий ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(['success' => true, 'data' => loadJson('svodki_select.json')]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Поддерживаем два формата входа: JSON-тело и form-encoded (jQuery $.ajax)
if (empty($input)) {
    parse_str(file_get_contents('php://input'), $input);
}

$garnizon   = (string)($input['garnizon'] ?? $input['garnizon_id'] ?? '');
$startDate  = $input['start_date'] ?? $input['date'] ?? '';
$endDate    = $input['end_date']   ?? $input['date'] ?? '';
$action     = $input['action'] ?? '';

// ── Нормализация дат ──────────────────────────────────────────────────────────
function normDate(string $d): string {
    if (!$d) return '';
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return $d;
    $dt = DateTime::createFromFormat('d.m.Y', $d);
    return $dt ? $dt->format('Y-m-d') : '';
}

$startSql = normDate($startDate);
$endSql   = normDate($endDate);

$incidents = loadJson('incidents.json');

// ── ПРОСМОТР списка ──────────────────────────────────────────────────────────
if (empty($action) || $action === 'view' || (!empty($startDate) && empty($input['numb_proicsh']))) {

    if (empty($garnizon) || empty($startSql)) {
        echo json_encode(['error' => 'Нет данных для обработки (даты или garnizon).']);
        exit;
    }

    $mvdGarnizons = ['6','5','31','10','13','29','17'];

    $filtered = array_filter($incidents, function ($inc) use ($garnizon, $startSql, $endSql, $mvdGarnizons) {
        $d = $inc['data_proicsh'] ?? '';
        if ($garnizon === '88') {
            return in_array((string)($inc['garnizon'] ?? ''), $mvdGarnizons) && $d >= $startSql && $d <= $endSql;
        }
        return (string)($inc['garnizon'] ?? '') === $garnizon && $d >= $startSql && $d <= $endSql;
    });

    echo json_encode([
        'success'   => true,
        'incidents' => array_values(array_map(function ($incident) use ($garnizonNames) {
            if (empty($incident['garnizon_name'])) {
                $incident['garnizon_name'] = $garnizonNames[(string)($incident['garnizon'] ?? '')] ?? '';
            }
            return $incident;
        }, $filtered)),
    ]);
    exit;
}

// ── СОХРАНЕНИЕ / ОБНОВЛЕНИЕ ───────────────────────────────────────────────────
if ($action === 'save' || !empty($input['id_zapisi']) || (!empty($input['numb_proicsh']) && !empty($input['selected_text']))) {
    $numb     = (int)($input['numb_proicsh'] ?? 0);
    $text     = trim($input['text_proicsh']    ?? '');
    $type     = trim($input['selected_text']   ?? '');
    $idZapisi = $input['id_zapisi']            ?? null;
    $date     = normDate($input['date'] ?? $startDate);
    $garnizon = (string)($input['garnizon'] ?? '');

    if (!$numb || !$date || !$garnizon || (!$idZapisi && !$type)) {
        echo json_encode(['success' => false, 'error' => 'Не заполнены обязательные поля']);
        exit;
    }

    if ($idZapisi) {
        // UPDATE
        $found = false;
        foreach ($incidents as &$inc) {
            if (($inc['id_zapisi'] ?? '') == $idZapisi) {
                $inc['numb_proicsh']    = $numb;
                if ($type !== '') {
                    $inc['type_proicsh_name'] = $type;
                }
                $inc['text_proicsh']    = $text;
                $inc['data_proicsh']    = $date;
                $inc['garnizon']        = $garnizon;
                $inc['garnizon_name']   = $garnizonNames[$garnizon] ?? '';
                $found = true;
                break;
            }
        }
        unset($inc);
        if (!$found) {
            echo json_encode(['success' => false, 'error' => 'Запись не найдена']);
            exit;
        }
        $msg = 'Карточка обновлена';
    } else {
        // INSERT
        $newId = nextId(array_map(fn($i) => ['id' => $i['id_zapisi'] ?? 0], $incidents));
        $incidents[] = [
            'id_zapisi'        => $newId,
            'numb_proicsh'     => $numb,
            'type_proicsh_name'=> $type,
            'text_proicsh'     => $text,
            'data_proicsh'     => $date,
            'garnizon'         => $garnizon,
            'garnizon_name'    => $garnizonNames[$garnizon] ?? '',
            'glavn'            => 0,
            'is_editable'      => true,
        ];
        $msg = 'Карточка создана';
    }

    saveJson('incidents.json', array_values($incidents));
    echo json_encode(['success' => true, 'message' => $msg]);
    exit;
}

// ── ОБНОВЛЕНИЕ GLAVN (главная/не главная) ─────────────────────────────────────
if (isset($input['numb']) && isset($input['glavn'])) {
    foreach ($incidents as &$inc) {
        if (($inc['numb_proicsh'] ?? 0) == (int)$input['numb']
            && (string)($inc['garnizon'] ?? '') === $garnizon) {
            $inc['glavn'] = (int)$input['glavn'];
            break;
        }
    }
    unset($inc);
    saveJson('incidents.json', array_values($incidents));
    echo json_encode(['success' => true]);
    exit;
}

// ── УДАЛЕНИЕ ─────────────────────────────────────────────────────────────────
if ($action === 'delete' && isset($input['id_zapisi'])) {
    $incidents = array_values(array_filter($incidents, fn($i) => ($i['id_zapisi'] ?? '') != $input['id_zapisi']));
    saveJson('incidents.json', $incidents);
    echo json_encode(['success' => true, 'message' => 'Карточка удалена']);
    exit;
}

// ── Fallback ──────────────────────────────────────────────────────────────────
// Если дошли сюда — проверка существования карточки (numb_proicsh без text)
if (!empty($input['numb_proicsh'])) {
    $numb     = (int)$input['numb_proicsh'];
    $dateCheck= normDate($input['date'] ?? '');
    $gar      = (string)($input['garnizon'] ?? '');
    $existing = array_filter($incidents, fn($i) =>
        ($i['numb_proicsh'] ?? 0) == $numb
        && ($i['data_proicsh'] ?? '') === $dateCheck
        && (string)($i['garnizon'] ?? '') === $gar
    );
    echo json_encode(['success' => true, 'exists' => !empty($existing), 'incidents' => array_values($existing)]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Неизвестное действие']);
