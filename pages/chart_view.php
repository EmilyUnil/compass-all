<?php
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');

$chartMode           = $chartMode ?? 'selector';
$title               = $chartMode === 'svodki' ? 'ГРАФИК СВОДКИ' : 'ГРАФИК СЕЛЕКТОР';
$backPage            = $chartMode === 'svodki' ? 'svodki.php' : 'selector.php';
$apiMode             = $chartMode === 'svodki' ? 'svodki' : 'selector';
$garnizonNames       = [
    '88' => 'МВД', '6' => 'Тирасполь', '5' => 'Бендеры',
    '31' => 'Слободзея', '10' => 'Григориополь', '13' => 'Дубоссары',
    '29' => 'Рыбница', '17' => 'Каменка',
];
$garnizon            = filter_input(INPUT_GET, 'garnizon',     FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '88';
$startDate           = filter_input(INPUT_GET, 'start',        FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? date('d.m.Y', strtotime('-30 days'));
$endDate             = filter_input(INPUT_GET, 'end',          FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? date('d.m.Y', strtotime('-1 day'));
$numberNaim          = filter_input(INPUT_GET, 'number_naim',  FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$initialGarnisonText = strtoupper($garnizonNames[$garnizon] ?? 'ГАРНИЗОН');
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
            min-height: 100vh;
        }
        .header {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
            height: 60px;
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
        /* ── date-selector: те же размеры, что в selector.php / svodki.php ── */
        .date-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            max-width: 1200px;
            margin-bottom: 0;
        }
        .date-selector .centered-input {
            flex: 1;
            max-width: 35%;
            min-width: 300px;
            text-align: center;
        }
        .date-selector #back-button,
        .date-selector button,
        .date-selector .dropdown > button {
            width: 180px;
            height: 50px;
            background-color: #4682B4;
            color: #fff;
            border: 1px solid #4682B4;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            padding: 10px;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
            vertical-align: middle;
            transition: background-color 0.3s ease, border-color 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
        }
        .date-selector #back-button:hover,
        .date-selector button:hover,
        .date-selector .dropdown > button:hover {
            background-color: #fff;
            color: #4682B4;
            border-color: #4682B4;
        }
        .date-selector input {
            padding: 10px;
            border: 1px solid #4682B4;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            height: 50px;
            cursor: pointer;
            text-overflow: ellipsis;
            line-height: 1.5;
            vertical-align: middle;
        }
        .dropdown-toggle::after { display: none !important; }
        .dropdown-menu.show { width: 180px; }
        /* ИИ+ГЕО пара: суммарно 180px как один обычный btn */
        .btn-pair-chart { display: flex; gap: 6px; width: 180px; flex-shrink: 0; }
        .btn-pair-chart button { width: auto !important; flex: 1; font-size: 16px; }
        /* ── Content ── */
        .content {
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            gap: 16px;
            width: 100%;
            max-width: 1200px;
            margin-bottom: 40px;
        }
        .chart-container {
            position: relative;
            flex: 2;
            min-width: 0;
            height: 600px;
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .chart-title {
            text-align: center;
            font-size: 24px;
            margin-bottom: 15px;
            color: #4682B4;
        }
        /* ── Selection checkboxes ── */
        .selection-container {
            flex: 1;
            min-width: 0;
            height: 600px;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 10px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .selection-item {
            margin-bottom: 6px;
            padding: 7px 8px;
            background: #f9f9f9;
            border-radius: 6px;
        }
        .selection-item-sub {
            display: flex;
            align-items: center;
        }
        .selection-color {
            width: 15px;
            height: 15px;
            margin-right: 8px;
            display: inline-block;
            border: 1px solid #aaa;
            flex-shrink: 0;
        }
        .selection-checkbox { margin-right: 8px; cursor: pointer; }
        /* ── Daterangepicker ── */
        .drp-calendar.right { display: none !important; }
        .daterangepicker { min-width: auto !important; z-index: 10000 !important; }
        .calendar-underline { position: absolute; bottom: 2px; left: 50%; width: 50%; height: 2px; transform: translateX(-50%); z-index: 100; }
        /* ── AI modal ── */
        #aiModal .card,
        #aiModal .card:hover,
        #aiModal .card:active {
            transform: none !important;
            transition: none !important;
            border-color: transparent !important;
            box-shadow: none !important;
        }
    </style>
</head>
<body>
<div class="header">
    <h1><?php echo htmlspecialchars($title); ?></h1>
</div>
<div class="container">
    <div class="date-selector">
        <a href="#" id="back-button">НАЗАД</a>
        <div class="dropdown">
            <button class="dropdown-toggle" type="button" id="garnizon-dropdown" data-bs-toggle="dropdown">
                <?php echo htmlspecialchars($initialGarnisonText); ?>
            </button>
            <ul class="dropdown-menu" id="garnizon-menu">
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
        <input type="text" id="date-range" class="centered-input"
               value="<?php echo htmlspecialchars($startDate . ' по ' . $endDate); ?>">
        <div class="btn-pair-chart">
            <button id="ai-button">ИИ</button>
            <?php if ($chartMode === 'svodki'): ?>
            <button id="geo-button">ГЕО</button>
            <?php endif; ?>
        </div>
    </div>
    <div class="content">
        <div class="chart-container">
            <div class="chart-title">Общий график</div>
            <canvas id="myChart"></canvas>
        </div>
        <div id="selection" class="selection-container">
            <p>Загрузка...</p>
        </div>
    </div>
</div>

<!-- AI Modal -->
<div class="modal" id="aiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:#1a2c42;color:#d0d8e4;">
                <h5 class="modal-title" style="font-weight:600;letter-spacing:.3px;">Анализ ИИ — криминогенная обстановка</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background:#f0f2f5;">
                <div id="ai-loading" class="text-center py-5">
                    <div class="spinner-border" style="width:2.5rem;height:2.5rem;color:#1a2c42;"></div>
                    <p class="mt-3" style="color:#4a5568;">Анализирую данные, подождите...</p>
                </div>
                <div id="ai-content" class="d-none"></div>
            </div>
        </div>
    </div>
</div>

<script>
const chartMode     = <?php echo json_encode($apiMode); ?>;
const initNaim      = <?php echo json_encode($numberNaim); ?>;
const garnizonNames = <?php echo json_encode(array_map('strtoupper', $garnizonNames)); ?>;
let selectedGarnizon = <?php echo json_encode($garnizon); ?>;
let chartInstance    = null;
let allDatasets      = [];

// ── Helpers ──────────────────────────────────────────────────────
function escapeHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── AI ───────────────────────────────────────────────────────────
async function callAiProvider(provider, apiKey, prompt) {
    if (provider === 'groq') {
        const r = await fetch('https://api.groq.com/openai/v1/chat/completions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${apiKey}` },
            body: JSON.stringify({ model: 'llama-3.3-70b-versatile', messages: [{ role: 'user', content: prompt }], max_tokens: 1400, temperature: 0.3 })
        });
        const d = await r.json();
        if (d.error) throw new Error(d.error.message || 'Groq error');
        return d?.choices?.[0]?.message?.content || '';
    } else {
        const r = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${apiKey}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ contents: [{ parts: [{ text: prompt }] }], generationConfig: { maxOutputTokens: 1400, temperature: 0.3 } })
        });
        const d = await r.json();
        if (d.error) throw new Error(d.error.message || 'Gemini error');
        return d?.candidates?.[0]?.content?.parts?.[0]?.text || '';
    }
}
function statsHeader(stats) {
    return `<div style="background:#2c3e50;color:#ecf0f1;padding:10px 14px;border-radius:4px;margin-bottom:14px;font-size:.9rem;">
        <strong>Регион:</strong> ${escapeHtml(stats.garnizon||'')} &nbsp;&nbsp;
        <strong>Период:</strong> ${escapeHtml(stats.period||'')} &nbsp;&nbsp;
        <strong>Зарег. преступлений:</strong> ${stats.total||0}
    </div>`;
}
function renderSections(text) {
    const defs = [
        { key: 'АНАЛИЗ ОБСТАНОВКИ',            bg: '#1e3d6e' },
        { key: 'ОСНОВНЫЕ УГРОЗЫ',              bg: '#6b1a24' },
        { key: 'РЕКОМЕНДАЦИИ',                 bg: '#0d4d30' },
        { key: 'ПРОГНОЗ НА СЛЕДУЮЩИЙ ПЕРИОД',  bg: '#7a4000' },
    ];
    let html = '', hasSections = false;
    for (let i = 0; i < defs.length; i++) {
        const { key, bg } = defs[i];
        const tag = `[${key}]`;
        const si  = text.indexOf(tag);
        if (si === -1) continue;
        hasSections = true;
        let ei = text.length;
        for (let j = i + 1; j < defs.length; j++) {
            const ni = text.indexOf(`[${defs[j].key}]`);
            if (ni !== -1) { ei = ni; break; }
        }
        const content = text.substring(si + tag.length, ei).trim();
        html += `<div class="card mb-3" style="border-left:4px solid ${bg}">
            <div class="card-header" style="background:${bg};color:#fff;font-weight:600;">${escapeHtml(key)}</div>
            <div class="card-body" style="white-space:pre-wrap;font-size:.92rem;">${escapeHtml(content)}</div>
        </div>`;
    }
    if (!hasSections) html = `<div style="white-space:pre-wrap;font-size:.92rem;">${escapeHtml(text)}</div>`;
    return html;
}
function renderGarrisonSections(text) {
    const regex = /\[ГАРНИЗОН:\s*([^\]]+)\]/g;
    const matches = [];
    let m;
    while ((m = regex.exec(text)) !== null) matches.push({ name: m[1].trim(), pos: m.index, end: m.index + m[0].length });
    if (!matches.length) return `<div style="white-space:pre-wrap;font-size:.92rem;">${escapeHtml(text)}</div>`;
    let html = '';
    matches.forEach((match, i) => {
        const contentEnd = i + 1 < matches.length ? matches[i + 1].pos : text.length;
        const content = text.substring(match.end, contentEnd).trim();
        html += `<div class="card mb-2">
            <div class="card-header" style="background:#1e3d6e;color:#fff;font-weight:600;">${escapeHtml(match.name)}</div>
            <div class="card-body" style="white-space:pre-wrap;font-size:.92rem;">${escapeHtml(content)}</div>
        </div>`;
    });
    return html;
}

