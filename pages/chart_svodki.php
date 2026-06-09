<?php
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');

$garnizon   = filter_input(INPUT_GET, 'garnizon', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '6';
$numberNaim = filter_input(INPUT_GET, 'number_naim', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null;
$startDate  = filter_input(INPUT_GET, 'start', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? date('Y-m-d', strtotime('-30 days'));
$endDate    = filter_input(INPUT_GET, 'end', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? date('Y-m-d', strtotime('-1 day'));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>График Сводки</title>
    <script src="../assets/js/chart_line.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.1/daterangepicker.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.1/daterangepicker.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        body {
            background-color: #f4f4f4;
            color: #333;
            padding-top: 20px;
            display: flex;
            flex-direction: column;
            position: relative;
            justify-content: flex-start;
            min-height: 100vh;
        }
        .header {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
            position: relative;
            height: 60px;
        }
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            position: relative;
        }
        h1 {
            margin: 0;
            text-align: center;
            color: #4682B4;
            font-size: 36px;
            font-weight: bold;
            line-height: 1;
        }
        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            padding: 0 15px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .date-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            transition: transform 0.3s ease;
            width: 100%;
            max-width: 1200px;
            margin-bottom: 0px;
        }
        .date-selector .centered-input {
            flex: 1;
            max-width: 35%;
            min-width: 300px;
            text-align: center;
        }
        .date-selector #back-button, .date-selector button, .date-selector .dropdown button {
            width: 180px;
            height: 50px;
            background-color: #4682B4;
            color: #fff;
            border: 1px solid #4682B4;
            font-size: 18px;
            font-weight: 600;
            padding: 10px;
            text-overflow: ellipsis;
            transition: background-color 0.3s ease, border-color 0.3s ease;
            vertical-align: middle;
            border-radius: 8px;
            box-sizing: border-box;
        }
        .dropdown-menu.show {
            width: 180px;
        }
        .date-selector a:hover, .date-selector button:hover, .date-selector .dropdown button:hover {
            background-color: #fff;
            color: #4682B4;
            border-color: #4682B4;
        }
        .container .date-selector input {
            padding: 10px;
            border: 1px solid #4682B4;
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            height: 50px;
            cursor: pointer;
            border-radius: 8px;
            text-overflow: ellipsis;
            line-height: 1.5;
            vertical-align: middle;
        }
        .content {
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 1180px;
            align-items: center;
        }
        .chart-container {
            position: relative;
            width: 100%;
            height: 600px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .chart-container + .chart-container {
            margin-top: 40px;
        }
        .chart-title {
            text-align: center;
            font-size: 24px;
            margin-bottom: 15px;
            color: #4682B4;
        }
        .selection-container {
            width: 100%;
            max-height: 310px;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 10px;
            margin-top: 25px;
            margin-bottom: 25px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .selection-item {
            margin-bottom: 12px;
            padding: 8px;
            background-color: #f9f9f9;
            border-radius: 6px;
        }
        .selection-item strong {
            display: block;
            margin-bottom: 6px;
        }
        .sub-checkboxes {
            margin-left: 20px;
        }
        .selection-item-sub {
            display: flex;
            align-items: center;
            margin-bottom: 4px;
        }
        .selection-color {
            width: 15px;
            height: 15px;
            margin-right: 8px;
            display: inline-block;
            border: 1px solid #aaa;
        }
        .selection-checkbox {
            margin-right: 8px;
        }
        .avg-text {
            margin-left: 8px;
            font-size: 0.9em;
            color: #555;
        }
        #garnizon-dropdown {
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>ГРАФИК СВОДКИ</h1>
        </div>
    </div>
    <div class="container">
        <div class="date-selector">
            <a href="<?php echo htmlspecialchars('selector_degur.php' . ($garnizon || $startDate || $endDate ? '?' : '') . ($garnizon ? 'garnizon=' . urlencode($garnizon) : '') . ($startDate ? ($garnizon ? '&' : '') . 'start=' . urlencode($startDate) : '') . ($endDate ? ($garnizon || $startDate ? '&' : '') . 'end=' . urlencode($endDate) : '')); ?>" class="btn btn-primary btn-back" id="back-button">НАЗАД</a>
            <div class="dropdown">
                <button class="btn btn-primary "
                        type="button"
                        id="garnizon-dropdown"
                        >
                    <?php echo htmlspecialchars($initialGarnisonText); ?>
                </button>
                
                    <ul class="dropdown-menu">
                        
                            <li><a class="dropdown-item" href="javascript:void(0);" data-index="88">МВД</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" data-index="6">Тирасполь</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" data-index="5">Бендеры</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" data-index="31">Слободзея</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" data-index="10">Григориополь</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" data-index="13">Дубоссары</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" data-index="29">Рыбница</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" data-index="17">Каменка</a></li>
                        
                    </ul>
                
            </div>
            <input type="text" id="date-range" class="form-control centered-input" value="<?php echo htmlspecialchars($startDate . ' по ' . $endDate); ?>">
            <button id="ai-button" class="btn btn-primary">ИИ</button>
            <button id="geo-button" class="btn btn-primary">ГЕО</button>
        </div>
        <div class="content">
            <div class="chart-container">
                <div class="chart-title">Общий график</div>
                <canvas id="myChart"></canvas>
            </div>
            <div id="selection" class="selection-container"></div>
            <div class="chart-container" id="sub-chart-container" style="display: none;">
                <div class="chart-title">Разбивка по районам (только для МВД)</div>
                <canvas id="myChartSub"></canvas>
            </div>
        </div>
    </div>

<script>
let chartMain = null;
let chartSub = null;
let selectedGarnisonIndex = <?php echo json_encode($initialGarnisonIndex); ?>;
let selectedItems = {};
let allItems = {};
let numberNaimFromUrl = <?php echo json_encode($numberNaim ?? null); ?>;
let hasAutoSelected = false;

const subGarnizons = ['6', '5', '31', '10', '13', '29', '17'];
const subNames = {
    '6': 'ТИРАСПОЛЬ',
    '5': 'БЕНДЕРЫ',
    '31': 'СЛОБОДЗЕЯ',
    '10': 'ГРИГОРИОПОЛЬ',
    '13': 'ДУБОССАРЫ',
    '29': 'РЫБНИЦА',
    '17': 'КАМЕНКА'
};
const subColors = {
    '6': '#1f77b4',
    '5': '#ff7f0e',
    '31': '#2ca02c',
    '10': '#d62728',
    '13': '#9467bd',
    '29': '#8c564b',
    '17': '#e377c2'
};

function simpleHash(str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        hash = (hash * 31 + str.charCodeAt(i)) & 0x7FFFFFFF;
    }
    return hash;
}

function logAction(action) {
    $.ajax({
        url: '../api/stub.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ action: action }),
        error: function() { console.warn('Ошибка логирования'); }
    });
}

async function loadAllIndicators() {
    try {
        const response = await $.ajax({ url: '../api/output_sel_deg.php' });
        if (!Array.isArray(response)) throw new Error('Неверный формат');
        return response;
    } catch (err) {
        console.error(err);
        return [];
    }
}

async function loadData(garn, naim = null) {
    const picker = $('#date-range').data('daterangepicker');
    if (!picker) throw new Error('Daterangepicker не готов');
    const startDate = picker.startDate.format('YYYY-MM-DD');
    const endDate = picker.endDate.format('YYYY-MM-DD');

    const response = await $.ajax({
        url: '../api/get_chart.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            startDate: startDate,
            endDate: endDate,
            garnizon: garn,
            numberNaim: naim
        })
    });

    if (!response.success) throw new Error(response.error || 'Ошибка сервера');
    return response.data || [];
}

