<?php
$chartMode = 'selector';
require __DIR__ . '/chart_view.php';
__halt_compiler();
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
    <title>График Селектор</title>
    <script src="../assets/js/chart_line.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.1/daterangepicker.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.1/daterangepicker.css">
    <script>
        const garnizonNames = <?php echo json_encode($garnizonNames); ?>;
    </script>
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
            margin-right: 15px;
        }
        .chart-container {
            position: relative;
            width: 100%;
            margin: 0 auto;
            min-height: 500px;
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        .selection-color {
            width: 15px;
            height: 15px;
            margin-right: 5px;
            display: inline-block;
        }
        .selection-checkbox {
            margin-right: 5px;
        }
        #secondary-chart-container {
            display: none;
        }
        #secondary-chart-container.active {
            display: block;
            margin-bottom: 25px;
        }
        #garnizon-dropdown {
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>ГРАФИК СЕЛЕКТОР</h1>
        </div>
    </div>
    <div class="container">
        <div class="date-selector">
            <a href="<?php echo htmlspecialchars('selector.php' . ($garnizon || $startDate || $endDate ? '?' : '') . ($garnizon ? 'garnizon=' . urlencode($garnizon) : '') . ($startDate ? ($garnizon ? '&' : '') . 'start=' . urlencode($startDate) : '') . ($endDate ? ($garnizon || $startDate ? '&' : '') . 'end=' . urlencode($endDate) : '')); ?>" class="btn btn-primary btn-back" id="back-button">НАЗАД</a>
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
                        <?php } else { ?>
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
                <canvas id="myChart"></canvas>
            </div>
            <div id="selection" class="selection-container"></div>
            <div id="secondary-chart-container" class="chart-container">
                <canvas id="secondaryChart"></canvas>
            </div>
        </div>
    </div>
<script>
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    elements: {
        line: {
            tension: 0.4  // Глобальное сглаживание (плавные кривые Безье)
        }
    },
    scales: {
        x: {
            title: { display: true, text: 'Дата', font: { size: 14 } },
            ticks: { maxRotation: 45, minRotation: 45 }
        },
        y: {
            title: { display: true, text: 'Количество', font: { size: 14 } },
            beginAtZero: true,
            ticks: { stepSize: 1, precision: 0 }
        }
    },
    plugins: {
        legend: { display: true, position: 'top', labels: { font: { size: 12 } } },
        tooltip: {
            enabled: true,
            mode: 'nearest',
            intersect: true,
            position: 'nearest',
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleFont: { size: 14 },
            bodyFont: { size: 12 },
            padding: 10,
            callbacks: {
                label: function(context) {
                    let label = context.dataset.label || '';
                    if (label) label += ': ';
                    if (context.parsed.y !== null) label += context.parsed.y;
                    return label;
                }
            }
        },
        title: { display: false, text: '', font: { size: 16, weight: 'bold' }, padding: { top: 10, bottom: 10 } }
    },
    interaction: { mode: 'nearest', axis: 'xy', intersect: true }
};
const mainChartOptions = JSON.parse(JSON.stringify(chartOptions));
const secondaryChartOptions = JSON.parse(JSON.stringify(chartOptions));
secondaryChartOptions.scales.y.beginAtZero = true;

// ===== ИНИЦИАЛИЗИРУЕМ ВСЕ ГЛОБАЛЬНЫЕ ПЕРЕМЕННЫЕ ЗДЕСЬ =====
let chartInstance = null;
let secondaryChartInstance = null;
let selectedGarnizonIndex = <?php echo json_encode($initialGarnisonIndex); ?>;
let accessLevel = ""3"";
let selectedItems = {};
let numberNaim = <?php echo json_encode($numberNaim); ?>;
let isEditableGlobal = false;
let markedDatesCache = [];
let daterangepickerInstance = null;

async function loadMarkedDatesCache() {
    markedDatesCache = [];
    let garnizonIds;
  
    if ((accessLevel === '2') && selectedGarnizonIndex === '88') {
        garnizonIds = ['6', '5', '31', '10', '13', '29', '17', '93'];
    } else if (selectedGarnizonIndex === '6') {
        garnizonIds = ['6', '93'];
    } else {
        garnizonIds = [selectedGarnizonIndex];
    }
  
    for (let garnizon of garnizonIds) {
        try {
            const response = await fetch('../api/marked_dates.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ garnizon: garnizon }),
            });
            const data = await response.json();
            if (data.success && Array.isArray(data.periods)) {
                markedDatesCache = markedDatesCache.concat(data.periods.filter(period =>
                    moment(period.start, "YYYY-MM-DD", true).isValid() &&
                    moment(period.end, "YYYY-MM-DD", true).isValid()
                ));
            }
        } catch (error) {
            console.error(`Ошибка при загрузке отмеченных дат для гарнизона ${garnizon}:`, error);
            await logAction(`Ошибка при загрузке отмеченных дат для гарнизона ${garnizon}: ${error.message}`);
        }
    }

    // Дедупликация периодов строго по start и end
    const seen = new Set();
    markedDatesCache = markedDatesCache.filter(period => {
        const key = `${period.start}|${period.end}`;
        if (seen.has(key)) {
            return false;
        }
        seen.add(key);
        return true;
    });

    // Сортировка по дате начала
    markedDatesCache.sort((a, b) => a.start.localeCompare(b.start));
}

function findLatestFilledPeriod(selectedEnd, periods) {
    let latestEnd = null;
    let latestPeriod = null;
    periods.forEach(period => {
        const pEnd = moment(period.end, "YYYY-MM-DD");
        if (pEnd.isSameOrBefore(selectedEnd) && (!latestEnd || pEnd.isAfter(latestEnd))) {
            latestEnd = pEnd;
            latestPeriod = period;
        }
    });
    return latestPeriod;
}