// ── Selection checkboxes ─────────────────────────────────────────
function updateChartVisibility() {
    if (!chartInstance) return;
    // Opt-in: только отмеченные видны; ничего не отмечено — ничего не видно
    const visibleIdx = new Set(
        $('.selection-checkbox:checked').map((_, el) => Number($(el).data('idx'))).get()
    );
    allDatasets.forEach((_, i) => chartInstance.setDatasetVisibility(i, visibleIdx.has(i)));
    chartInstance.update();
}

function buildSelectionMenu(datasets, activeNaim) {
    if (!datasets.length) {
        $('#selection').html('<p>Нет данных для отображения.</p>');
        return;
    }
    let html = '<h4 style="margin-bottom:10px;font-size:1rem;">Выберите показатели:</h4>';
    datasets.forEach((ds, i) => {
        // Чекбокс активен только если пришли по ссылке с конкретным показателем
        const isActive = activeNaim && String(ds.number_naim) === String(activeNaim);
        const color    = ds.borderColor || '#999';
        html += `<div class="selection-item">
            <div class="selection-item-sub">
                <input type="checkbox" class="selection-checkbox" data-idx="${i}" ${isActive ? 'checked' : ''}>
                <div class="selection-color" style="background-color:${color};"></div>
                <span>${escapeHtml(ds.label || '')}</span>
            </div>
        </div>`;
    });
    $('#selection').html(html);
    $('.selection-checkbox').on('change', updateChartVisibility);
}

