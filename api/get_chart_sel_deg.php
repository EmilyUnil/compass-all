<?php
$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$payload['mode'] = 'svodki';
file_put_contents('php://temp', '');
$_SERVER['REQUEST_METHOD'] = 'POST';
$GLOBALS['__chart_payload_override'] = $payload;
require __DIR__ . '/get_chart_sel_deg_proxy.php';
__halt_compiler();
$input = json_decode(file_get_contents('php://input'), true);
$startDate = $input['startDate'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $input['endDate'] ?? date('Y-m-d', strtotime('-1 day'));
$garnizon = $input['garnizon'] ?? $_SESSION['user_garnizon'];
$numberNaim = $input['numberNaim'] ?? null;

$accessLevel = (string) ($_SESSION['dostup'][156] ?? '0');
$userOrgan = (string)$_SESSION['user_organ'];
$userGarnizon = (string)$_SESSION['user_garnizon'];

if (!DateTime::createFromFormat('Y-m-d', $startDate) || !DateTime::createFromFormat('Y-m-d', $endDate)) {
    echo json_encode(['success' => false, 'error' => 'Неверный формат дат']);
    exit;
}

function simpleHash($str) {
    $hash = 0;
    for ($i = 0; $i < strlen($str); $i++) {
        $hash = ($hash * 31 + ord($str[$i])) & 0x7FFFFFFF;
    }
    return $hash;
}

try {
    $conn->set_charset("utf8");

    $colorQuery = "SELECT id, numb FROM chart_color WHERE id BETWEEN 1 AND 70";
    $colorResult = $conn->query($colorQuery);
    $colors = [];
    while ($row = $colorResult->fetch_assoc()) {
        $colors[$row['id']] = $row['numb'];
    }
    $colorResult->free();

    $query = "";
    $params = [];
    $types = "";

    if (($accessLevel == '2' || $accessLevel == '4') && $garnizon == '88') {
        // МВД — сумма по всем гарнизонам
        $query = "SELECT 
                    i.data_end AS date, 
                    i.id_svodki,
                    SUM(i.raskr) AS raskr,
                    SUM(i.neraskr) AS neraskr,
                    SUM(i.kolichestvo) AS kolichestvo,
                    MAX(s.naimenov) AS naimenov,
                    MAX(s.number_naim) AS number_naim,
                    MAX(s.pole) AS pole
                  FROM itog_sel_deg i
                  LEFT JOIN svodki_select s ON i.id_svodki = s.id
                  WHERE i.data_end BETWEEN ? AND ?";

        $params = [$startDate, $endDate];
        $types = "ss";

        if ($numberNaim) {
            $query .= " AND s.number_naim = ?";
            $params[] = $numberNaim;
            $types .= "s";
        }

        $query .= " GROUP BY i.data_end, i.id_svodki";
    } else {
        // Обычный гарнизон
        $query = "SELECT 
                    i.data_end AS date, 
                    i.id_svodki,
                    i.garnizon,
                    i.raskr,
                    i.neraskr,
                    i.kolichestvo,
                    s.naimenov, 
                    s.number_naim, 
                    s.pole
                  FROM itog_sel_deg i
                  LEFT JOIN svodki_select s ON i.id_svodki = s.id
                  WHERE i.data_end BETWEEN ? AND ? AND i.garnizon = ?";

        $params = [$startDate, $endDate, $garnizon];
        $types = "sss";

        if ($accessLevel < '1') {
            $query .= " AND i.podrazdel = ?";
            $params[] = $userOrgan;
            $types .= "s";
        }

        if ($numberNaim) {
            $query .= " AND s.number_naim = ?";
            $params[] = $numberNaim;
            $types .= "s";
        }
    }

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error . " | Query: $query");
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $number_naim = $row['number_naim'] ?? '';
        $raskr_color_id = (simpleHash($number_naim . 'raskr') % 70) + 1;
        $neraskr_color_id = (simpleHash($number_naim . 'neraskr') % 70) + 1;
        $kolichestvo_color_id = (simpleHash($number_naim . 'kolichestvo') % 70) + 1;

        $data[] = [
            'date' => $row['date'],
            'id_svodki' => $row['id_svodki'],
            'garnizon' => $row['garnizon'] ?? null,
            'raskr' => $row['pole'] == 1 ? null : (int)($row['raskr'] ?? 0),
            'neraskr' => $row['pole'] == 1 ? null : (int)($row['neraskr'] ?? 0),
            'kolichestvo' => $row['pole'] == 1 ? (int)($row['kolichestvo'] ?? 0) : null,
            'naimenov' => $row['naimenov'] ?? 'Неизвестно',
            'number_naim' => $number_naim,
            'pole' => $row['pole'] ?? null,
            'raskr_color' => $colors[$raskr_color_id] ?? '#000000',
            'neraskr_color' => $colors[$neraskr_color_id] ?? '#666666',
            'kolichestvo_color' => $colors[$kolichestvo_color_id] ?? '#999999'
        ];
    }

    $stmt->close();
    $conn->close();

    // ОТЛАДКА — смотрите в error_log сервера
    error_log("=== CHART REQUEST ===");
    error_log("garnizon: $garnizon | period: $startDate to $endDate | numberNaim: " . ($numberNaim ?? 'null'));
    error_log("Query: $query");
    error_log("Returned rows: " . count($data));
    error_log("Sample row: " . json_encode($data[0] ?? 'NO DATA'));

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);

} catch (Exception $e) {
    error_log("CHART ERROR: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
?>
