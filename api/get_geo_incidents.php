<?php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/storage.php';

$input     = json_decode(file_get_contents('php://input'), true) ?? [];
$garnizon  = $input['garnizon']  ?? '';
$startDate = $input['startDate'] ?? '';
$endDate   = $input['endDate']   ?? '';

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) $startDate = '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate))   $endDate   = '';

$isMvd = ($garnizon === '' || $garnizon === '88');

$all = loadJson('incidents.json');
$result = [];

foreach ($all as $row) {
    if (!isset($row['lat'], $row['lng'])) continue;

    $date = $row['data_proicsh'] ?? '';
    if ($startDate && $date < $startDate) continue;
    if ($endDate   && $date > $endDate)   continue;
    if (!$isMvd && ($row['garnizon'] ?? '') !== $garnizon) continue;

    $result[] = [
        'id'           => (int)($row['id_zapisi']          ?? 0),
        'numb'         => (int)($row['numb_proicsh']       ?? 0),
        'type'         => $row['type_proicsh_name']        ?? '',
        'address'      => $row['address']                  ?? '',
        'date'         => $date,
        'garnizon'     => $row['garnizon']                 ?? '',
        'garnizon_name'=> $row['garnizon_name']            ?? '',
        'is_important' => (bool)($row['is_important']      ?? false),
        'lat'          => (float)$row['lat'],
        'lng'          => (float)$row['lng'],
        'glavn'        => (int)($row['glavn']              ?? 0),
        'text'         => $row['text_proicsh']             ?? '',
    ];
}

echo json_encode(['success' => true, 'incidents' => $result], JSON_UNESCAPED_UNICODE);