function applyInitialVisibility(datasets, activeNaim) {
    if (!chartInstance) return;
    // Навигация через кнопку (нет activeNaim): всё скрыто, ждём выбора
    // Навигация через ссылку: только нужный показатель виден
    datasets.forEach((ds, i) => {
        const visible = !!activeNaim && String(ds.number_naim) === String(activeNaim);
        chartInstance.setDatasetVisibility(i, visible);
    });
    chartInstance.update();
}

// ── Chart load ───────────────────────────────────────────────────
function currentGarnizon() {
    return selectedGarnizon || <?php echo json_encode($garnizon); ?>;
}

async function loadChart() {
    const picker = $('#date-range').data('daterangepicker');
    $('#selection').html('<p>Загрузка...</p>');
    try {
        const response = await fetch('../api/get_chart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                mode:       chartMode,
                garnizon:   currentGarnizon(),
                startDate:  picker.startDate.format('YYYY-MM-DD'),
                endDate:    picker.endDate.format('YYYY-MM-DD'),
                numberNaim: null,
            })
        });
        const data = await response.json();
        if (!data.success) {
            $('#selection').html(`<div class="text-danger p-3">${escapeHtml(data.error || 'Ошибка загрузки')}</div>`);
            return;
        }
        allDatasets = data.datasets || [];

        const ctx = document.getElementById('myChart').getContext('2d');
        if (chartInstance) chartInstance.destroy();
        chartInstance = new Chart(ctx, {
            type: 'line',
            data: { labels: data.labels || [], datasets: allDatasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                elements: { line: { tension: 0.4 } },
                scales: {
                    x: { title: { display: true, text: 'Дата', font: { size: 14 } }, ticks: { maxRotation: 45, minRotation: 45 } },
                    y: { title: { display: true, text: 'Количество', font: { size: 14 } }, beginAtZero: true, ticks: { stepSize: 1, precision: 0 } }
                },
                plugins: {
                    legend: { display: true, position: 'top', labels: { font: { size: 12 }, filter: (item) => !item.hidden } },
                    tooltip: {
                        enabled: true,
                        mode: 'nearest',
                        intersect: true,
                        callbacks: {
                            label: ctx => {
                                let l = ctx.dataset.label || '';
                                if (l) l += ': ';
                                if (ctx.parsed.y !== null) l += ctx.parsed.y;
                                return l;
                            }
                        }
                    }
                },
                interaction: { mode: 'nearest', axis: 'xy', intersect: true }
            }
        });

        buildSelectionMenu(allDatasets, initNaim);
        applyInitialVisibility(allDatasets, initNaim);
    } catch (err) {
        $('#selection').html(`<div class="text-danger p-3">Ошибка: ${escapeHtml(err.message)}</div>`);
    }
}

