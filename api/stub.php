<?php
// Заглушка: access_control, log_action, check_access
header('Content-Type: application/json; charset=UTF-8');
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';

// Возвращаем полный набор полей, чтобы JS не падал
echo json_encode([
    'success'               => true,
    'access'                => true,
    'all_garrisons_plus_88' => true,
    'can_edit'              => true,
    'action'                => $action,
]);