function generateAllDates(startDate, endDate) {
    const dates = [];
    let current = moment(startDate);
    const end = moment(endDate);
    while (current.isSameOrBefore(end)) {
        dates.push(current.format('DD.MM.YYYY'));
        current.add(1, 'days');
    }
    return dates;
}

function calculateAverage(data, allDates, number_naim, field) {
    let sum = 0;
    allDates.forEach(date => {
        const rowDate = moment(date, 'DD.MM.YYYY').format('YYYY-MM-DD');
        const row = data.find(r => r.date === rowDate && r.number_naim === number_naim);
        sum += (row && row[field] !== null) ? row[field] : 0;
    });
    return allDates.length > 0 ? (sum / allDates.length).toFixed(2) : '0.00';
}

function createDataset(data, allDates, number_naim, field, label, color, borderDash = []) {
    const points = allDates.map(date => {
        const rowDate = moment(date, 'DD.MM.YYYY').format('YYYY-MM-DD');
        const row = data.find(r => r.date === rowDate && r.number_naim === number_naim);
        const y = (row && row[field] !== null) ? row[field] : 0;
        return { x: date, y: y };
    });

    return {
        label: label,
        data: points,
        borderColor: color,
        backgroundColor: 'transparent',
        fill: false,
        tension: 0.4,
        borderDash: borderDash,
        pointRadius: 5,
        pointHoverRadius: 7
    };
}