function findPeriodContainingDate(date, periods) {
    return periods.find(period => {
        const pStart = moment(period.start, "YYYY-MM-DD");
        const pEnd = moment(period.end, "YYYY-MM-DD");
        return date.isSameOrAfter(pStart) && date.isSameOrBefore(pEnd);
    });
}

function isRangeOverlappingAnyMarked(start, end, periods) {
    return periods.some(period => {
        const pStart = moment(period.start, "YYYY-MM-DD");
        const pEnd = moment(period.end, "YYYY-MM-DD");
        return !start.isAfter(pEnd) && !end.isBefore(pStart);
    });
}

function isExactlyOneFullPeriodCovered(selectedStart, selectedEnd, periods) {
    if (!periods || periods.length === 0) return false;
  
    const coveringPeriods = periods.filter(period => {
        const pStart = moment(period.start, "YYYY-MM-DD");
        const pEnd = moment(period.end, "YYYY-MM-DD");
        if (!pStart.isValid() || !pEnd.isValid()) return false;
        return selectedStart.isSameOrAfter(pStart) && selectedEnd.isSameOrBefore(pEnd);
    });
  
    return coveringPeriods.length === 1;
}

function isOneDay(startDate, endDate) {
    return startDate.isSame(endDate, 'day');
}

function shouldExpandPeriod(selectedStart, selectedEnd, markedDates) {
    if (isOneDay(selectedStart, selectedEnd)) {
        return true;
    }
    if (isExactlyOneFullPeriodCovered(selectedStart, selectedEnd, markedDates)) {
        return true;
    }
    return false;
}

async function adjustDateRange(selectedStart, selectedEnd, isRollback = false) {
    if (markedDatesCache.length === 0) {
        await loadMarkedDatesCache();
    }
    let adjustedStart = selectedStart.clone();
    let adjustedEnd = selectedEnd.clone();

    // 1. Если выбран ровно один день и он внутри какого-то сохранённого периода → расширяем до всего периода
    if (isOneDay(adjustedStart, adjustedEnd)) {
        const containingPeriod = findPeriodContainingDate(adjustedStart, markedDatesCache);
        if (containingPeriod) {
            adjustedStart = moment(containingPeriod.start, "YYYY-MM-DD");
            adjustedEnd = moment(containingPeriod.end, "YYYY-MM-DD");
            return { start: adjustedStart, end: adjustedEnd };
        }
    }

    // 2. Если выбран диапазон, который полностью лежит внутри одного сохранённого периода → расширяем до него
    if (isExactlyOneFullPeriodCovered(adjustedStart, adjustedEnd, markedDatesCache)) {
        const coveringPeriod = findPeriodContainingDate(adjustedStart, markedDatesCache); // или любой день из диапазона
        if (coveringPeriod) {
            adjustedStart = moment(coveringPeriod.start, "YYYY-MM-DD");
            adjustedEnd = moment(coveringPeriod.end, "YYYY-MM-DD");
            return { start: adjustedStart, end: adjustedEnd };
        }
    }

    // 3. Если диапазон НЕ пересекает ни один сохранённый период → берём самый последний заполненный
    if (!isRangeOverlappingAnyMarked(adjustedStart, adjustedEnd, markedDatesCache)) {
        const latestPeriod = findLatestFilledPeriod(adjustedEnd, markedDatesCache);
        if (latestPeriod) {
            adjustedStart = moment(latestPeriod.start, "YYYY-MM-DD");
            adjustedEnd = moment(latestPeriod.end, "YYYY-MM-DD");
        }
    }

    // 4. Если это rollback (пользователь сдвинул начало влево) → прижимаем начало к началу периода, содержащего конец
    else if (isRollback) {
        const containingPeriod = findPeriodContainingDate(adjustedEnd, markedDatesCache);
        if (containingPeriod) {
            adjustedStart = moment(containingPeriod.start, "YYYY-MM-DD");
        }
    }

    // 5. Старое поведение «откат на последний месяц» убираем полностью — оно больше не нужно

    return { start: adjustedStart, end: adjustedEnd };
}

function logAction(action) {
    $.ajax({
        url: '../api/stub.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ action: action }),
        error: function(xhr, status, error) {
            console.error('Ошибка логирования:', error, xhr.responseText);
        }
    });
}

function initializeSelectedItems() {
    if (Object.keys(selectedItems).length === 0 && numberNaim) {
        if (selectedGarnizonIndex === '6') {
            ['6', '93', '6_total'].forEach(g => {
                const key = `${numberNaim}_${g}`;
                selectedItems[key] = { kolichestvo: true, color: null };
            });
        } else if ((accessLevel === '2') && selectedGarnizonIndex === '88') {
            const key = `${numberNaim}_88_total`;
            selectedItems[key] = { kolichestvo: true, color: null };
            ['6_total', '5', '31', '10', '13', '29', '17'].forEach(g => {
                const districtKey = `${numberNaim}_${g}`;
                selectedItems[districtKey] = { kolichestvo: true, color: null };
            });
        } else {
            const key = `${numberNaim}_${selectedGarnizonIndex}`;
            selectedItems[key] = { kolichestvo: true, color: null };
        }
    }
}

function splitIntoWeeks(startDate, endDate) {
    const weeks = [];
    let currentStart = startDate.clone();
    let periodNum = 0;
    while (currentStart.isSameOrBefore(endDate)) {
        let weekEnd = currentStart.clone().add(6, 'days');
        if (weekEnd.isAfter(endDate)) weekEnd = endDate.clone();
        weeks.push({
            start: currentStart.format('DD.MM.YYYY'),
            end: weekEnd.format('DD.MM.YYYY'),
            period: periodNum++
        });
        currentStart.add(7, 'days');
    }
    return weeks;
}