// ── Calendar marking ─────────────────────────────────────────────
const MONTHS_RU = {
    // Русские (если момент переключён на ru)
    'Январь':'01','Февраль':'02','Март':'03','Апрель':'04','Май':'05','Июнь':'06',
    'Июль':'07','Август':'08','Сентябрь':'09','Октябрь':'10','Ноябрь':'11','Декабрь':'12',
    // Английские полные (daterangepicker по умолчанию)
    'January':'01','February':'02','March':'03','April':'04','June':'06',
    'July':'07','August':'08','September':'09','October':'10','November':'11','December':'12',
    // Английские сокращённые
    'Jan':'01','Feb':'02','Mar':'03','Apr':'04','May':'05','Jun':'06',
    'Jul':'07','Aug':'08','Sep':'09','Oct':'10','Nov':'11','Dec':'12',
};

function convertToFullDate(day, monthYear) {
    const parts = monthYear.split(' ');
    const month = MONTHS_RU[parts[0]];
    const year  = parts[1];
    if (!month || !year) return null;
    return `${year}-${month}-${day.padStart(2, '0')}`;
}

function isValidDateInCurrentMonth(date, monthYear) {
    const parts = monthYear.split(' ');
    const month = MONTHS_RU[parts[0]];
    const year  = parts[1];
    if (!month || !year) return false;
    const d = moment(date, 'YYYY-MM-DD');
    return d.isValid() && String(d.month() + 1).padStart(2,'0') === month && String(d.year()) === year;
}