async function updateChart() {
    try {
        const picker = $('#date-range').data('daterangepicker');
        let startDateStr = picker.startDate.format('YYYY-MM-DD');
        let endDateStr = picker.endDate.format('YYYY-MM-DD');
        const allDates = generateAllDates(startDateStr, endDateStr);

        const isMVD = selectedGarnisonIndex === '88';
        const hasSelected = Object.keys(selectedItems).length > 0;
        $('#sub-chart-container').toggle(isMVD && hasSelected);

        const mainData = isMVD ? await loadData('88') : await loadData(selectedGarnisonIndex);

        let datasetsMain = [];

        Object.keys(selectedItems).forEach(naim => {
            const sel = selectedItems[naim];
            const item = allItems[naim];
            if (!sel || !item) return;

            const baseLabel = item.naimenov.toUpperCase();

            if (item.pole == 1 && sel.kolichestvo) {
                datasetsMain.push(createDataset(mainData, allDates, naim, 'kolichestvo', baseLabel, item.colors.kolichestvo));
            } else if (item.pole == 2) {
                if (sel.neraskr) datasetsMain.push(createDataset(mainData, allDates, naim, 'neraskr', baseLabel + ' (нераскрытые)', item.colors.neraskr));
                if (sel.raskr) datasetsMain.push(createDataset(mainData, allDates, naim, 'raskr', baseLabel + ' (раскрытые)', item.colors.raskr, [5, 5]));
            }
        });

        if (chartMain) chartMain.destroy();
        const ctxMain = document.getElementById('myChart').getContext('2d');
        chartMain = new Chart(ctxMain, {
            type: 'line',
            data: { labels: allDates, datasets: datasetsMain },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { title: { display: true, text: 'Дата' } },
                    y: { title: { display: true, text: 'Количество' }, beginAtZero: true, ticks: { stepSize: 1, precision: 0 } }
                },
                plugins: { legend: { display: true } }
            }
        });

        if (isMVD && hasSelected) {
            const subPromises = subGarnizons.map(async g => ({
                g,
                name: subNames[g],
                color: subColors[g],
                data: await loadData(g)
            }));

            const subResults = await Promise.all(subPromises);

            let datasetsSub = [];

            Object.keys(selectedItems).forEach(naim => {
                const sel = selectedItems[naim];
                const item = allItems[naim];
                if (!sel || !item) return;

                const baseLabel = item.naimenov.toUpperCase();

                subResults.forEach(({name, color, data: subData}) => {
                    if (item.pole == 1 && sel.kolichestvo) {
                        datasetsSub.push(createDataset(subData, allDates, naim, 'kolichestvo', name + ' — ' + baseLabel, color));
                    } else if (item.pole == 2) {
                        if (sel.neraskr) datasetsSub.push(createDataset(subData, allDates, naim, 'neraskr', name + ' — ' + baseLabel + ' (нераскр.)', color));
                        if (sel.raskr) datasetsSub.push(createDataset(subData, allDates, naim, 'raskr', name + ' — ' + baseLabel + ' (раскр.)', color, [5, 5]));
                    }
                });
            });

            if (chartSub) chartSub.destroy();
            const ctxSub = document.getElementById('myChartSub').getContext('2d');
            chartSub = new Chart(ctxSub, {
                type: 'line',
                data: { labels: allDates, datasets: datasetsSub },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { title: { display: true, text: 'Дата' } },
                        y: { title: { display: true, text: 'Количество' }, beginAtZero: true, ticks: { stepSize: 1, precision: 0 } }
                    },
                    plugins: { legend: { display: true } }
                }
            });
        }

    } catch (error) {
        console.error('Ошибка updateChart:', error);
        initializeEmptyCharts();
    }
}