async function markDatesInCalendar() {
    if (selectedGarnizonIndex === null || $('.drp-calendar').length === 0) return;
    let allMarkedDates = markedDatesCache;
    if (allMarkedDates.length === 0) {
        await loadMarkedDatesCache();
        allMarkedDates = markedDatesCache;
    }
    $('.drp-calendar tbody td').css({
        'background-color': '',
        'color': '',
        'text-decoration': '',
        'cursor': ''
    }).removeClass('marked-date').find('.calendar-underline').remove();
    $('.drp-calendar').each(function() {
        const $calendar = $(this);
        let monthYearText = '';
        const $monthElement = $calendar.find('.month');
        if ($monthElement.length) {
            monthYearText = $monthElement.text().trim();
        }
        if (!monthYearText) return;
        $calendar.find('tbody td').each(function() {
            if ($(this).hasClass('week') || !$(this).text().trim()) return;
            const dateText = $(this).text().trim();
            const formattedDate = convertToFullDate(dateText, monthYearText);
            if (!formattedDate) return;
            const currentDate = moment(formattedDate, "YYYY-MM-DD");
            const yesterday = moment().subtract(1, 'day');
            if (!$(this).hasClass('off') && currentDate.isValid() && isValidDateInCurrentMonth(formattedDate, monthYearText)) {
                const markedDate = allMarkedDates.find(period => {
                    const periodStart = moment(period.start, "YYYY-MM-DD");
                    const periodEnd = moment(period.end, "YYYY-MM-DD");
                    return currentDate.isSameOrAfter(periodStart) && currentDate.isSameOrBefore(periodEnd);
                });
                if (markedDate) {
                    $(this).css({
                        'color': '#000',
                        'text-decoration': 'none',
                        'cursor': 'pointer',
                        'position': 'relative'
                    }).removeClass('disabled off unavailable').addClass('available').append(`
                        <span class="calendar-underline" style="
                            position: absolute; bottom: 2px; left: 50%; width: 50%; height: 2px; z-index: 100;
                            background-color: ${markedDate.color}; transform: translateX(-50%);
                        "></span>
                    `);
                } else if (currentDate.isSame(yesterday, 'day')) {
                    $(this).removeClass('disabled off unavailable').addClass('available').css({
                        'color': '#000',
                        'text-decoration': 'none',
                        'cursor': 'pointer'
                    });
                }
            }
        });
    });
}

function isValidDateInCurrentMonth(date, monthYear) {
    try {
        if (!date || !monthYear) return false;
   
        const months = {
            'Январь': '01', 'Февраль': '02', 'Март': '03', 'Апрель': '04', 'Май': '05', 'Июнь': '06',
            'Июль': '07', 'Август': '08', 'Сентябрь': '09', 'Октябрь': '10', 'Ноябрь': '11', 'Декабрь': '12'
        };
   
        const monthMatch = Object.keys(months).find(month => monthYear.includes(month));
        const yearMatch = monthYear.match(/\d{4}/);
   
        if (!monthMatch || !yearMatch) return false;
   
        const dateObj = moment(date, "YYYY-MM-DD", true);
        if (!dateObj.isValid()) return false;
   
        return dateObj.format('MM') === months[monthMatch] &&
               dateObj.format('YYYY') === yearMatch[0];
    } catch (error) {
        return false;
    }
}

function convertToFullDate(day, monthYear) {
    try {
        const months = {
            'Январь': '01', 'Февраль': '02', 'Март': '03', 'Апрель': '04', 'Май': '05', 'Июнь': '06',
            'Июль': '07', 'Август': '08', 'Сентябрь': '09', 'Октябрь': '10', 'Ноябрь': '11', 'Декабрь': '12'
        };
   
        if (!day || !monthYear || !/^\d+$/.test(day)) return null;
   
        const monthMatch = Object.keys(months).find(month => monthYear.includes(month));
        if (!monthMatch) return null;
   
        const yearMatch = monthYear.match(/\d{4}/);
        if (!yearMatch) return null;
   
        const month = months[monthMatch];
        const year = yearMatch[0];
        const formattedDay = day.padStart(2, '0');
   
        const testDate = moment(`${year}-${month}-${formattedDay}`, "YYYY-MM-DD", true);
        if (!testDate.isValid()) return null;
   
        return testDate.format('YYYY-MM-DD');
    } catch (error) {
        return null;
    }
}

function initializeEmptyChart(chartId = 'myChart') {
    const instance = chartId === 'myChart' ? chartInstance : secondaryChartInstance;
    if (instance) instance.destroy();
    const canvas = document.getElementById(chartId);
    if (!canvas) {
        console.error(`Canvas with id "${chartId}" not found.`);
        return;
    }
    const ctx = canvas.getContext('2d');
    const options = chartId === 'myChart' ? mainChartOptions : secondaryChartOptions;
    const newChart = new Chart(ctx, {
        type: 'line',
        data: { labels: [], datasets: [] },
        options: options
    });
    if (chartId === 'myChart') {
        chartInstance = newChart;
    } else {
        secondaryChartInstance = newChart;
    }
}