function applyColorsToCalendar(periods) {
    // Убираем старые подчёркивания сразу, красим через 50ms — к тому времени DOM гарантированно обновлён
    $('.drp-calendar tbody td').find('.calendar-underline').remove();
    setTimeout(() => {
        $('.drp-calendar').each(function () {
            const monthYear = $(this).find('.month').text().trim();
            if (!monthYear) return;
            $(this).find('tbody td').each(function () {
                if ($(this).hasClass('week') || !$(this).text().trim()) return;
                const dateStr = convertToFullDate($(this).text().trim(), monthYear);
                if (!dateStr || $(this).hasClass('off') || !isValidDateInCurrentMonth(dateStr, monthYear)) return;
                const cur = moment(dateStr, 'YYYY-MM-DD');
                if (!cur.isValid()) return;
                const hit = periods.find(p => {
                    const ps = moment(p.start, 'YYYY-MM-DD');
                    const pe = moment(p.end,   'YYYY-MM-DD');
                    return ps.isValid() && pe.isValid() && cur.isSameOrAfter(ps) && cur.isSameOrBefore(pe);
                });
                if (hit) {
                    $(this).css({ color: '#000', position: 'relative' })
                           .append(`<span class="calendar-underline" style="background-color:${hit.color};"></span>`);
                }
            });
        });
    }, 50);
}

let markedPeriodsCache = null;

async function markDatesInCalendar() {
    try {
        const apiMode = chartMode === 'svodki' ? 'svodki' : 'selector';
        const res  = await fetch('../api/marked_dates.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ garnizon: currentGarnizon(), mode: apiMode })
        });
        const data = await res.json();
        // svodki returns 'dates', selector returns 'periods' — both are [{start, end, color}]
        const items = data.dates || data.periods || [];
        markedPeriodsCache = items;
        applyColorsToCalendar(items);
    } catch (e) {
        console.error('markDatesInCalendar error:', e);
    }
}

