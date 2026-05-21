<?php
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');

$chartMode = $chartMode ?? 'selector';
$title = $chartMode === 'svodki' ? 'ГРАФИК СВОДКИ' : 'ГРАФИК СЕЛЕКТОР';
$backPage = $chartMode === 'svodki' ? 'svodki.php' : 'selector.php';
$apiMode = $chartMode === 'svodki' ? 'svodki' : 'selector';
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
$garnizon = filter_input(INPUT_GET, 'garnizon', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '88';
$startDate = filter_input(INPUT_GET, 'start', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? date('d.m.Y', strtotime('-30 days'));
$endDate = filter_input(INPUT_GET, 'end', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? date('d.m.Y', strtotime('-1 day'));
$numberNaim = filter_input(INPUT_GET, 'number_naim', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$initialGarnisonText = $garnizonNames[$garnizon] ?? 'ГАРНИЗОН';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="../assets/js/daterangepicker.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/compass_state.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.1/daterangepicker.css">
    <style>
        body { background:#f4f4f4; color:#333; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif; }
        .page { max-width:1280px; margin:20px auto; padding:0 16px; }
        .header { text-align:center; color:#4682B4; font-size:36px; font-weight:700; margin-bottom:20px; }
        .toolbar { display:flex; gap:10px; align-items:center; margin-bottom:20px; }
        .toolbar .btn, .toolbar input { height:48px; border-radius:8px; font-weight:600; }
        .toolbar .btn { min-width:180px; background:#4682B4; border-color:#4682B4; }
        .toolbar .btn:hover { background:#fff; color:#4682B4; }
        .toolbar input { flex:1; min-width:260px; text-align:center; border:1px solid #4682B4; }
        .chart-wrap, .selection-wrap { background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.08); }
        .chart-wrap { padding:18px; min-height:520px; }
        .selection-wrap { margin-top:20px; padding:16px; }
        .selection-item { display:flex; align-items:center; gap:8px; margin-bottom:8px; }
        .swatch { width:14px; height:14px; border-radius:50%; display:inline-block; }
    </style>
</head>
<body>
<div class="page">
    <div class="header"><?php echo htmlspecialchars($title); ?></div>
    <div class="toolbar">
        <button class="btn btn-primary" id="back-button">НАЗАД</button>
        <button class="btn btn-primary" id="garnizon-dropdown"><?php echo htmlspecialchars(strtoupper($initialGarnisonText)); ?></button>
        <input type="text" id="date-range" value="<?php echo htmlspecialchars($startDate . ' по ' . $endDate); ?>">
    </div>
    <div class="chart-wrap">
        <canvas id="chart"></canvas>
    </div>
    <div class="selection-wrap" id="selection"></div>
</div>
<script>
const chartMode = <?php echo json_encode($apiMode); ?>;
const numberNaim = <?php echo json_encode($numberNaim); ?>;
let chartInstance = null;

function buildBackUrl() {
    return CompassState.buildURL(<?php echo json_encode($backPage); ?>);
}

function renderSelection(datasets) {
    const $selection = $('#selection');
    if (!datasets.length) {
        $selection.html('<div>Нет данных для выбранного периода.</div>');
        return;
    }
    $selection.html(datasets.map((ds, index) => `
        <label class="selection-item">
            <input type="checkbox" class="dataset-toggle" data-index="${index}" checked>
            <span class="swatch" style="background:${ds.borderColor}"></span>
            <span>${ds.label}</span>
        </label>
    `).join(''));
}

async function loadChart() {
    const picker = $('#date-range').data('daterangepicker');
    const response = await fetch('../api/get_chart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            mode: chartMode,
            garnizon: String(CompassState.get().garnizon || <?php echo json_encode($garnizon); ?>),
            startDate: picker.startDate.format('YYYY-MM-DD'),
            endDate: picker.endDate.format('YYYY-MM-DD'),
            numberNaim: numberNaim || null
        })
    });
    const data = await response.json();
    if (!data.success) {
        $('#selection').html(`<div>${data.error || 'Ошибка загрузки графика.'}</div>`);
        return;
    }

    renderSelection(data.datasets || []);
    const ctx = document.getElementById('chart').getContext('2d');
    if (chartInstance) chartInstance.destroy();
    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels || [],
            datasets: data.datasets || []
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'nearest', intersect: false },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

$(document).on('change', '.dataset-toggle', function() {
    const index = Number($(this).data('index'));
    if (chartInstance && chartInstance.data.datasets[index]) {
        chartInstance.setDatasetVisibility(index, $(this).is(':checked'));
        chartInstance.update();
    }
});

$(function() {
    const state = CompassState.initFromURL();
    const start = moment(state.startDate || <?php echo json_encode($startDate); ?>, 'DD.MM.YYYY', true);
    const end = moment(state.endDate || <?php echo json_encode($endDate); ?>, 'DD.MM.YYYY', true);
    $('#date-range').daterangepicker({
        startDate: start.isValid() ? start : moment().subtract(30, 'days'),
        endDate: end.isValid() ? end : moment().subtract(1, 'days'),
        maxDate: moment().subtract(1, 'days'),
        autoApply: true,
        linkedCalendars: false,
        locale: { format: 'DD.MM.YYYY', separator: ' по ' }
    });

    $('#back-button').on('click', function() {
        window.location.href = buildBackUrl();
    });

    $('#date-range').on('apply.daterangepicker', function(ev, picker) {
        CompassState.set({
            startDate: picker.startDate.format('DD.MM.YYYY'),
            endDate: picker.endDate.format('DD.MM.YYYY')
        });
        loadChart();
    });

    loadChart();
});
</script>
</body>
</html>