async function updateSelectionMenu() {
    if (!selectedGarnizonIndex) {
        console.error('Нет выбранного гарнизона');
        $('#selection').html('<p>Ошибка: гарнизон не выбран.</p>');
        initializeEmptyChart();
        return;
    }
    if (!daterangepickerInstance) {
        console.error('Daterangepicker не инициализирован');
        return;
    }
    const startDate = daterangepickerInstance.startDate.format('YYYY-MM-DD');
    const endDate = daterangepickerInstance.endDate.format('YYYY-MM-DD');
    try {
        const response = await $.ajax({
            url: '../api/get_chart.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                startDate: startDate,
                endDate: endDate,
                garnizon: selectedGarnizonIndex,
                numberNaim: null
            }),
            timeout: 10000
        });
        if (!response.success) {
            console.error('Ошибка сервера:', response.error);
            $('#selection').html('<p>Ошибка загрузки данных: ' + (response.error || 'Неизвестная ошибка сервера') + '</p>');
            initializeEmptyChart();
            if ((accessLevel === '2' || accessLevel === '4') && selectedGarnizonIndex === '88') {
                initializeEmptyChart('secondaryChart');
                $('#secondary-chart-container').addClass('active');
            }
            logAction(`Ошибка в updateSelectionMenu: ${response.error || 'Неизвестная ошибка сервера'}`);
            return;
        }
        let allItems = response.allItems || {};
        let colors = response.colors || {};
        let data = response.data || [];
        if (data.length === 0) {
            await loadMarkedDatesCache();
            const latestPeriod = findLatestFilledPeriod(moment(endDate), markedDatesCache);
            if (latestPeriod) {
                const newStartStr = latestPeriod.start;
                const newEndStr = latestPeriod.end;
                if (newStartStr === startDate && newEndStr === endDate) {
                    $('#selection').html('<p>Нет данных для выбранного периода.</p>');
                    initializeEmptyChart();
                    if ((accessLevel === '2' || accessLevel === '4') && selectedGarnizonIndex === '88') {
                        initializeEmptyChart('secondaryChart');
                        $('#secondary-chart-container').addClass('active');
                    }
                    return;
                }
                daterangepickerInstance.setStartDate(moment(newStartStr));
                daterangepickerInstance.setEndDate(moment(newEndStr));
                $('#date-range').val(daterangepickerInstance.startDate.format('DD.MM.YYYY') + ' по ' + daterangepickerInstance.endDate.format('DD.MM.YYYY'));
                return await updateSelectionMenu();
            } else {
                $('#selection').html('<p>Нет данных для отображения.</p>');
                initializeEmptyChart();
                if ((accessLevel === '2' || accessLevel === '4') && selectedGarnizonIndex === '88') {
                    initializeEmptyChart('secondaryChart');
                    $('#secondary-chart-container').addClass('active');
                }
                return;
            }
        }
        let averages = {};
        let totals = {};
        let uniqueDays = {};
        data.forEach(item => {
            if (!item.date || !moment(item.date, 'YYYY-MM-DD', true).isValid()) return;
            const key = `${item.number_naim}_${item.garnizon}`;
            if (!uniqueDays[key]) {
                uniqueDays[key] = new Set();
            }
            uniqueDays[key].add(item.date);
        });
        data.forEach(item => {
            if (!item.date || !moment(item.date, 'YYYY-MM-DD', true).isValid()) return;
            const key = `${item.number_naim}_${item.garnizon}`;
            if (!averages[key]) {
                averages[key] = { sum: 0 };
            }
            if (item.kolichestvo !== null && !isNaN(item.kolichestvo)) {
                averages[key].sum += Number(item.kolichestvo);
            }
            if ((accessLevel === '2' || accessLevel === '4') && selectedGarnizonIndex === '88' && ['6', '5', '31', '10', '13', '29', '17', '93'].includes(item.garnizon)) {
                const totalKey = `${item.number_naim}_88_total`;
                if (!totals[totalKey]) {
                    totals[totalKey] = { sum: 0 };
                    uniqueDays[totalKey] = new Set();
                }
                if (item.kolichestvo !== null && !isNaN(item.kolichestvo)) {
                    totals[totalKey].sum += Number(item.kolichestvo);
                }
                uniqueDays[totalKey].add(item.date);
            }
            if (selectedGarnizonIndex === '6' && ['6', '93'].includes(item.garnizon)) {
                const totalKey = `${item.number_naim}_6_total`;
                if (!totals[totalKey]) {
                    totals[totalKey] = { sum: 0 };
                    uniqueDays[totalKey] = new Set();
                }
                if (item.kolichestvo !== null && !isNaN(item.kolichestvo)) {
                    totals[totalKey].sum += Number(item.kolichestvo);
                }
                uniqueDays[totalKey].add(item.date);
            }
        });
        Object.keys(averages).forEach(key => {
            const uniqueCount = uniqueDays[key]?.size || 0;
            averages[key].avg = uniqueCount > 0 ? (averages[key].sum / uniqueCount).toFixed(2) : '0.00';
        });
        Object.keys(totals).forEach(key => {
            const uniqueCount = uniqueDays[key]?.size || 0;
            averages[key] = uniqueCount > 0 ? { avg: (totals[key].sum / uniqueCount).toFixed(2) } : { avg: '0.00' };
        });
        let selectionHtml = '<h4>Выберите показатели:</h4>';
        Object.entries(allItems).forEach(([number_naim, naimenov]) => {
            if (selectedGarnizonIndex === '6') {
                ['6', '93', '6_total'].forEach(garnizon => {
                    const key = `${number_naim}_${garnizon}`;
                    const label = garnizon === '6_total' ? `${naimenov} (Тирасполь)` :
                                  garnizon === '93' ? `${naimenov} (ОРОВД)` : `${naimenov} (Центр)`;
                    const colorKey = garnizon === '6_total' ? `${number_naim}_total` :
                                    garnizon === '93' ? `${number_naim}_orvd` : `${number_naim}_${garnizon}`;
                    let hash = simpleHash(colorKey);
                    let offset = 0;
                    if (garnizon === '93') offset = 1;
                    else if (garnizon === '6_total') offset = 2;
                    const numColors = Object.keys(colors).length;
                    const colorIndex = ((hash + offset) % numColors) + 1;
                    const itemColor = colors[colorIndex] || '#999999';
                    const avg = averages[key] ? averages[key].avg : '0.00';
                    if (!selectedItems[key]) {
                        selectedItems[key] = { kolichestvo: false, color: itemColor };
                    } else if (!selectedItems[key].color) {
                        selectedItems[key].color = itemColor;
                    }
                    selectionHtml += `
                        <div class="selection-item">
                            <input type="checkbox" class="selection-checkbox"
                                   data-id="${key}"
                                   data-type="kolichestvo"
                                   data-number-naim="${number_naim}"
                                   data-garnizon="${garnizon}"
                                   ${selectedItems[key].kolichestvo ? 'checked' : ''}>
                            <div class="selection-color" style="background-color: ${itemColor}"></div>
                            ${label} (среднее: ${avg})
                        </div>`;
                });
            } else if ((accessLevel === '2' || accessLevel === '4') && selectedGarnizonIndex === '88') {
                const key = `${number_naim}_88_total`;
                const label = `${naimenov} (Общее по МВД)`;
                const colorKey = `${number_naim}_total`;
                let hash = simpleHash(colorKey);
                const colorIndex = (hash % Object.keys(colors).length) + 1;
                const itemColor = colors[colorIndex] || '#999999';
                const avg = averages[key] ? averages[key].avg : '0.00';
                if (!selectedItems[key]) {
                    selectedItems[key] = { kolichestvo: false, color: itemColor };
                } else if (!selectedItems[key].color) {
                    selectedItems[key].color = itemColor;
                }
                selectionHtml += `
                    <div class="selection-item">
                        <input type="checkbox" class="selection-checkbox"
                               data-id="${key}"
                               data-type="kolichestvo"
                               data-number-naim="${number_naim}"
                               data-garnizon="88_total"
                               ${selectedItems[key].kolichestvo ? 'checked' : ''}>
                        <div class="selection-color" style="background-color: ${itemColor}"></div>
                        ${label} (среднее: ${avg})
                    </div>`;
            } else {
                const key = `${number_naim}_${selectedGarnizonIndex}`;
                const label = naimenov;
                const colorKey = `${number_naim}_${selectedGarnizonIndex}`;
                let hash = simpleHash(colorKey);
                const colorIndex = (hash % Object.keys(colors).length) + 1;
                const itemColor = colors[colorIndex] || '#999999';
                const avg = averages[key] ? averages[key].avg : '0.00';
                if (!selectedItems[key]) {
                    selectedItems[key] = { kolichestvo: false, color: itemColor };
                } else if (!selectedItems[key].color) {
                    selectedItems[key].color = itemColor;
                }
                selectionHtml += `
                    <div class="selection-item">
                        <input type="checkbox" class="selection-checkbox"
                               data-id="${key}"
                               data-type="kolichestvo"
                               data-number-naim="${number_naim}"
                               data-garnizon="${selectedGarnizonIndex}"
                               ${selectedItems[key].kolichestvo ? 'checked' : ''}>
                        <div class="selection-color" style="background-color: ${itemColor}"></div>
                        ${label} (среднее: ${avg})
                    </div>`;
            }
        });
        $('#selection').html(selectionHtml);
        $('.selection-checkbox').off('change').on('change', function() {
            const id = $(this).data('id');
            const type = $(this).data('type');
            const number_naim = $(this).data('number-naim');
            const garnizon = $(this).data('garnizon');
            const isChecked = $(this).is(':checked');
            if (selectedGarnizonIndex === '6') {
                ['6', '93', '6_total'].forEach(g => {
                    const key = `${number_naim}_${g}`;
                    if (!selectedItems[key]) {
                        const colorKey = g === '6_total' ? `${number_naim}_total` :
                                        g === '93' ? `${number_naim}_orvd` : `${number_naim}_${g}`;
                        let hash = simpleHash(colorKey);
                        let offset = 0;
                        if (g === '93') offset = 1;
                        else if (g === '6_total') offset = 2;
                        const numColors = Object.keys(colors).length;
                        const colorIndex = ((hash + offset) % numColors) + 1;
                        const itemColor = colors[colorIndex] || '#999999';
                        selectedItems[key] = { kolichestvo: isChecked, color: itemColor };
                    } else {
                        selectedItems[key].kolichestvo = isChecked;
                    }
                    $(`.selection-checkbox[data-id="${key}"]`).prop('checked', isChecked);
                });
            } else if ((accessLevel === '2' || accessLevel === '4') && selectedGarnizonIndex === '88') {
                const key = `${number_naim}_88_total`;
                if (!selectedItems[key]) {
                    const colorKey = `${number_naim}_total`;
                    let hash = simpleHash(colorKey);
                    const colorIndex = (hash % Object.keys(colors).length) + 1;
                    const itemColor = colors[colorIndex] || '#999999';
                    selectedItems[key] = { kolichestvo: isChecked, color: itemColor };
                } else {
                    selectedItems[key].kolichestvo = isChecked;
                }
                ['6_total', '5', '31', '10', '13', '29', '17'].forEach(g => {
                    const districtKey = `${number_naim}_${g}`;
                    if (!selectedItems[districtKey]) {
                        const districtColorKey = g === '6_total' ? `${number_naim}_total` : `${number_naim}_${g}`;
                        let districtHash = simpleHash(districtColorKey);
                        const districtColorIndex = (districtHash % Object.keys(colors).length) + 1;
                        const districtItemColor = colors[districtColorIndex] || '#999999';
                        selectedItems[districtKey] = { kolichestvo: isChecked, color: districtItemColor };
                    } else {
                        selectedItems[districtKey].kolichestvo = isChecked;
                    }
                });
            } else {
                if (!selectedItems[id]) {
                    const colorKey = `${number_naim}_${garnizon}`;
                    let hash = simpleHash(colorKey);
                    const colorIndex = (hash % Object.keys(colors).length) + 1;
                    const itemColor = colors[colorIndex] || '#999999';
                    selectedItems[id] = { kolichestvo: isChecked, color: itemColor };
                } else {
                    selectedItems[id].kolichestvo = isChecked;
                }
            }
            updateChart();
            logAction(`Изменение чекбокса: ${id} (${isChecked ? 'включен' : 'выключен'})`);
        });
        await updateChart();
    } catch (error) {
        console.error('Ошибка в updateSelectionMenu:', error);
        let errorMessage = error.responseJSON?.error || error.statusText || error.message || 'Неизвестная ошибка';
        $('#selection').html(`<p>Ошибка загрузки данных: ${errorMessage} (Статус: ${error.status || 'N/A'})</p>`);
        initializeEmptyChart();
        if ((accessLevel === '2' || accessLevel === '4') && selectedGarnizonIndex === '88') {
            initializeEmptyChart('secondaryChart');
            $('#secondary-chart-container').addClass('active');
        }
        logAction(`Ошибка в updateSelectionMenu: ${errorMessage} (Статус: ${error.status || 'N/A'})`);
    }
}

