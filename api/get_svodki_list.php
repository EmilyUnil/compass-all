<?php
/**
 * API: Справочник строк таблицы SELECTOR (еженедельные)
 * Возвращает {success, data: [...]} — для loadSvodkiList() на главной
 * и плоский массив для fetchAllRows() в selector.php
 */
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/storage.php';

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$format = $input['format'] ?? ($_GET['format'] ?? 'wrapped');  // 'flat' или 'wrapped'

$items = loadJson('text_selector.json');

// Нормализуем
$result = array_map(function ($item) {
    return [
        'id'           => (int)$item['id'],
        'id_svodki'    => (int)$item['id'],   // алиас для совместимости с главной
        'number_naim'  => (string)$item['number_naim'],
        'naimenov'     => $item['naimenov'] ?? '',
        'section'      => (int)($item['section'] ?? 1),
        'checking'     => $item['checking'] ?? '',
        'pole'         => (int)($item['pole'] ?? 1),
    ];
}, array_values($items));

if ($format === 'flat') {
    // selector.php fetchAllRows ожидает прямой массив
    echo json_encode($result);
} else {
    // index.php loadSvodkiList() ожидает {success, data}
    echo json_encode(['success' => true, 'data' => $result]);
}