async function updateSelectionMenu() {
    try {
        const allIndicators = await loadAllIndicators();
        const fullData = await loadData(selectedGarnisonIndex);
        const picker = $('#date-range').data('daterangepicker');
        const allDates = generateAllDates(picker.startDate.format('YYYY-MM-DD'), picker.endDate.format('YYYY-MM-DD'));

        allItems = {};
        allIndicators.forEach(ind => {
            allItems[ind.number_naim] = {
                naimenov: ind.naimenov || 'Неизвестно',
                pole: ind.pole,
                colors: {
                    kolichestvo: '#0066ff',
                    neraskr: '#ff4444',
                    raskr: '#44aa44'
                }
            };
        });

        fullData.forEach(row => {
            if (allItems[row.number_naim]) {
                allItems[row.number_naim].colors.kolichestvo = row.kolichestvo_color || allItems[row.number_naim].colors.kolichestvo;
                allItems[row.number_naim].colors.neraskr = row.neraskr_color || allItems[row.number_naim].colors.neraskr;
                allItems[row.number_naim].colors.raskr = row.raskr_color || allItems[row.number_naim].colors.raskr;
            }
        });

        Object.keys(allItems).forEach(naim => {
            const item = allItems[naim];
            if (item.pole == 2) {
                const id1 = simpleHash(naim + 'neraskr') % 70 + 1;
                const id2 = simpleHash(naim + 'raskr') % 70 + 1;
                item.colors.neraskr = item.colors.neraskr || `hsl(${id1 * 5}, 80%, 50%)`;
                item.colors.raskr = item.colors.raskr || `hsl(${id2 * 5}, 80%, 50%)`;
            }
        });

        const sortedKeys = Object.keys(allItems).sort((a, b) => parseFloat(a) - parseFloat(b));

        let selectionHtml = '<h4>Выберите показатели:</h4>';

        sortedKeys.forEach(number_naim => {
            const item = allItems[number_naim];
            if (!item) return;

            const subCheckboxes = [];

            if (item.pole == 1) {
                const avg = calculateAverage(fullData, allDates, number_naim, 'kolichestvo');
                const checked = !!selectedItems[number_naim]?.kolichestvo;
                subCheckboxes.push(`
                    <div class="selection-item-sub">
                        <input type="checkbox" class="selection-checkbox" data-naim="${number_naim}" data-type="kolichestvo" ${checked ? 'checked' : ''}>
                        <div class="selection-color" style="background-color: ${item.colors.kolichestvo};"></div>
                        Количество <span class="avg-text">(среднее: ${avg})</span>
                    </div>`);
            } else if (item.pole == 2) {
                const avgNer = calculateAverage(fullData, allDates, number_naim, 'neraskr');
                const avgR = calculateAverage(fullData, allDates, number_naim, 'raskr');
                const checked = !!selectedItems[number_naim]?.neraskr || false;
                subCheckboxes.push(`
                    <div class="selection-item-sub">
                        <input type="checkbox" class="selection-checkbox" data-naim="${number_naim}" data-type="neraskr" ${checked ? 'checked' : ''}>
                        <div class="selection-color" style="background-color: ${item.colors.neraskr};"></div>
                        Нераскрытые <span class="avg-text">(среднее: ${avgNer})</span>
                    </div>
                    <div class="selection-item-sub">
                        <input type="checkbox" class="selection-checkbox" data-naim="${number_naim}" data-type="raskr" ${checked ? 'checked' : ''}>
                        <div class="selection-color" style="background-color: ${item.colors.raskr};"></div>
                        Раскрытые <span class="avg-text">(среднее: ${avgR})</span>
                    </div>`);
            }

            selectionHtml += `
                <div class="selection-item">
                    <strong>${number_naim}. ${item.naimenov.toUpperCase()}</strong>
                    <div class="sub-checkboxes">
                        ${subCheckboxes.join('')}
                    </div>
                </div>`;
        });

        $('#selection').html(selectionHtml);

        $('.selection-checkbox').off('change').on('change', function() {
            const naim = $(this).data('naim');
            const type = $(this).data('type');
            const checked = $(this).is(':checked');

            if (!selectedItems[naim]) selectedItems[naim] = {};
            selectedItems[naim][type] = checked;

            const item = allItems[naim];
            if (item && item.pole == 2) {
                const otherType = type === 'neraskr' ? 'raskr' : 'neraskr';
                selectedItems[naim][otherType] = checked;
                $(`input[data-naim="${naim}"][data-type="${otherType}"]`).prop('checked', checked);
            }

            updateChart();
        });

        if (numberNaimFromUrl !== null && !hasAutoSelected) {
            if (allItems[numberNaimFromUrl]) {
                const item = allItems[numberNaimFromUrl];
                if (!selectedItems[numberNaimFromUrl]) selectedItems[numberNaimFromUrl] = {};
                if (item.pole == 1) {
                    selectedItems[numberNaimFromUrl].kolichestvo = true;
                    $(`input[data-naim="${numberNaimFromUrl}"][data-type="kolichestvo"]`).prop('checked', true);
                } else if (item.pole == 2) {
                    selectedItems[numberNaimFromUrl].neraskr = true;
                    selectedItems[numberNaimFromUrl].raskr = true;
                    $(`input[data-naim="${numberNaimFromUrl}"][data-type="neraskr"], input[data-naim="${numberNaimFromUrl}"][data-type="raskr"]`).prop('checked', true);
                }
                updateChart();
            }
            hasAutoSelected = true;
        }

    } catch (error) {
        console.error('Ошибка updateSelectionMenu:', error);
        $('#selection').html('<p>Ошибка загрузки: ' + error.message + '</p>');
        initializeEmptyCharts();
    }
}