// ── Init ─────────────────────────────────────────────────────────
$(function () {
    const state = CompassState.initFromURL();
    const start = moment(state.startDate || <?php echo json_encode($startDate); ?>, 'DD.MM.YYYY', true);
    const end   = moment(state.endDate   || <?php echo json_encode($endDate);   ?>, 'DD.MM.YYYY', true);

    $('#date-range').daterangepicker({
        startDate:      start.isValid() ? start : moment().subtract(30, 'days'),
        endDate:        end.isValid()   ? end   : moment().subtract(1, 'days'),
        maxDate:        moment().subtract(1, 'days'),
        autoApply:      true,
        linkedCalendars: false,
        locale: {
            format: 'DD.MM.YYYY',
            separator: ' по ',
            monthNames: ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
            daysOfWeek: ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'],
            firstDay: 1
        }
    });

    // Назад
    $('#back-button').on('click', function (e) {
        e.preventDefault();
        window.location.href = CompassState.buildURL(<?php echo json_encode($backPage); ?>);
    });

    // Гарнизон
    $('#garnizon-menu').on('click', 'a.dropdown-item', function () {
        const idx  = String($(this).data('index'));
        selectedGarnizon = idx;
        const name = garnizonNames[idx] || 'ГАРНИЗОН';
        $('#garnizon-dropdown').text(name);
        CompassState.set({ garnizon: idx });
        markedPeriodsCache = null;
        loadChart();
        markDatesInCalendar();
    });

    // Даты
    $('#date-range').on('apply.daterangepicker', function (ev, picker) {
        CompassState.set({ startDate: picker.startDate.format('DD.MM.YYYY'), endDate: picker.endDate.format('DD.MM.YYYY') });
        loadChart();
    });
    // show — календарь ещё рендерится, ждём 250ms
    $('#date-range').on('show.daterangepicker', function () {
        setTimeout(() => {
            if (markedPeriodsCache) applyColorsToCalendar(markedPeriodsCache);
            else markDatesInCalendar();
        }, 250);
    });
    // showCalendar — листание месяца, DOM уже готов
    $('#date-range').on('showCalendar.daterangepicker', function () {
        if (markedPeriodsCache) applyColorsToCalendar(markedPeriodsCache);
        else markDatesInCalendar();
    });

    // ГЕО (только svodki-режим)
    $('#geo-button').on('click', function () {
        const picker = $('#date-range').data('daterangepicker');
        const start  = picker.startDate.format('DD.MM.YYYY');
        const end    = picker.endDate.format('DD.MM.YYYY');
        window.location.href = `geo_view.php?garnizon=${selectedGarnizon}&start=${start}&end=${end}`;
    });

    // ИИ
    $('#ai-button').on('click', async function () {
        const picker   = $('#date-range').data('daterangepicker');
        const aiStart  = picker.startDate.format('YYYY-MM-DD');
        const aiEnd    = picker.endDate.format('YYYY-MM-DD');
        const garnizon = currentGarnizon();
        $('#ai-content').addClass('d-none').html('');
        $('#ai-loading').removeClass('d-none');
        const aiModal = new bootstrap.Modal(document.getElementById('aiModal'));
        aiModal.show();
        try {
            const resp = await fetch('../api/ai_analysis.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ garnizon, start_date: aiStart, end_date: aiEnd, source: chartMode })
            });
            const rawText = await resp.text();
            let prepData;
            try { prepData = JSON.parse(rawText); } catch (_) {
                throw Object.assign(new Error('PHP-ошибка: ' + rawText.substring(0, 200)), { _raw: rawText });
            }
            if (!prepData.success) {
                $('#ai-loading').addClass('d-none');
                $('#ai-content').removeClass('d-none').html(`<div class="alert alert-danger">${escapeHtml(prepData.error || 'Ошибка')}</div>`);
                return;
            }
            if (prepData.mode === 'mvd') {
                const header   = statsHeader(prepData.stats || {});
                const tabsHtml = `${header}
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-brief-ai" type="button">Кратко</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-detail-ai" type="button">Подробнее</button></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane show active" id="tab-brief-ai"><div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div> Загрузка...</div></div>
                    <div class="tab-pane" id="tab-detail-ai"><div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div> Загрузка...</div></div>
                </div>`;
                $('#ai-loading').addClass('d-none');
                $('#ai-content').removeClass('d-none').html(tabsHtml);
                const [briefText, detailText] = await Promise.all([
                    callAiProvider(prepData.provider, prepData.api_key, prepData.prompt_brief),
                    callAiProvider(prepData.provider, prepData.api_key, prepData.prompt_detailed)
                ]);
                $('#tab-brief-ai').html(renderSections(briefText));
                $('#tab-detail-ai').html(renderGarrisonSections(detailText));
            } else {
                const analysis = await callAiProvider(prepData.provider, prepData.api_key, prepData.prompt);
                $('#ai-loading').addClass('d-none');
                $('#ai-content').removeClass('d-none').html(
                    statsHeader(prepData.stats || {}) + renderSections(analysis)
                );
            }
        } catch (err) {
            $('#ai-loading').addClass('d-none');
            let msg = err.message || 'Ошибка';
            if (err._raw) msg += `<br><small style="font-family:monospace">${escapeHtml(err._raw.substring(0, 400))}</small>`;
            $('#ai-content').removeClass('d-none').html(`<div class="alert alert-danger"><strong>Ошибка:</strong> ${msg}</div>`);
        }
    });

    loadChart();
});
</script>
</body>
</html>
