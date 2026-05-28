<?php
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');

$garnizon  = filter_input(INPUT_GET, 'garnizon', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '6';
$startDate = filter_input(INPUT_GET, 'start', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$endDate   = filter_input(INPUT_GET, 'end',   FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$yesterday = date('d.m.Y', strtotime('-1 day'));
if (!$startDate) $startDate = $yesterday;
if (!$endDate)   $endDate   = $yesterday;

$garnizonNames = [
    '88'=>'МВД','6'=>'Тирасполь','5'=>'Бендеры','31'=>'Слободзея',
    '10'=>'Григориополь','13'=>'Дубоссары','29'=>'Рыбница','17'=>'Каменка'
];
$initialGarnizonName = $garnizonNames[$garnizon] ?? 'МВД';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчёты деятельности</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="../assets/js/compass_state.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.1/daterangepicker.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.1/daterangepicker.css">
    <style>
    *{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;}
    body{background-color:#f5f7fa;color:#333;}
    .header{display:flex;justify-content:center;align-items:center;margin-top:20px;position:relative;height:60px;}
    h1{margin:0;text-align:center;color:#4682B4;font-size:36px;font-weight:bold;line-height:1;}
    .main-wrapper{max-width:1600px;margin:0 auto;padding:30px 20px;}
    /* Date selector — sticky как в selector.php */
    .date-selector{position:sticky;top:0;background:#f5f7fa;z-index:999;display:flex;align-items:center;justify-content:center;gap:10px;padding:15px 20px;box-shadow:0 4px 12px rgba(0,0,0,.1);border-bottom:1px solid #dee2e6;flex-wrap:wrap;margin-bottom:20px;}
    .date-selector button,.date-selector .dropdown button{width:180px;height:50px;background-color:#4682B4;color:#fff;border:1px solid #4682B4;font-size:18px;font-weight:600;padding:10px;text-overflow:ellipsis;transition:background-color .3s,border-color .3s;border-radius:8px;box-sizing:border-box;cursor:pointer;}
    .date-selector button:hover,.date-selector .dropdown button:hover{background-color:#fff;color:#4682B4;border-color:#4682B4;}
    .dropdown{position:relative;display:inline-block;}
    .dropdown-menu{position:absolute;top:100%;left:0;z-index:1000;width:180px;padding:5px 0;margin:2px 0 0;font-size:16px;list-style:none;background-color:#fff;border:1px solid #ccc;border-radius:4px;box-shadow:0 6px 12px rgba(0,0,0,.175);}
    .dropdown-item{display:block;width:100%;padding:8px 20px;color:#333;background-color:transparent;border:0;text-decoration:none;cursor:pointer;white-space:nowrap;}
    .dropdown-item:hover{background-color:#f5f5f5;}
    #date-range{padding:10px;border:1px solid #4682B4;font-size:18px;font-weight:600;text-align:center;width:35%;min-width:300px;height:50px;cursor:pointer;border-radius:8px;background-color:#fff;color:#333;transition:all .3s;}
    /* Layout */
    .filter-content-wrapper{display:flex;gap:30px;}
    .sidebar{width:300px;background:#fff;border-radius:8px;padding:25px;box-shadow:0 2px 8px rgba(0,0,0,.08);height:fit-content;position:sticky;top:90px;}
    .sidebar h3{color:#4682B4;font-size:14px;font-weight:700;margin-bottom:16px;text-transform:uppercase;letter-spacing:.5px;}
    .sidebar hr{border:none;border-top:2px solid #4682B4;margin:20px 0;opacity:.2;}
    .search-box input{width:100%;padding:12px 16px;border:2px solid #d0d8e0;border-radius:8px;font-size:14px;transition:all .3s;}
    .search-box input:focus{outline:none;border-color:#4682B4;box-shadow:0 0 0 3px rgba(70,130,180,.1);}
    .filter-section{margin-bottom:18px;}
    .filter-section h4{color:#4682B4;font-size:12px;font-weight:700;text-transform:uppercase;margin-bottom:10px;letter-spacing:.5px;}
    .filter-section select{width:100%;padding:10px 12px;border:1px solid #d0d8e0;border-radius:6px;font-size:13px;background:#fff;color:#333;cursor:pointer;}
    .filter-section select:focus{outline:none;border-color:#4682B4;}
    .sidebar-buttons{display:flex;gap:10px;margin-top:20px;}
    .btn-sidebar{flex:1;padding:12px 16px;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:all .3s;text-transform:uppercase;}
    .btn-reset{background:#fff;color:#4682B4;border:1px solid #4682B4;}
    .btn-reset:hover{background:#4682B4;color:#fff;}
    .btn-apply{background:#4682B4;color:#fff;}
    .btn-apply:hover{background:#fff;color:#4682B4;border:2px solid #4682B4;}
    /* Cards */
    .reports-by-date{margin-bottom:40px;}
    .date-header{color:#4682B4;font-size:18px;font-weight:700;margin-bottom:15px;padding-bottom:10px;border-bottom:2px solid #4682B4;}
    .report-type-label{color:#999;font-size:12px;font-weight:600;text-transform:uppercase;margin:8px 0;padding-left:5px;letter-spacing:.5px;}
    .report-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;margin-bottom:20px;}
    .report-card{background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.08);border-left:4px solid #4682B4;transition:all .3s;position:relative;overflow:hidden;height:120px;width:300px;}
    .report-card:hover{box-shadow:0 8px 16px rgba(0,0,0,.12);transform:translateY(-4px);}
    .report-card a{text-decoration:none;color:inherit;display:block;height:100%;position:relative;z-index:2;}
    .report-card h3{color:#4682B4;margin-bottom:8px;font-size:16px;font-weight:600;transition:color .4s;}
    .report-card p{color:#666;font-size:14px;line-height:1.5;margin:0;transition:color .4s;}
    .report-card:hover h3,.report-card:hover p{color:#fff;}
    .report-card .ripple{position:absolute;top:0;left:0;width:0;height:0;border-radius:50%;background:#4682B4;transform:translate(-50%,-50%);transition:width .6s ease-out,height .6s ease-out;pointer-events:none;z-index:1;}
    .report-card:hover .ripple{width:800px;height:800px;}
    .no-reports{grid-column:1/-1;text-align:center;padding:50px;color:#999;font-size:18px;}
    .loading{text-align:center;padding:40px;color:#4682B4;font-size:16px;}
    .calendar-underline{position:absolute;bottom:2px;left:50%;width:50%;height:2px;transform:translateX(-50%);}
    @media(max-width:1200px){.filter-content-wrapper{flex-direction:column;}.sidebar{width:100%;position:static;}}
    @media(max-width:650px){.date-selector{flex-wrap:wrap;} #date-range{min-width:100%;}}
    .drp-calendar.right { display:none !important; }
    .daterangepicker { min-width:auto !important; }
    </style>
</head>
<body>

<div class="header">
    <div class="header-content"><h1>ОТЧЁТЫ ДЕЯТЕЛЬНОСТИ</h1></div>
</div>

<div class="date-selector">
    <button id="back-button">← НАЗАД</button>
    <div class="dropdown">
        <button type="button" id="garnizon-dropdown"><?php echo htmlspecialchars($initialGarnizonName); ?></button>
        <ul class="dropdown-menu" style="display:none;">
            <?php foreach ($garnizonNames as $k => $n): ?>
                <li><a class="dropdown-item" href="javascript:void(0);" data-index="<?php echo $k; ?>"><?php echo $n; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <input type="text" id="date-range" placeholder="Выберите период">
</div>

<div class="main-wrapper">
    <div class="filter-content-wrapper">
        <aside class="sidebar">
            <h3>🔍 Поиск</h3>
            <div class="search-box" style="margin-bottom:20px;">
                <input type="text" id="search-input" placeholder="Поиск по отчётам...">
            </div>
            <hr>
            <h3>⚙️ Фильтры</h3>
            <div class="filter-section">
                <h4>Тип отчёта</h4>
                <select id="period">
                    <option value="">Все</option>
                    <option value="daily">Ежедневный</option>
                    <option value="weekly">Еженедельный</option>
                </select>
            </div>
            <div class="sidebar-buttons">
                <button class="btn-sidebar btn-reset" onclick="resetFilters()">Сбросить</button>
                <button class="btn-sidebar btn-apply" onclick="applyFilters()">Применить</button>
            </div>
        </aside>

        <div style="flex:1;">
            <div id="reports-container">
                <div class="loading">Загрузка отчётов…</div>
            </div>
        </div>
    </div>
</div>

<script>
// ── CompassState ───────────────────────────────────────────────────────────
const _csState = CompassState.initFromURL();
let selectedGarnizonIndex = _csState.garnizon || <?php echo json_encode($garnizon); ?>;
let selectedStartDate = null;
let selectedEndDate   = null;

const garnizonNames = {
    '88':'МВД','6':'Тирасполь','5':'Бендеры','31':'Слободзея',
    '10':'Григориополь','13':'Дубоссары','29':'Рыбница','17':'Каменка'
};
const baseGarnizonIds = ['6','5','31','10','13','29','17'];
const daysRU = ['Воскресенье','Понедельник','Вторник','Среда','Четверг','Пятница','Суббота'];

// ── Инициализация ────────────────────────────────────────────────────────────
$(document).ready(function() {
    const yesterday = moment().subtract(1,'days');

    const _stateS = CompassState.get();
    const initStart = _stateS.startDate ? moment(_stateS.startDate, 'DD.MM.YYYY') : moment('<?php echo $startDate; ?>', 'DD.MM.YYYY');
    const initEnd   = _stateS.endDate   ? moment(_stateS.endDate,   'DD.MM.YYYY') : moment('<?php echo $endDate; ?>',   'DD.MM.YYYY');
    selectedStartDate = initStart.isValid() ? initStart : yesterday;
    selectedEndDate   = initEnd.isValid()   ? initEnd   : yesterday;

    selectedGarnizonIndex = CompassState.get().garnizon || selectedGarnizonIndex;
    $('#garnizon-dropdown').text(garnizonNames[selectedGarnizonIndex] || 'МВД');

    // DateRangePicker
    $('#date-range').daterangepicker({
        startDate: selectedStartDate, endDate: selectedEndDate,
        maxDate: yesterday,
        locale: { format:'DD.MM.YYYY', separator:' по ', applyLabel:'Применить', cancelLabel:'Отмена',
            monthNames:['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
            daysOfWeek:['Вс','Пн','Вт','Ср','Чт','Пт','Сб'], firstDay:1 },
        opens:'center', autoApply:true, linkedCalendars:false,
        isInvalidDate: d => d.isSameOrAfter(moment(), 'day')
    });
    $('#date-range').val(selectedStartDate.format('DD.MM.YYYY') + ' по ' + selectedEndDate.format('DD.MM.YYYY'));

    $('#date-range').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('DD.MM.YYYY') + ' по ' + picker.endDate.format('DD.MM.YYYY'));
        selectedStartDate = picker.startDate;
        selectedEndDate   = picker.endDate;
        CompassState.set({ startDate: picker.startDate.format('DD.MM.YYYY'), endDate: picker.endDate.format('DD.MM.YYYY') });
        loadAvailableReports();
    });
    $('#date-range').on('show.daterangepicker showCalendar.daterangepicker', () => setTimeout(markDatesInCalendar, 200));

    // Dropdown гарнизон
    $(document).on('click', '.dropdown-item[data-index]', function(e) {
        e.preventDefault();
        selectedGarnizonIndex = String($(this).data('index'));
        CompassState.set({ garnizon: selectedGarnizonIndex });
        $('#garnizon-dropdown').text($(this).text());
        $(this).closest('.dropdown-menu').hide();
        loadAvailableReports();
    });

    $('#garnizon-dropdown').on('click', function(e) {
        e.stopPropagation();
        const $menu = $(this).siblings('.dropdown-menu');
        $menu.is(':visible') ? $menu.hide() : $menu.show();
    });
    $(document).on('click', function() { $('.dropdown-menu').hide(); });

    $('#back-button').on('click', () => window.location.href = CompassState.buildURL('../index.php'));

    // Поиск
    $('#search-input').on('input', filterBySearch);
    $('#period').on('change', applyFilters);

    loadAvailableReports();
});

// ── Загрузка отчётов ─────────────────────────────────────────────────────────
async function loadAvailableReports() {
    if (!selectedStartDate || !selectedEndDate) return;
    $('#reports-container').html('<div class="loading">Загрузка…</div>');

    const garnizonIds = selectedGarnizonIndex === '88' ? [...baseGarnizonIds] : [selectedGarnizonIndex];
    const periodFilter = $('#period').val();
    let allReports = [];

    try {
        // Загружаем суточные (svodki)
        if (!periodFilter || periodFilter === 'daily') {
            const dailyData = await loadReportData('../api/marked_dates.php', garnizonIds, 'daily');
            allReports = allReports.concat(dailyData);
        }
        // Загружаем еженедельные (selector)
        if (!periodFilter || periodFilter === 'weekly') {
            const weeklyData = await loadReportData('../api/marked_dates.php', garnizonIds, 'weekly');
            allReports = allReports.concat(weeklyData);
        }

        // Фильтр по датам
        allReports = allReports.filter(r =>
            !r.periodEnd.isBefore(selectedStartDate) && !r.periodStart.isAfter(selectedEndDate)
        );

        allReports.sort((a, b) => b.startDate - a.startDate);
        displayReports(allReports);
        markDatesInCalendar();
    } catch(err) {
        console.error('loadAvailableReports error:', err);
        $('#reports-container').html('<div class="no-reports">Ошибка загрузки данных.</div>');
    }
}

async function loadReportData(url, garnizonIds, reportType) {
    const reports = [];
    const mode    = reportType === 'daily' ? 'svodki' : 'selector';

    for (const garnizonId of garnizonIds) {
        try {
            const res  = await fetch(url, {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ garnizon: garnizonId, mode })
            });
            if (!res.ok) continue;
            const data = await res.json();
            const periods = data.periods || data.dates || [];

            periods.forEach(item => {
                const pStart = moment(item.start, 'YYYY-MM-DD');
                const pEnd   = moment(item.end,   'YYYY-MM-DD');
                if (!pStart.isValid() || !pEnd.isValid()) return;

                const garName  = garnizonNames[garnizonId] || garnizonId;
                const periodTxt= pStart.format('DD.MM.YYYY') + (pStart.isSame(pEnd,'day') ? '' : ' - ' + pEnd.format('DD.MM.YYYY'));
                const linkFile = reportType === 'daily' ? 'svodki.php' : 'selector.php';
                const title    = reportType === 'daily'
                    ? `Сводка ${periodTxt} — ${garName}`
                    : `Селектор ${periodTxt} — ${garName}`;

                reports.push({
                    garnizonId, title,
                    description: reportType === 'daily' ? 'Ежедневный отчёт' : 'Еженедельный отчёт',
                    start: pStart.format('DD.MM.YYYY'), end: pEnd.format('DD.MM.YYYY'),
                    startDate: pStart, linkFile, reportType,
                    periodStart: pStart, periodEnd: pEnd
                });
            });
        } catch(e) { console.error('loadReportData error:', e); }
    }
    return reports;
}

// ── Отображение ──────────────────────────────────────────────────────────────
function displayReports(reports) {
    const $container = $('#reports-container');
    $container.empty();

    const searchVal = $('#search-input').val().toLowerCase();
    const filtered  = searchVal ? reports.filter(r => r.title.toLowerCase().includes(searchVal)) : reports;

    if (!filtered.length) {
        $container.html('<div class="no-reports">Нет отчётов за выбранный период.</div>');
        return;
    }

    const byDate = {};
    filtered.forEach(r => {
        const key = r.startDate.format('YYYY-MM-DD');
        if (!byDate[key]) byDate[key] = { daily:[], weekly:[] };
        byDate[key][r.reportType].push(r);
    });

    Object.keys(byDate).sort((a,b) => b.localeCompare(a)).forEach(dateKey => {
        const d   = moment(dateKey, 'YYYY-MM-DD');
        const $sec= $(`<div class="reports-by-date">
            <div class="date-header">${d.format('DD.MM.YYYY')} (${daysRU[d.day()]})</div>
        </div>`);

        ['daily','weekly'].forEach(type => {
            if (!byDate[dateKey][type].length) return;
            const label = type === 'daily' ? 'Ежедневные отчёты' : 'Еженедельные отчёты';
            $sec.append(`<div class="report-type-label">${label}</div>`);
            const $grid = $('<div class="report-grid"></div>');
            byDate[dateKey][type].forEach(r => {
                $grid.append(`
                    <div class="report-card">
                        <span class="ripple"></span>
                        <a href="${r.linkFile}?garnizon=${r.garnizonId}&start=${r.start}&end=${r.end}">
                            <h3>${r.title}</h3>
                            <p>${r.description}</p>
                        </a>
                    </div>`);
            });
            $sec.append($grid);
        });
        $container.append($sec);
    });

    // Ripple effect
    $(document).on('mouseenter', '.report-card', function(e) {
        const o = $(this).offset();
        $(this).find('.ripple').css({ left: e.pageX - o.left, top: e.pageY - o.top });
    });
}

// ── Даты в календаре ─────────────────────────────────────────────────────────
async function markDatesInCalendar() {
    if (!$('.drp-calendar').length) return;
    const garnizonIds = selectedGarnizonIndex === '88' ? [...baseGarnizonIds, '88'] : [selectedGarnizonIndex];
    const modes = ['svodki','selector'];
    const allDates = [];

    for (const mode of modes) {
        for (const g of garnizonIds) {
            try {
                const res  = await fetch('../api/marked_dates.php', {
                    method:'POST', headers:{'Content-Type':'application/json'},
                    body: JSON.stringify({ garnizon: g, mode })
                });
                const data = await res.json();
                const periods = data.periods || data.dates || [];
                periods.forEach(item => {
                    let cur = moment(item.start,'YYYY-MM-DD');
                    const end = moment(item.end,'YYYY-MM-DD');
                    while (cur.isSameOrBefore(end)) {
                        allDates.push({ date: cur.format('YYYY-MM-DD'), color: item.color || '#4682B4' });
                        cur.add(1,'days');
                    }
                });
            } catch(e) {}
        }
    }

    const dateMap = new Map();
    allDates.forEach(d => { if (!dateMap.has(d.date)) dateMap.set(d.date, d.color); });

    setTimeout(() => {
        $('.drp-calendar tbody td').each(function() {
            if (!$(this).text().trim()) return;
            const day = $(this).text().trim();
            const my  = $(this).closest('.drp-calendar').find('.month').text().trim();
            const months = {'Январь':'01','Февраль':'02','Март':'03','Апрель':'04','Май':'05','Июнь':'06','Июль':'07','Август':'08','Сентябрь':'09','Октябрь':'10','Ноябрь':'11','Декабрь':'12'};
            const [mn, yr] = my.split(' ');
            const dateStr = `${yr}-${months[mn]}-${day.padStart(2,'0')}`;
            if (dateMap.has(dateStr)) {
                $(this).css('position','relative').find('.calendar-underline').remove();
                $(this).append(`<span class="calendar-underline" style="background-color:${dateMap.get(dateStr)};"></span>`);
            }
        });
    }, 300);
}

function filterBySearch() { loadAvailableReports(); }
function applyFilters()   { loadAvailableReports(); }
function resetFilters() {
    $('#period').val('');
    $('#search-input').val('');
    loadAvailableReports();
}
</script>
</body>
</html>