function initializeEmptyCharts() {
    if (chartMain) chartMain.destroy();
    if (chartSub) chartSub.destroy();
    const ctxMain = document.getElementById('myChart').getContext('2d');
    chartMain = new Chart(ctxMain, { type: 'line', data: { labels: [], datasets: [] }, options: { responsive: true, maintainAspectRatio: false } });
    const ctxSub = document.getElementById('myChartSub').getContext('2d');
    chartSub = new Chart(ctxSub, { type: 'line', data: { labels: [], datasets: [] }, options: { responsive: true, maintainAspectRatio: false } });
}

$(document).ready(function() {
    const today = moment();
    const yesterday = today.clone().subtract(1, 'days');

    $('#date-range').daterangepicker({
        startDate: '<?php echo htmlspecialchars($startDate); ?>',
        endDate: '<?php echo htmlspecialchars($endDate); ?>',
        maxDate: yesterday,
        locale: {
            format: 'DD.MM.YYYY',
            separator: ' по ',
            monthNames: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
            daysOfWeek: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
            firstDay: 1,
            applyLabel: 'Применить',
            cancelLabel: 'Отмена'
        },
        opens: 'center',
        autoUpdateInput: true,
        autoApply: true,
        linkedCalendars: false,
        isInvalidDate: date => date.isSame(today, 'day')
    });

    // Автоматическое расширение периода до 30 дней, если передан один день (включая вчерашний)
    const picker = $('#date-range').data('daterangepicker');
    if (picker.startDate.isSame(picker.endDate, 'day')) {
        picker.setStartDate(picker.endDate.clone().subtract(29, 'days'));
        $('#date-range').val(picker.startDate.format('DD.MM.YYYY') + ' по ' + picker.endDate.format('DD.MM.YYYY'));
    }

    $('#date-range').on('apply.daterangepicker', function(ev, picker) {
        let startDate = picker.startDate;
        let endDate = picker.endDate;
        if (startDate.isAfter(endDate)) {
            alert('Дата начала не может быть больше даты окончания');
            picker.setStartDate(endDate);
            picker.setEndDate(startDate);
            return;
        }
        if (endDate.isAfter(yesterday)) {
            alert('Выбранный период не может включать текущий день');
            picker.setEndDate(yesterday);
            return;
        }
        selectedItems = {};
        hasAutoSelected = false;
        updateSelectionMenu();
        logAction(`Выбор периода для графика: ${startDate.format('DD.MM.YYYY')} - ${endDate.format('DD.MM.YYYY')}`);
    });

    $('#ai-button, #geo-button').on('click', function() {
        alert('Функционал в разработке.');
    });

    $('.dropdown-menu').on('click', 'a.dropdown-item', function(e) {
        e.preventDefault();
        const newIndex = $(this).data('index').toString();
        const text = $(this).text().toUpperCase();
        if (newIndex === selectedGarnisonIndex) return;
        selectedGarnisonIndex = newIndex;
        $('#garnizon-dropdown').text(text);
        selectedItems = {};
        hasAutoSelected = false;
        updateSelectionMenu();
        logAction(`Смена гарнизона на графике: ${text}`);
    });

    initializeEmptyCharts();
    updateSelectionMenu();
});
</script>
</body>
</html>