async function updateChart() {
    if (!selectedGarnizonIndex) {
        console.error('Нет выбранного гарнизона');
        initializeEmptyChart();
        $('#selection').html('<p>Ошибка: гарнизон не выбран.</p>');
        return;
    }
    if (!daterangepickerInstance) {
        console.error('Daterangepicker не инициализирован');
        return;
    }
    const requestedStartMoment = daterangepickerInstance.startDate.clone();
    const requestedEndMoment = daterangepickerInstance.endDate.clone();
    const requestedStart = requestedStartMoment.format('YYYY-MM-DD');
    const requestedEnd = requestedEndMoment.format('YYYY-MM-DD');

    if (markedDatesCache.length === 0) {
        await loadMarkedDatesCache();
    }

    const relevantPeriods = markedDatesCache.filter(period => {
        const periodStart = moment(period.start, 'YYYY-MM-DD');
        const periodEnd = moment(period.end, 'YYYY-MM-DD');
        return periodStart.isSameOrBefore(requestedEndMoment) &&
               periodEnd.isSameOrAfter(requestedStartMoment);
    });

    if (relevantPeriods.length === 0) {
        initializeEmptyChart();
        if ((accessLevel === '2' || accessLevel === '4') && selectedGarnizonIndex === '88') {
            initializeEmptyChart('secondaryChart');
            $('#secondary-chart-container').addClass('active');
        }
        $('#selection').html('<p>Нет данных для отображения в выбранном периоде.</p>');
        return;
    }

    try {
        const response = await $.ajax({
            url: '../api/get_chart.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                startDate: requestedStart,
                endDate: requestedEnd,
                garnizon: selectedGarnizonIndex,
                numberNaim: null
            }),
            timeout: 10000
        });

        if (!response.success) {
            throw new Error(response.error || 'Неизвестная ошибка сервера');
        }

        let data = response.data || [];
        let allItems = response.allItems || {};
        let colors = response.colors || {};

        // Если данных нет — пытаемся подтянуть последний период
        if (data.length === 0) {
            const latestPeriod = findLatestFilledPeriod(moment(requestedEnd), markedDatesCache);
            if (latestPeriod && (latestPeriod.start !== requestedStart || latestPeriod.end !== requestedEnd)) {
                daterangepickerInstance.setStartDate(moment(latestPeriod.start));
                daterangepickerInstance.setEndDate(moment(latestPeriod.end));
                $('#date-range').val(moment(latestPeriod.start).format('DD.MM.YYYY') + ' по ' + moment(latestPeriod.end).format('DD.MM.YYYY'));
                return await updateChart(); // рекурсивный вызов с новым диапазоном
            }
            initializeEmptyChart();
            $('#selection').html('<p>Нет данных для отображения.</p>');
            return;
        }

        // === НОВАЯ ЛОГИКА: используем реальные даты из данных (data_end) ===
        let dataByDate = {}; // 'YYYY-MM-DD' => { naim: { total: sum, garns: {garn: val} } }

        data.forEach(item => {
            if (!item.date || !moment(item.date, 'YYYY-MM-DD', true).isValid()) return;
            const date = item.date;
            const naim = item.number_naim;
            const garn = item.garnizon;
            const val = Number(item.kolichestvo || 0);

            if (!dataByDate[date]) dataByDate[date] = {};
            if (!dataByDate[date][naim]) dataByDate[date][naim] = { total: 0, garns: {} };
            dataByDate[date][naim].garns[garn] = val;
            dataByDate[date][naim].total += val;
        });

        const uniqueDates = Object.keys(dataByDate).sort((a, b) => a.localeCompare(b));
        const labels = uniqueDates.map(date => moment(date).format('DD.MM.YYYY'));

        let seriesData = {};

        // Инициализация серий (label и цвет как раньше)
        Object.keys(selectedItems).filter(key => selectedItems[key].kolichestvo).forEach(key => {
            const parts = key.split('_');
            const number_naim = parts[0];
            const garnizon = parts.slice(1).join('_');
            const label = garnizon === '6_total' ? `${allItems[number_naim]} (Тирасполь)` :
                          garnizon === '88_total' ? `${allItems[number_naim]} (Общее по МВД)` :
                          garnizon === '93' ? `${allItems[number_naim]} (ОРОВД)` :
                          garnizon === '6' ? `${allItems[number_naim]} (Центр)` :
                          `${allItems[number_naim]} (${garnizonNames[garnizon] || garnizon})`;

            const colorKey = garnizon === '6_total' || garnizon === '88_total' ? `${number_naim}_total` :
                             garnizon === '93' ? `${number_naim}_orvd` : `${number_naim}_${garnizon}`;
            let hash = simpleHash(colorKey);
            let colorIndex = selectedGarnizonIndex === '6' ?
                ((hash + (garnizon === '93' ? 1 : garnizon === '6_total' ? 2 : 0)) % Object.keys(colors).length) + 1 :
                (hash % Object.keys(colors).length) + 1;
            const itemColor = colors[colorIndex] || '#999999';

            seriesData[key] = {
                naimenov: label,
                color: selectedItems[key].color || itemColor
            };
        });

        // Заполнение точек по реальным датам
        Object.keys(seriesData).forEach(key => {
            const parts = key.split('_');
            const naim = parts[0];
            const garnSuffix = parts.slice(1).join('_');
            const isTotal = (garnSuffix === '88_total' || garnSuffix === '6_total');

            const points = uniqueDates.map((date, i) => {
                const label = labels[i];
                let y = 0;
                const dayData = dataByDate[date][naim];
                if (dayData) {
                    if (isTotal) {
                        y = dayData.total;
                    } else {
                        y = dayData.garns[garnSuffix] || 0;
                    }
                }
                return { x: label, y: y };
            });

            seriesData[key].kolichestvo = points;
        });

        // === Датасеты (основной и вторичный) ===
        const datasets = [];
        const secondaryDatasets = [];
        Object.keys(seriesData).forEach(key => {
            if (!selectedItems[key]?.kolichestvo || seriesData[key].kolichestvo.length === 0) return;
            const dataset = {
                label: seriesData[key].naimenov,
                data: seriesData[key].kolichestvo,
                borderColor: seriesData[key].color,
                backgroundColor: seriesData[key].color,
                borderWidth: key.endsWith('_88_total') || key.endsWith('_6_total') ? 4 : 2,
                fill: false,
                spanGaps: true
            };
            if (selectedGarnizonIndex === '88' && (accessLevel === '2' || accessLevel === '4') && !key.endsWith('_88_total')) {
                secondaryDatasets.push(dataset);
            } else {
                datasets.push(dataset);
            }
        });

        // Обновление графиков
        if (chartInstance) {
            chartInstance.data.labels = labels;
            chartInstance.data.datasets = datasets;
            chartInstance.update();
        } else {
            chartInstance = new Chart(document.getElementById('myChart').getContext('2d'), {
                type: 'line',
                data: { labels, datasets },
                options: mainChartOptions
            });
        }

        if (selectedGarnizonIndex === '88' && (accessLevel === '2' || accessLevel === '4')) {
            $('#secondary-chart-container').addClass('active');
            if (secondaryChartInstance) {
                secondaryChartInstance.data.labels = labels;
                secondaryChartInstance.data.datasets = secondaryDatasets;
                secondaryChartInstance.update();
            } else {
                secondaryChartInstance = new Chart(document.getElementById('secondaryChart').getContext('2d'), {
                    type: 'line',
                    data: { labels, datasets: secondaryDatasets },
                    options: secondaryChartOptions
                });
            }
        } else {
            $('#secondary-chart-container').removeClass('active');
        }

    } catch (error) {
        console.error('Ошибка при загрузке данных для графика:', error);
        const errorMessage = error.responseJSON?.error || error.message || 'Неизвестная ошибка';
        initializeEmptyChart();
        if ((accessLevel === '2' || accessLevel === '4') && selectedGarnizonIndex === '88') {
            initializeEmptyChart('secondaryChart');
            $('#secondary-chart-container').addClass('active');
        }
        $('#selection').html(`<p>Ошибка загрузки данных: ${errorMessage}</p>`);
        logAction(`Ошибка в updateChart: ${errorMessage}`);
    }
}

