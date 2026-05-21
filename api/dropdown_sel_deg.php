<?php
/**
 * API: Справочник строк таблицы СВОДКИ (суточные)
 * GET без тела → плоский массив [{number_naim, naimenov, pole}]
 * POST с телом → обычная логика output_sel_deg.php
 */
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/storage.php';

// Если GET (fetchAllRows в svodki.php) — отдаём справочник плоским массивом
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $items = loadJson('svodki_select.json');
    $result = array_map(function ($item) {
        return [
            'id'          => (int)$item['id'],
            'number_naim' => (string)$item['number_naim'],
            'naimenov'    => $item['naimenov'] ?? '',
            'pole'        => (int)($item['pole'] ?? 1),
        ];
    }, array_values($items));

    // Сортировка по number_naim
    usort($result, fn($a, $b) => version_compare($a['number_naim'], $b['number_naim']));
    echo json_encode($result);
    exit;
}