function simpleHash(str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        hash = (hash * 34 + str.charCodeAt(i)) & 0x7FFFFFFF;
    }
    return hash;
}

$(document).ready(async function() {
    moment.locale('ru');
    let today = moment();
    let yesterday = today.clone().subtract(1, 'days');
    let initialStartDate = '<?php echo htmlspecialchars($startDate); ?>';
    let initialEndDate = '<?php echo htmlspecialchars($endDate); ?>';
    if (!moment(initialStartDate, 'DD.MM.YYYY', true).isValid()) {
        initialStartDate = yesterday.format('DD.MM.YYYY');
    }
    if (!moment(initialEndDate, 'DD.MM.YYYY', true).isValid()) {
        initialEndDate = yesterday.format('DD.MM.YYYY');
    }
    $('#date-range').val(initialStartDate + ' по ' + initialEndDate);

    const originalUpdateCalendars = $.fn.daterangepicker.prototype.updateCalendars;
    $.fn.daterangepicker.prototype.updateCalendars = function() {
        originalUpdateCalendars.apply(this, arguments);
        setTimeout(() => markDatesInCalendar(), 0);
    };

    await loadMarkedDatesCache();

    $('#date-range').daterangepicker({
        startDate: initialStartDate,
        endDate: initialEndDate,
        locale: {
            format: 'DD.MM.YYYY',
            separator: ' по ',
            monthNames: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
            daysOfWeek: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
            firstDay: 1,
            applyLabel: 'Применить',
            cancelLabel: 'Отмена',
            customRangeLabel: 'Выбрать даты'
        },
        opens: 'center',
        autoUpdateInput: true,
        maxDate: yesterday,
        autoApply: true,
        linkedCalendars: false,
        isInvalidDate: function(date) {
            return date.isSame(today, 'day') || date.isAfter(today, 'day');
        }
    });

    daterangepickerInstance = $('#date-range').data('daterangepicker');

    if (daterangepickerInstance) {
        const adjusted = await adjustDateRange(
            daterangepickerInstance.startDate,
            daterangepickerInstance.endDate
        );
  
        if (!adjusted.start.isSame(daterangepickerInstance.startDate) ||
            !adjusted.end.isSame(daterangepickerInstance.endDate)) {
            daterangepickerInstance.setStartDate(adjusted.start);
            daterangepickerInstance.setEndDate(adjusted.end);
            $('#date-range').val(adjusted.start.format('DD.MM.YYYY') + ' по ' + adjusted.end.format('DD.MM.YYYY'));
        }
    }

    let debounceTimeout;
    const calendarObserver = new MutationObserver((mutations) => {
        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(() => {
            markDatesInCalendar();
        }, 100);
    });

    const calendarContainer = document.querySelector('.daterangepicker');
    if (calendarContainer) {
        calendarObserver.observe(calendarContainer, { childList: true, subtree: true, attributes: true });
    } else {
        setTimeout(() => {
            const retryContainer = document.querySelector('.daterangepicker');
            if (retryContainer) {
                calendarObserver.observe(retryContainer, { childList: true, subtree: true, attributes: true });
            }
        }, 1000);
    }

    $('#date-range').on('apply.daterangepicker', async function(ev, picker) {
        let selectedStart = picker.startDate.clone();
        let selectedEnd = picker.endDate.clone();
        const yesterday = moment().subtract(1, 'days');
        if (selectedEnd.isAfter(yesterday)) {
            selectedEnd = yesterday.clone();
            picker.setEndDate(selectedEnd);
        }
        if (selectedStart.isAfter(selectedEnd)) {
            selectedStart = selectedEnd.clone();
            picker.setStartDate(selectedStart);
        }
        const prevStart = daterangepickerInstance.startDate.clone();
        const prevEnd = daterangepickerInstance.endDate.clone();
        const isRollback = selectedStart.isBefore(prevStart);
        await loadMarkedDatesCache();
        const adjusted = await adjustDateRange(selectedStart, selectedEnd, isRollback);
        picker.setStartDate(adjusted.start);
        picker.setEndDate(adjusted.end);
        $('#date-range').val(adjusted.start.format('DD.MM.YYYY') + ' по ' + adjusted.end.format('DD.MM.YYYY'));
        setTimeout(async () => {
            picker.updateCalendars();
            picker.updateView();
            await markDatesInCalendar();
            await updateSelectionMenu();
            await logAction(`Выбор периода: ${adjusted.start.format('DD.MM.YYYY')} по ${adjusted.end.format('DD.MM.YYYY')} для гарнизона ${$('#garnizon-dropdown').text()}`);
        }, 150);
    });

    $('#date-range').on('keydown', async function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const value = $(this).val().trim();
            const parts = value.split(' по ');
            if (parts.length === 2) {
                const startStr = parts[0];
                const endStr = parts[1];
                const newStart = moment(startStr, 'DD.MM.YYYY', true);
                const newEnd = moment(endStr, 'DD.MM.YYYY', true);
                if (newStart.isValid() && newEnd.isValid() && newStart.isSameOrBefore(newEnd)) {
                    const yesterday = moment().subtract(1, 'days');
                    if (newEnd.isAfter(yesterday)) {
                        newEnd = yesterday.clone();
                    }
                    const prevStart = daterangepickerInstance.startDate.clone();
                    const prevEnd = daterangepickerInstance.endDate.clone();
                    const isRollback = newStart.isBefore(prevStart);
                    await loadMarkedDatesCache();
                    const adjusted = await adjustDateRange(newStart, newEnd, isRollback);
                    daterangepickerInstance.setStartDate(adjusted.start);
                    daterangepickerInstance.setEndDate(adjusted.end);
                    $(this).val(adjusted.start.format('DD.MM.YYYY') + ' по ' + adjusted.end.format('DD.MM.YYYY'));
                    await updateSelectionMenu();
                    await logAction(`Ручной ввод периода на Enter: ${adjusted.start.format('DD.MM.YYYY')} по ${adjusted.end.format('DD.MM.YYYY')} для гарнизона ${$('#garnizon-dropdown').text()}`);
                } else {
                    $(this).val(daterangepickerInstance.startDate.format('DD.MM.YYYY') + ' по ' + daterangepickerInstance.endDate.format('DD.MM.YYYY'));
                }
            } else {
                $(this).val(daterangepickerInstance.startDate.format('DD.MM.YYYY') + ' по ' + daterangepickerInstance.endDate.format('DD.MM.YYYY'));
            }
        }
    });

    $('#date-range').on('show.daterangepicker showCalendar.daterangepicker', function() {
        setTimeout(() => markDatesInCalendar(), 0);
    });

    $('.dropdown-menu').on('click', 'a.dropdown-item', async function(event) {
        event.preventDefault();
        const newGarnizonIndex = $(this).data('index').toString();
        const selectedText = $(this).text().toUpperCase();
        if (newGarnizonIndex === selectedGarnizonIndex) return;
        selectedGarnizonIndex = newGarnizonIndex;
        $('#garnizon-dropdown').text(selectedText);
        selectedItems = {};
        initializeSelectedItems();
        markedDatesCache = [];
  
        await updateSelectionMenu();
        await logAction(`Выбор гарнизона для графика: ${selectedText}`);
        setTimeout(() => markDatesInCalendar(), 0);
    });

    $('#chart-container').html('<p>Загрузка данных...</p>');
    initializeEmptyChart();
    initializeEmptyChart('secondaryChart');
    initializeSelectedItems();
    await updateSelectionMenu().then(() => {
        $('#chart-container').find('p').remove();
        setTimeout(() => markDatesInCalendar(), 0);
    }).catch(error => {
        console.error('Ошибка при начальной загрузке:', error);
        $('#selection').html(`<p>Ошибка начальной загрузки данных: ${error.message || 'Неизвестная ошибка'}</p>`);
        $('#chart-container').find('p').remove();
    });
});
</script>
</body>
</html>
