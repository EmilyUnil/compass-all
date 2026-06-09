<?php
// Упрощённый заголовок без зависимости от БД
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');

$garnizon = filter_input(INPUT_GET, 'garnizon', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null;
$startDate = filter_input(INPUT_GET, 'start', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$endDate   = filter_input(INPUT_GET, 'end', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$yesterday = date('d.m.Y', strtotime('-1 day'));
if (!$startDate || !preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $startDate)) $startDate = $yesterday;
if (!$endDate   || !preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $endDate))   $endDate   = $yesterday;

$garnizonNames = [
    '88' => 'МВД','6' => 'ТИРАСПОЛЬ','5' => 'БЕНДЕРЫ','31' => 'СЛОБОДЗЕЯ',
    '10' => 'ГРИГОРИОПОЛЬ','13' => 'ДУБОССАРЫ','29' => 'РЫБНИЦА','17' => 'КАМЕНКА'
];
$initialGarnizonIndex = ($garnizon !== null && array_key_exists($garnizon, $garnizonNames)) ? $garnizon : '6';
$initialGarnisonText  = $garnizonNames[$initialGarnizonIndex] ?? 'ГАРНИЗОН';
$userAccessLevel = '3'; // По умолчанию разрешаем редактирование (без сессий)
$canViewAllGarrisons = true; // Показываем все гарнизоны (без сессий)
// URL-параметры перебивают PHP-дефолты (будет перезаписано JS из CompassState)

// Список типов происшествий (был из БД — теперь статический)
$incidentTypes = [
    ['numb_nazv' => '1', 'nazv' => 'Убийство'],
    ['numb_nazv' => '2', 'nazv' => 'Тяжкий вред здоровью'],
    ['numb_nazv' => '3', 'nazv' => 'Изнасилование'],
    ['numb_nazv' => '4', 'nazv' => 'Кража'],
    ['numb_nazv' => '5', 'nazv' => 'Грабёж'],
    ['numb_nazv' => '6', 'nazv' => 'Разбой'],
    ['numb_nazv' => '7', 'nazv' => 'Мошенничество'],
    ['numb_nazv' => '8', 'nazv' => 'Хулиганство'],
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сводка</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="../assets/js/compass_state.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="../assets/js/daterangepicker.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="../assets/css/style_sel_deg.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.1/daterangepicker.css">
    <style>
.container {
padding-bottom: 30px;
    max-width: 1200px;
    margin: 0 auto;
}
        .header {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
margin-bottom: 0px;
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
        #type_proicsh_menu .dropdown-item {
            white-space: normal;
            word-wrap: break-word;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        #type_proicsh_btn {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .date-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 999;
            padding: 15px 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-bottom: 1px solid #dee2e6;
        }

    /* Баннер режима "только просмотр" */
    #readonly-banner {
        display: none;
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 6px;
        padding: 8px 16px;
        margin: 8px 20px 0;
        font-size: 14px;
        font-weight: 600;
        color: #856404;
        text-align: center;
    }
    #readonly-banner.show { display: block; }

        .btn-back {
            margin-right: 10px;
        }
        .button-group {
            display: flex;
            gap: 10px;
        }
        .button-group .btn {
    width: 180px;
    height: 50px;
    background-color: #78858B;
    color: #fff;
    border: 1px solid #78858B;
    font-size: 18px;
    font-weight: 600;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 15px;
    display: none;
    transition: background-color 0.3s ease, border-color 0.3s ease;
        }
        .button-group .btn:hover {
background-color: #fff;
    color: #78858B;
    border: 1px solid #78858B;
}
.naimenov a {
    color: #000000 !important;
    text-decoration: none;
}
.naimenov a:hover {
    text-decoration: underline;
}
.form-switch {
  padding-left: 3em;
}
.svodki-split-inputs {
    display: flex;
    align-items: center;
    gap: 6px;
}
.svodki-split-inputs .split-part {
    min-width: 0;
    text-align: center;
}
.split-separator {
    font-weight: 700;
}
.drp-calendar.right { display:none !important; }
.daterangepicker { min-width:auto !important; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>СВОДКА</h1>
        </div>
    </div>
    <div class="container mt-3" id="main-content">
        <div class="date-selector">
            <button class="btn btn-primary btn-back" id="back-button" onclick="window.location.href='../index.php'">НАЗАД</button>
            <div class="dropdown">
                <button class="btn btn-primary <?php if (!$canViewAllGarrisons) echo 'dropdown-toggle'; ?>"
                        type="button"
                        id="garnizon-dropdown"
                        <?php if (!$canViewAllGarrisons) echo 'disabled'; ?>
                        <?php if ($canViewAllGarrisons) echo 'data-bs-toggle="dropdown"'; ?>>
                    <?php echo htmlspecialchars($initialGarnisonText); ?>
                </button>
                <?php if ($canViewAllGarrisons) { ?>
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
                <?php } ?>
            </div>
            <input type="text" id="date-range" class="form-control" placeholder="Выберите период">
            <button id="show-button" class="btn btn-primary">ГРАФИК</button>
            <button id="ai-button" class="btn btn-primary">ИИ</button>
        </div>
        <div class="table-container">
            <div class="table-column" id="pole2-table">
                <table id="data-table-pole2" class="table table-striped table-bordered">
                    <thead></thead>
                    <tbody id="results-table-body-pole2"></tbody>
                </table>
            </div>
            <div class="table-column" id="pole1-table">
                <table id="data-table-pole1" class="table table-striped table-bordered">
                    <thead></thead>
                    <tbody id="results-table-body-pole1"></tbody>
                </table>
            </div>
        </div>
        <div class="save-button-container"></div>
        <div id="access-denied-message" style="display: none; padding: 20px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;">
            Нет доступа к этому разделу.
        </div>
        <div class="additional-section" id="additional-input-section" style="display: none;">
            <div class="header">ПОДРОБНАЯ ИНФОРМАЦИЯ</div>
            <div class="additional-input-container">
                <span class="readonly" id="garnizon-display"><?php echo htmlspecialchars($initialGarnisonText); ?></span>
                <div class="dropdown">
                    <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" id="type_proicsh_btn">ИНЦИДЕНТ</button>
                    <ul class="dropdown-menu" id="type_proicsh_menu">
                        <?php foreach ($incidentTypes as $type) { ?>
                            <li><a class="dropdown-item" href="javascript:void(0);"
                                   data-numb="<?php echo htmlspecialchars($type['numb_nazv']); ?>"
                                   data-text="<?php echo htmlspecialchars($type['nazv']); ?>"
                                   title="<?php echo htmlspecialchars($type['nazv']); ?>">
                                <?php echo htmlspecialchars($type['nazv']); ?>
                            </a></li>
                        <?php } ?>
                    </ul>
                </div>
                <input type="text" id="number-input" placeholder="НОМЕР">
                <span class="readonly" id="date-display"></span>
            </div>
            <div class="mb-3">
                <textarea class="form-control" id="exampleFormControlTextarea1" rows="3" placeholder="Введите сообщение"></textarea>
            </div>
            <div class="button-group">
                <button id="additional-save-button" class="btn btn-primary" style="display: none;">СОХРАНИТЬ</button>
                <button id="additional-cancel-button" class="btn btn-secondary" style="display: none;">ОТМЕНА</button>
            </div>
        </div>
        <div id="toggle-section" class="mt-3" style="display: none;">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="show-all-toggle">
                <label class="form-check-label" for="show-all-toggle">Показывать все фабулы</label>
            </div>
        </div>
        <div class="cards-container mt-4" id="incident-cards"></div>
    </div>
<script>

        // ── CompassState ───────────────────────────────────────────────────────
        const _csState = CompassState.initFromURL();
let selectedGarnisonIndex = _csState.garnizon || "<?php echo htmlspecialchars($initialGarnizonIndex); ?>";
let accessLevel = "<?php echo htmlspecialchars($userAccessLevel); ?>";
let userGarnizon = "";
let isEditableGlobal = false;
let isButtonClicked = false;
const garnisonNames = {
    '88': 'МВД',
    '6': 'ТИРАСПОЛЬ',
    '5': 'БЕНДЕРЫ',
    '31': 'СЛОБОДЗЕЯ',
    '10': 'ГРИГОРИОПОЛЬ',
    '13': 'ДУБОССАРЫ',
    '29': 'РЫБНИЦА',
    '17': 'КАМЕНКА'
};
function applyGarnizonDropdownScope() {
    $('.date-selector .dropdown-menu li').show();
}
async function checkAccess(action, userLevel, userGarnizon, targetGarnizon, startDate = null, endDate = null) {
    try {
        const payload = {
            action,
            userLevel,
            garrison: userGarnizon,
            targetGarrison: targetGarnizon
        };
        if (startDate) payload.start_date = startDate;
        if (endDate) payload.end_date = endDate;
        const response = await fetch('../api/stub.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(payload)
        });
        const data = await response.json();
        return data;
    } catch (error) {
        logAction(`Ошибка проверки доступа для ${action}: ${error.message}`);
        console.error(`Ошибка проверки доступа для ${action}:`, error);
        return { success: false, access: false, error: error.message };
    }
}
async function logAction(action) {
    try {
        const response = await fetch('../api/stub.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: action })
        });
        const data = await response.json();
        return data;
    } catch (error) {
        console.warn('Ошибка при логировании:', error.message);
        return { success: false, error: error.message };
    }
}
$(document).ready(async function() {
    try {
        // Проверяем базовый доступ
        const initialAccessCheck = await checkAccess('view_svodki', accessLevel, userGarnizon, selectedGarnisonIndex);
        if (!initialAccessCheck.success || !initialAccessCheck.access) {
            $('#main-content').html('<div style="padding: 20px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;">У вас недостаточно прав для доступа к этому разделу.</div>');
            await logAction('Попытка доступа к СВОДКАМ без прав');
            return;
        }
        // Инициализируем интерфейс
        const initialGarnizonText = garnisonNames[selectedGarnisonIndex] || '<?php echo htmlspecialchars($initialGarnisonText); ?>';
        $('#garnizon-dropdown').text(initialGarnizonText);
        $('#garnizon-display').text(initialGarnizonText);
        applyGarnizonDropdownScope();
$('#back-button').on('click', function() {
    if (document.referrer && document.referrer.indexOf('report.php') !== -1) {
        window.location.href = CompassState.buildURL('../index.php');
    } else {
        window.location.href = CompassState.buildURL('../index.php');
    }
});
        $('#date-range').daterangepicker({
            startDate: _csState.startDate || '<?php echo htmlspecialchars($startDate); ?>',
            endDate: _csState.endDate || '<?php echo htmlspecialchars($endDate); ?>',
            maxDate: moment().subtract(1, 'days'),
            locale: {
                format: 'DD.MM.YYYY',
                separator: ' по ',
                monthNames: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
                            'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
                daysOfWeek: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
                firstDay: 1,
                applyLabel: 'Применить',
                cancelLabel: 'Отмена',
                customRangeLabel: 'Выбрать даты'
            },
            opens: 'center',
            autoUpdateInput: true,
            linkedCalendars: false,
            autoApply: true,
            isInvalidDate: function(date) {
                return date.isSame(moment(), 'day') || date.isAfter(moment(), 'day');
            }
        });
        $('#date-display').text( $('#date-range').data('daterangepicker').startDate.format('DD.MM.YYYY') );
        // Initial load
        const initialStart = moment(_csState.startDate || '<?php echo $startDate; ?>', 'DD.MM.YYYY');
        const initialEnd = moment(_csState.endDate || '<?php echo $endDate; ?>', 'DD.MM.YYYY');
        CompassState.set({
            garnizon: selectedGarnisonIndex,
            startDate: initialStart.format('DD.MM.YYYY'),
            endDate: initialEnd.format('DD.MM.YYYY')
        });
        const initialWeeks = splitIntoWeeks(initialStart, initialEnd);
        await showSelectedDates(initialWeeks);
        toggleToggleSection();
    $('#show-button').on('click', async function() {
        const startDate = $('#date-range').data('daterangepicker').startDate.format('DD.MM.YYYY');
        const endDate = $('#date-range').data('daterangepicker').endDate.format('DD.MM.YYYY');
        const garnizon = selectedGarnisonIndex;
        const accessResponse = await checkAccess('show_chart', accessLevel, userGarnizon, garnizon);
        if (!accessResponse.success || !accessResponse.access) {
            alert('Нет доступа к графику.');
            return;
        }
        window.location.href = `chart_sel_deg.php?garnizon=${garnizon}&start=${startDate}&end=${endDate}`;
        logAction(`Переход к графику для гарнизона ${$('#garnizon-dropdown').text()} за ${startDate} - ${endDate}`);
    });
    $('#ai-button').on('click', async function() {
        const accessResponse = await checkAccess('ai_button', accessLevel, userGarnizon, selectedGarnisonIndex);
        if (!accessResponse.success || !accessResponse.access) {
            alert('Нет доступа к ИИ.');
            return;
        }
        const picker = $('#date-range').data('daterangepicker');
        const aiStart = picker ? picker.startDate.format('YYYY-MM-DD') : '';
        const aiEnd   = picker ? picker.endDate.format('YYYY-MM-DD')   : '';
        $('#ai-content').addClass('d-none').html('');
        $('#ai-loading').removeClass('d-none');
        const aiModal = new bootstrap.Modal(document.getElementById('aiModal'));
        aiModal.show();
        try {
            // Шаг 1: получаем данные и ключ с сервера
            const resp    = await fetch('../api/ai_analysis.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ garnizon: selectedGarnisonIndex ?? '', start_date: aiStart, end_date: aiEnd, source: 'svodki' })
            });
            const rawText = await resp.text();
            let prepData;
            try { prepData = JSON.parse(rawText); } catch (_) {
                const e = new Error('Сервер вернул не-JSON ответ (возможно PHP-ошибка)');
                e._rawText = rawText;
                throw e;
            }
            if (!prepData.success) {
                $('#ai-loading').addClass('d-none');
                $('#ai-content').removeClass('d-none').html(`<div class="alert alert-danger">${escapeHtml(prepData.error || 'Ошибка')}</div>`);
                return;
            }

            // Шаг 2: вызываем AI API из браузера
            if (prepData.mode === 'mvd') {
                // МВД: две вкладки — кратко и подробнее
                const header = statsHeader(prepData.stats || {});
                const tabsHtml = `${header}
                <ul class="nav nav-tabs mb-3" id="aiNavTabs">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-brief-ai" type="button">Кратко</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-detail-ai" type="button">Подробнее</button></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane show active" id="tab-brief-ai"><div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div> Загрузка...</div></div>
                    <div class="tab-pane" id="tab-detail-ai"><div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div> Загрузка...</div></div>
                </div>`;
                $('#ai-loading').addClass('d-none');
                $('#ai-content').removeClass('d-none').html(tabsHtml);

                // Параллельные запросы к AI
                const [briefText, detailText] = await Promise.all([
                    callAiProvider(prepData.provider, prepData.api_key, prepData.prompt_brief),
                    callAiProvider(prepData.provider, prepData.api_key, prepData.prompt_detailed)
                ]);
                $('#tab-brief-ai').html(renderSections(briefText));
                $('#tab-detail-ai').html(renderGarrisonSections(detailText));
            } else {
                // Конкретный гарнизон: один анализ
                const analysis = await callAiProvider(prepData.provider, prepData.api_key, prepData.prompt);
                $('#ai-loading').addClass('d-none');
                $('#ai-content').removeClass('d-none').html(renderAiAnalysis({ analysis, stats: prepData.stats }));
            }
        } catch (err) {
            $('#ai-loading').addClass('d-none');
            let errMsg = err.message || 'Неизвестная ошибка';
            if (err._rawText) errMsg += `<br><small style="font-family:monospace;">${escapeHtml(err._rawText.substring(0, 500))}</small>`;
            $('#ai-content').removeClass('d-none').html(`<div class="alert alert-danger"><strong>Ошибка:</strong> ${errMsg}</div>`);
        }
        logAction('Нажатие кнопки ИИ');
    });
    $('#additional-cancel-button').on('click', function() {
        $('#number-input').val('').removeData('id_zapisi');
        $('#exampleFormControlTextarea1').val('');
        $('#type_proicsh_btn')
            .text('ИНЦИДЕНТ')
            .css('color', '#6c757d')
            .removeAttr('data-selected')
            .removeAttr('title');
        $('#additional-save-button').hide();
        $('#additional-cancel-button').hide();
        logAction('Очистка формы дополнительной информации');
    });
    // Патч updateCalendars: перерисовываем подсветку периодов при смене месяца
    const _origUpdateCals = $.fn.daterangepicker.prototype.updateCalendars;
    $.fn.daterangepicker.prototype.updateCalendars = function() {
        _origUpdateCals.apply(this, arguments);
        setTimeout(() => markDatesInCalendar(), 100);
    };

    $('#date-range').on('show.daterangepicker', function(ev, picker) {
        setTimeout(() => {
            $('.drp-calendar tbody td').each(function() {
                let dateText = $(this).text().trim();
                let monthYear = $(this).closest('.drp-calendar').find('.month').text().trim();
                let formattedDate = convertToFullDate(dateText, monthYear);
                if (moment(formattedDate, "YYYY-MM-DD").isSame(moment(), 'day')) {
                    $(this).css({
                        'text-decoration': 'line-through',
                        'color': '#999'
                    }).addClass('disabled');
                }
            });
            markDatesInCalendar();
        }, 200);
    });
    $('#date-range').on('showCalendar.daterangepicker', function(ev, picker) {
        setTimeout(() => {
            markDatesInCalendar();
        }, 200);
    });
    $('#date-range').on('apply.daterangepicker', async function(ev, picker) {
        let startDate = picker.startDate;
        let endDate = picker.endDate;
        
        if (startDate.isAfter(endDate)) {
            alert('Дата начала не может быть больше даты окончания');
            picker.setStartDate(endDate);
            picker.setEndDate(startDate);
            return;
        }
        if (endDate.isAfter(moment().subtract(1, 'days'))) {
            alert('Выбранный период не может включать текущий день');
            picker.setEndDate(moment().subtract(1, 'days'));
            return;
        }
        $('#date-display').text(startDate.format('DD.MM.YYYY'));
        let selectedText = $('#garnizon-dropdown').text();
        await logAction(`Выбор периода: ${startDate.format('DD.MM.YYYY')} - ${endDate.format('DD.MM.YYYY')} для гарнизона ${selectedText}`);
        let markedDates = [];
        if (selectedGarnisonIndex !== null) {
            try {
                const viewPeriodsResponse = await checkAccess('view_periods', accessLevel, userGarnizon, selectedGarnisonIndex);
                if (viewPeriodsResponse.success && viewPeriodsResponse.access) {
                    const garnizonIds = viewPeriodsResponse.all_garrisons_plus_88 ? ['6', '5', '31', '10', '13', '29', '17', '88'] : [selectedGarnisonIndex];
                    for (let garnizon of garnizonIds) {
                        const response = await fetch('../api/marked_dates.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ garnizon: garnizon, mode: 'svodki' })
                        });
                        const data = await response.json();
                        if (data.success && data.dates) {
                            markedDates = markedDates.concat(data.dates.map(item => ({
                                start: moment(item.start, "YYYY-MM-DD"),
                                end: moment(item.end, "YYYY-MM-DD")
                            })));
                        }
                    }
                }
            } catch (error) {
                logAction(`Ошибка при загрузке периодов: ${error.message}`);
                console.error("Ошибка при загрузке периодов:", error);
            }
        }
        if (markedDates.length > 0) {
            let adjustedStart = startDate.clone();
            let adjustedEnd = endDate.clone();
            if (startDate.isSame(endDate)) {
                for (let period of markedDates) {
                    if (startDate.isSameOrAfter(period.start) && startDate.isSameOrBefore(period.end)) {
                        adjustedStart = period.start;
                        adjustedEnd = period.end;
                        break;
                    }
                }
            } else {
                let startPeriod = markedDates.find(p =>
                    startDate.isSameOrAfter(p.start) && startDate.isSameOrBefore(p.end)
                );
                let endPeriod = markedDates.find(p =>
                    endDate.isSameOrAfter(p.start) && endDate.isSameOrBefore(p.end)
                );
                if (startPeriod) adjustedStart = startPeriod.start;
                if (endPeriod) adjustedEnd = endPeriod.end;
                if (startPeriod && endPeriod && startPeriod !== endPeriod) {
                    adjustedStart = startPeriod.start;
                    adjustedEnd = endPeriod.end;
                }
            }
            picker.setStartDate(adjustedStart);
            picker.setEndDate(adjustedEnd);
            startDate = adjustedStart;
            endDate = adjustedEnd;
            $(this).val(adjustedStart.format('DD.MM.YYYY') + ' по ' + adjustedEnd.format('DD.MM.YYYY'));
        }
        CompassState.set({
            garnizon: selectedGarnisonIndex,
            startDate: startDate.format('DD.MM.YYYY'),
            endDate: endDate.format('DD.MM.YYYY')
        });
        $('.save-button-container').empty();
        if (selectedGarnisonIndex !== null) {
            const weeks = splitIntoWeeks(startDate, endDate);
            await showSelectedDates(weeks);
        }
    });
    $('.date-selector .dropdown-item').click(async function(event) {
        event.preventDefault();
        let selectedText = $(this).text().toUpperCase();
        let newGarnisonIndex = $(this).data('index').toString();
        const accessResponse = await checkAccess('dropdown', accessLevel, userGarnizon, newGarnisonIndex);
        if (!accessResponse.success || !accessResponse.access) {
            alert('Нет доступа к этому гарнизону.');
            return;
        }
        selectedGarnisonIndex = newGarnisonIndex;
        CompassState.set({
            garnizon: selectedGarnisonIndex,
            startDate: $('#date-range').data('daterangepicker').startDate.format('DD.MM.YYYY'),
            endDate: $('#date-range').data('daterangepicker').endDate.format('DD.MM.YYYY')
        });
        $('#garnizon-dropdown').text(selectedText);
        $('#garnizon-display').text(selectedText);
        applyGarnizonDropdownScope();
        $('.save-button-container').empty();
        const startDate = $('#date-range').data('daterangepicker').startDate;
        const endDate = $('#date-range').data('daterangepicker').endDate;
        const weeks = splitIntoWeeks(startDate, endDate);
        showSelectedDates(weeks);
        toggleToggleSection();
        logAction(`Просмотр гарнизона: ${selectedText}`);
    });
    $('.save-button-container').on('click', 'button', async function() {
        isButtonClicked = true;
        const startDate = $('#date-range').data('daterangepicker').startDate.format('YYYY-MM-DD');
        const accessResponse = await checkAccess('save_data', accessLevel, userGarnizon, selectedGarnisonIndex, startDate);
        if (!accessResponse.success || !accessResponse.access) {
            alert('Нет доступа к сохранению данных.');
            return;
        }
        saveData();
        logAction(`Сохранение данных для гарнизона: ${$('#garnizon-display').text()} за ${$('#date-range').val()}`);
    });
    $('#number-input').on('input', function() {
        let value = $(this).val();
        if (!/^[0-9]*$/.test(value)) {
            $(this).val(value.replace(/[^0-9]/g, ''));
        }
        $(this).css('color', value.trim() ? '#000000' : '#6c757d');
        checkFormCompletion();
    });
    $('#type_proicsh_menu .dropdown-item').on('click', function() {
        const selectedText = $(this).data('text').toUpperCase();
        $('#type_proicsh_btn')
            .text(selectedText)
            .css('color', '#000000')
            .attr('data-selected', 'true')
            .attr('title', selectedText);
        checkFormCompletion();
    });
$('#show-all-toggle').on('change', async function() {
    const startDate = $('#date-range').data('daterangepicker').startDate;
    const endDate = $('#date-range').data('daterangepicker').endDate;
    await loadIncidentCards(startDate, endDate);
});
$(document).on('click', '#additional-save-button', async function() {
    const numberInput = $('#number-input').val().trim();
    const commentInput = $('#exampleFormControlTextarea1').val().trim();
    const garnizonText = $('#garnizon-display').text().trim();
    const selectedText = $('#type_proicsh_btn').text() === 'ИНЦИДЕНТ' ? '' : $('#type_proicsh_btn').text().trim();
    const startDate = $('#date-range').data('daterangepicker').startDate;
    const endDate = $('#date-range').data('daterangepicker').endDate;
    const garnizonId = selectedGarnisonIndex;
    const id_zapisi = $('#number-input').data('id_zapisi') || null;
    if (!numberInput) {
        alert('Пожалуйста, введите номер.');
        return;
    }
    if (!selectedText && !id_zapisi) {
        alert('Пожалуйста, выберите тип происшествия.');
        return;
    }
    if (!startDate.isSame(endDate, 'day')) {
        alert('Редактирование возможно только при выборе одного дня.');
        return;
    }
    const editAction = (accessLevel === '5') ? 'edit_svodki_before_special' : 'edit_svodki_1day';
    const accessResponse = await checkAccess(editAction, accessLevel, userGarnizon, garnizonId, startDate.format('YYYY-MM-DD'));
    if (!accessResponse.success || !accessResponse.access) {
        alert('Нет доступа к редактированию карточки.');
        return;
    }
    try {
        const checkResponse = await $.ajax({
            url: '../api/output_incidents.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'view',
                numb_proicsh: parseInt(numberInput),
                garnizon: garnizonId,
                date: endDate.format('DD.MM.YYYY')
            }
        });
        if (!checkResponse.success) {
            logAction(`Ошибка проверки карточки: ${checkResponse.error}`);
            alert('Ошибка: ' + checkResponse.error);
            return;
        }
        const saveResponse = await $.ajax({
            url: '../api/output_incidents.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'save',
                numb_proicsh: parseInt(numberInput),
                selected_text: selectedText,
                text_proicsh: commentInput,
                garnizon: garnizonId,
                date: endDate.format('DD.MM.YYYY'),
                id_zapisi: id_zapisi
            }
        });
        if (saveResponse.success) {
            const action = id_zapisi ?
                `Редактирование карточки: КУЗП ${numberInput}, тип ${selectedText || 'не изменен'}, гарнизон ${garnizonText}, дата ${endDate.format('DD.MM.YYYY')}, текст: ${commentInput}` :
                `Создание карточки: КУЗП ${numberInput}, тип ${selectedText}, гарнизон ${garnizonText}, дата ${endDate.format('DD.MM.YYYY')}, текст: ${commentInput}`;
            await logAction(action);
            alert(saveResponse.message || 'РљР°СЂС‚РѕС‡РєР° СЃРѕС…СЂР°РЅРµРЅР°.');
            $('#number-input').val('').removeData('id_zapisi');
            $('#exampleFormControlTextarea1').val('');
            $('#type_proicsh_btn')
                .text('ИНЦИДЕНТ')
                .css('color', '#6c757d')
                .removeAttr('data-selected')
                .removeAttr('title');
            checkFormCompletion();
            await loadIncidentCards(startDate, endDate);
        } else {
            logAction(`Ошибка сохранения карточки: ${saveResponse.error}`);
            alert('Ошибка: ' + saveResponse.error);
        }
    } catch (error) {
        logAction(`Ошибка при выполнении запроса на сохранение карточки: ${error.message}`);
        console.error('Ошибка при выполнении запроса:', error);
        alert('Ошибка сервера: ' + error.message);
    }
});
    } catch (error) {
        logAction(`Ошибка инициализации: ${error.message}`);
        console.error('Initialization error:', error);
        $('#main-content').html('<div style="padding: 20px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;">Ошибка инициализации страницы.</div>');
    }
});
function toggleToggleSection() {
    if (selectedGarnisonIndex === '88') {
        $('#toggle-section').show();
    } else {
        $('#toggle-section').hide();
    }
}
function checkFormCompletion() {
    const numberInput = $('#number-input').val().trim();
    const commentInput = $('#exampleFormControlTextarea1').val().trim();
    const selectedIncident = $('#type_proicsh_btn').text() !== 'ИНЦИДЕНТ';
    const isEditMode = Boolean($('#number-input').data('id_zapisi'));
    if (numberInput && commentInput && (selectedIncident || isEditMode)) {
        $('#additional-save-button').show();
        $('#additional-cancel-button').show();
    } else {
        $('#additional-save-button').hide();
        $('#additional-cancel-button').hide();
    }
}
async function updateGlavnStatus(numb, glavn) {
    try {
        const response = await fetch('../api/output_incidents.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ numb, glavn, garnizon: selectedGarnisonIndex })
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || data.error || 'error');
        const $card = $(`.card[data-numb="${numb}"]`);
        $card.data('glavn', glavn).attr('data-glavn', glavn);
    } catch (err) {
        logAction(`Ошибка в updateGlavnStatus: ${err.message}`);
        console.error('Ошибка в updateGlavnStatus:', err);
        alert('Ошибка при смене главной карточки: ' + err.message);
    }
}
function adjustCardTextHeight() {
    document.querySelectorAll('.card').forEach(card => {
        const isExpanded = card.classList.contains('expanded');
        const title = card.querySelector('.card-title');
        const text = card.querySelector('.card-text');
        const titleStyle = window.getComputedStyle(title);
        const titleLineHeight = parseFloat(titleStyle.lineHeight);
        const titleHeight = title.offsetHeight;
        const titleLines = Math.min(Math.round(titleHeight / titleLineHeight), 3);
        if (isExpanded) {
            text.style.display = 'block';
            text.style.webkitLineClamp = 'unset';
            text.style.overflow = 'visible';
        } else {
            const totalLines = 7;
            const textLines = totalLines - titleLines;
            text.style.display = '-webkit-box';
            text.style.webkitBoxOrient = 'vertical';
            text.style.overflow = 'hidden';
            text.style.webkitLineClamp = textLines.toString();
        }
    });
    const cards = document.querySelectorAll('.card:not(.expanded)');
    if (cards.length > 0) {
        let maxHeight = 0;
        cards.forEach(card => {
            card.style.height = 'auto';
            const cardHeight = card.offsetHeight;
            maxHeight = Math.max(maxHeight, cardHeight);
        });
        cards.forEach(card => {
            card.style.height = `${maxHeight}px`;
        });
    }
    document.querySelectorAll('.card.expanded').forEach(card => {
        card.style.height = 'auto';
    });
}

        function updateReadonlyBanner(isEditable) {
            const rules = CompassState.editRules('svodki');
            const $banner = $('#readonly-banner');
            if (isEditable || rules.reason === 'mvd_sum') {
                $banner.removeClass('show').text('');
            } else {
                let msg = '';
                if (rules.reason === 'multi_day') msg = '⚠ Выбрано несколько дней — для суточной сводки доступен только 1 день';
                else                              msg = '⚠ Режим просмотра — редактирование недоступно';
                $banner.addClass('show').text(msg);
            }
        }

async function loadIncidentCards(startDate, endDate) {
    try {
        const accessResponse = await checkAccess('view_svodki', accessLevel, userGarnizon, selectedGarnisonIndex);
        if (!accessResponse.success || !accessResponse.access) {
            $('#incident-cards').html('<div class="no-incidents">Нет доступа к просмотру происшествий.</div>');
            return;
        }
        const showAll = selectedGarnisonIndex === '88' ? $('#show-all-toggle').is(':checked') : false;
        let allIncidents = [];
        if (selectedGarnisonIndex === '88' && accessResponse.all_garrisons_plus_88) {
            // Гарнизон 88: при выключенном переключателе — только glavn=1, иначе все
            const response = await fetch('../api/output_incidents.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    start_date: startDate.format('YYYY-MM-DD'),
                    end_date: endDate.format('YYYY-MM-DD'),
                    garnizon: '88',
                    glavn_only: !showAll
                })
            });
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error || 'Неверный формат ответа от сервера');
            }
            allIncidents = data.incidents || [];
        } else {
            // Для других гарнизонов: все карточки только для выбранного гарнизона
            const garnizonIds = [selectedGarnisonIndex];
            for (let garnizon of garnizonIds) {
                const response = await fetch('../api/output_incidents.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        start_date: startDate.format('YYYY-MM-DD'),
                        end_date: endDate.format('YYYY-MM-DD'),
                        garnizon: garnizon
                    })
                });
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    throw new Error(`Ожидался JSON, получено: ${contentType}. Ответ: ${text}`);
                }
                const data = await response.json();
   
                if (!response.ok) {
                    throw new Error(data.error || `HTTP error! status: ${response.status}`);
                }
                if (!data.success) {
                    throw new Error(data.error || 'Неверный формат ответа от сервера');
                }
                if (!Array.isArray(data.incidents)) {
                    throw new Error('Ожидался массив incidents в ответе');
                }
                allIncidents = allIncidents.concat(data.incidents);
            }
        }
        $('#incident-cards').empty();
        if (allIncidents.length === 0) {
            $('#incident-cards').html('<div class="no-incidents">Нет происшествий за выбранный период.</div>');
            return;
        }
        const containerHtml = `<div class="row row-cols-1 row-cols-md-2 g-4"></div>`;
        $('#incident-cards').append(containerHtml);
        // Используем CompassState.editRules для унифицированной логики
        const _editRules = CompassState.editRules('svodki');
        let isEditableCard = _editRules.canEdit;
        updateReadonlyBanner(isEditableCard);
        allIncidents.forEach(incident => {
            const displayDate = moment(incident.data_proicsh, 'YYYY-MM-DD').format('DD.MM.YYYY');
            const textHtml = incident.text_proicsh.replace(/\n/g, '<br>');
 
            const isMainCard = incident.glavn === 1;
            const cardClass = isMainCard ? 'main-card' : '';
            const cardStyle = isMainCard ? 'border: 2px solid #D2691E;' : 'border: 2px solid #ccc;';
            const editButtonHtml = isEditableCard && incident.is_editable ? `
                <button class="btn btn-link edit-form-btn p-0 mb-2" title="Редактировать в форме">
                    ✎
                </button>
            ` : '';
            const cardHtml = `
                <div class="col">
                    <div class="card ${cardClass}" data-numb="${incident.numb_proicsh}" data-glavn="${incident.glavn}" data-id-zapisi="${incident.id_zapisi}" style="${cardStyle}">
                        <div class="card-body d-flex">
                            <div class="card-actions">
                                <div class="form-check mb-2">
                                    <input class="form-check-input glavn-checkbox" type="checkbox" value="" id="check-${incident.numb_proicsh}" ${incident.glavn === 1 ? 'checked' : ''} ${accessLevel === '3' || accessLevel === '4' || accessLevel === '5' ? '' : 'disabled'}>
                                    <label class="form-check-label" for="check-${incident.numb_proicsh}"></label>
                                </div>
                                ${editButtonHtml}
                                <button class="btn btn-link expand-btn p-0" title="Развернуть карточку"></button>
                            </div>
                            <label class="card-label flex-grow-1">
                                <h5 class="card-title">${incident.garnizon_name?.toUpperCase() || 'НЕИЗВЕСТНЫЙ ГАРНИЗОН'}. ${incident.type_proicsh_name || 'Неизвестный тип'}. КУЗП - ${incident.numb_proicsh} от ${displayDate}г.</h5>
                                <div class="card-text-wrapper">
                                    <div class="card-text" data-text="${incident.text_proicsh.replace(/"/g, '&quot;')}">${textHtml}</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            `;
            $('#incident-cards .row').append(cardHtml);
        });
        let clickTimeout = null;
        $(document).off('click', '.card').on('click', '.card', async function (e) {
            const $card = $(this);
            const $target = $(e.target);
            const isForbiddenZone = $target.is('.glavn-checkbox') || $target.is('.edit-form-btn') || $target.is('.expand-btn');
            if (clickTimeout !== null) {
                clearTimeout(clickTimeout);
                clickTimeout = null;
                if (isForbiddenZone || !(accessLevel === '3' || accessLevel === '4' || accessLevel === '5')) return;
                const numb = parseInt($card.attr('data-numb'));
                const currentGlavn = parseInt($card.attr('data-glavn')) || 0;
                const newGlavn = currentGlavn === 1 ? 0 : 1;
                const cardDate = $card.find('.card-title').text().match(/\d{2}\.\d{2}\.\d{4}/)?.[0];
                const dateMoment = moment(cardDate, 'DD.MM.YYYY');
                const editAction = (accessLevel === '5') ? 'edit_svodki_before_special' : 'edit_svodki_1day';
                const accessResponse = await checkAccess(editAction, accessLevel, userGarnizon, selectedGarnisonIndex, dateMoment.format('YYYY-MM-DD'));
                if (!accessResponse.access) {
                    alert('Нет доступа к изменению статуса карточки.');
                    return;
                }
                if (!isNaN(numb)) {
                    updateGlavnStatus(numb, newGlavn)
                        .then(() => {
                            $card.attr('data-glavn', newGlavn);
                            $card.data('glavn', newGlavn);
                            $card.find('.glavn-checkbox').prop('checked', newGlavn === 1);
                            $card.toggleClass('main-card', newGlavn === 1);
                            $card.css('border', newGlavn === 1 ? '2px solid #D2691E' : '2px solid #ccc');
                            logAction(`Изменение статуса карточки КУЗП-${numb} (glavn = ${newGlavn})`);
                        })
                        .catch(error => {
                            logAction(`Ошибка при обновлении glavn: ${error.message}`);
                            console.error('Ошибка при обновлении glavn:', error);
                            alert('Не удалось обновить статус карточки');
                        });
                }
                return;
            }
            clickTimeout = setTimeout(() => {
                clickTimeout = null;
                if (isForbiddenZone) return;
                const wasExpanded = $card.hasClass('expanded');
                $card.toggleClass('expanded');
                adjustCardTextHeight();
                if (!wasExpanded) {
                    const numb = $card.data('numb');
                    const idZapisi = $card.data('id-zapisi');
                    const cardTitle = $card.find('.card-title').text();
                    logAction(`Развернута карточка КУЗП-${numb} (ID: ${idZapisi}, ${cardTitle})`);
                }
            }, 250);
        });
        $(document).off('change', '.glavn-checkbox').on('change', '.glavn-checkbox', async function(e) {
            e.stopPropagation();
            const $card = $(this).closest('.card');
            const numb = parseInt($card.data('numb'));
            const newGlavn = $(this).is(':checked') ? 1 : 0;
            const cardDate = $card.find('.card-title').text().match(/\d{2}\.\d{2}\.\d{4}/)?.[0];
            const dateMoment = moment(cardDate, 'DD.MM.YYYY');
            const editAction = (accessLevel === '5') ? 'edit_svodki_before_special' : 'edit_svodki_1day';
            const accessResponse = await checkAccess(editAction, accessLevel, userGarnizon, selectedGarnisonIndex, dateMoment.format('YYYY-MM-DD'));
            if (!accessResponse.access) {
                alert('Нет доступа к изменению статуса карточки.');
                $(this).prop('checked', newGlavn === 0);
                return;
            }
                try {
                    await updateGlavnStatus(numb, newGlavn);
                    $card.attr('data-glavn', newGlavn);
                    $card.data('glavn', newGlavn);
                    $card.toggleClass('main-card', newGlavn === 1);
                    $card.css('border', newGlavn === 1 ? '2px solid #D2691E' : '2px solid #ccc');
                    logAction(`Изменение статуса карточки КУЗП-${numb} (glavn = ${newGlavn})`);
                } catch (error) {
                    logAction(`Ошибка при обновлении glavn: ${error.message}`);
                    console.error('Ошибка при обновлении glavn:', error);
                    alert('Не удалось обновить статус карточки');
                    $(this).prop('checked', newGlavn === 0);
                }
        });
        $(document).off('click', '.edit-form-btn').on('click', '.edit-form-btn', function(e) {
            e.stopPropagation();
            const $card = $(this).closest('.card');
            const numb = $card.data('numb');
            const id_zapisi = $card.data('idZapisi');
            const text = $card.find('.card-text').data('text') || '';
            const typeText = $card.find('.card-title').text().split('.')[1]?.trim().split('КУЗП')[0]?.trim() || '';
            const date = $card.find('.card-title').text().match(/\d{2}\.\d{2}\.\d{4}/)?.[0] || '';
            const garnizonText = $card.find('.card-title').text().split('.')[0]?.trim() || '';
            $('#additional-input-section').show();
            $('#number-input').val(numb).data('id_zapisi', id_zapisi);
            $('#exampleFormControlTextarea1').val(text);
            $('#date-display').text(date);
            $('#garnizon-display').text(garnizonText);
            const $incidentItem = $(`#type_proicsh_menu .dropdown-item:contains("${typeText}")`);
            if ($incidentItem.length) {
                const selectedText = $incidentItem.data('text').toUpperCase();
                $('#type_proicsh_btn')
                    .text(selectedText)
                    .css('color', '#000000')
                    .attr('data-selected', 'true')
                    .attr('title', selectedText);
            } else {
                $('#type_proicsh_btn')
                    .text('ИНЦИДЕНТ')
                    .css('color', '#6c757d')
                    .removeAttr('data-selected')
                    .removeAttr('title');
            }
            checkFormCompletion();
        });
        $(document).off('click', '.expand-btn').on('click', '.expand-btn', function(e) {
            e.stopPropagation();
            const $card = $(this).closest('.card');
            const wasExpanded = $card.hasClass('expanded');
            $card.toggleClass('expanded');
            adjustCardTextHeight();
            if (!wasExpanded) {
                const numb = $card.data('numb');
                const idZapisi = $card.data('id-zapisi');
                const cardTitle = $card.find('.card-title').text();
                logAction(`Развернута карточка КУЗП-${numb} (ID: ${idZapisi}, ${cardTitle})`);
            }
        });
        setTimeout(adjustCardTextHeight, 100);
    } catch (error) {
        logAction(`Ошибка в loadIncidentCards: ${error.message}`);
        console.error('Ошибка в loadIncidentCards:', error);
        $('#incident-cards').html('<div class="no-incidents">Ошибка при загрузке данных: ' + error.message + '</div>');
    }
}
    window.addEventListener('resize', adjustCardTextHeight);
    function processCards() {
        document.querySelectorAll('.card').forEach(card => {
            const title = card.querySelector('.card-title');
            const text = card.querySelector('.card-text');
 
            const lineHeight = parseInt(getComputedStyle(title).lineHeight);
            const titleHeight = title.offsetHeight;
            const titleLines = Math.min(Math.round(titleHeight / lineHeight), 3);
 
            const totalLines = 7;
            const textLines = totalLines - titleLines;
 
            if (!card.classList.contains('expanded')) {
                text.classList.remove('lines-3', 'lines-2', 'lines-4', 'lines-5', 'lines-6');
                text.classList.add(`lines-${textLines}`);
            }
 
            const isMain = card.dataset.glavn === '1';
            if (isMain) {
                card.classList.add('main-card');
                card.style.border = '2px solid #A5260A';
            } else {
                card.classList.remove('main-card');
                card.style.border = '2px solid #ccc';
            }
        });
    }
    window.addEventListener('load', processCards);
    window.addEventListener('resize', processCards);
    document.addEventListener('DOMContentLoaded', processCards);
    async function markDatesInCalendar() {
        if (selectedGarnisonIndex === null) return;
        let garnizonIds = ((accessLevel === '2' || accessLevel === '4') && selectedGarnisonIndex === '88') ? ['6', '5', '31', '10', '13', '29', '17', '88'] : [selectedGarnisonIndex];
        let allMarkedDates = [];
        try {
            for (let garnizon of garnizonIds) {
                const response = await fetch('../api/marked_dates.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ garnizon: garnizon, mode: 'svodki' })
                });
                const data = await response.json();
                if (data.success && data.dates) {
                    data.dates.forEach(item => {
                        let start = moment(item.start, "YYYY-MM-DD");
                        let end = moment(item.end, "YYYY-MM-DD");
                        let color = item.color || '#FF0000';
                        let current = start.clone();
                        while (current.isSameOrBefore(end) && current.isSameOrBefore(moment().subtract(1, 'days'))) {
                            allMarkedDates.push({ date: current.format("YYYY-MM-DD"), color });
                            current.add(1, 'days');
                        }
                    });
                }
            }
            applyColorsToCalendar(allMarkedDates);
        } catch (error) {
            logAction(`Ошибка при выделении дат: ${error.message}`);
            console.error("Ошибка при выделении дат:", error);
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
    function applyColorsToCalendar(markedDates) {
        $('.drp-calendar tbody td').css('position', 'relative').find('.calendar-underline').remove();
        setTimeout(() => {
            const highlightedDates = [];
            $('.drp-calendar tbody td').each(function() {
                if ($(this).hasClass('week') || !$(this).text().trim()) return;
                let dateText = $(this).text().trim();
                let monthYear = $(this).closest('.drp-calendar').find('.month').text().trim();
                let formattedDate = convertToFullDate(dateText, monthYear);
                if (!$(this).hasClass('off') && isValidDateInCurrentMonth(formattedDate, monthYear)) {
                    let markedDate = markedDates.find(d => {
                        if (d.date) {
                            return d.date === formattedDate;
                        }
                        const start = moment(d.start, "YYYY-MM-DD");
                        const end = moment(d.end, "YYYY-MM-DD");
                        const current = moment(formattedDate, "YYYY-MM-DD");
                        return current.isValid()
                            && start.isValid()
                            && end.isValid()
                            && current.isSameOrAfter(start, 'day')
                            && current.isSameOrBefore(end, 'day');
                    });
                    if (markedDate && !highlightedDates.includes(formattedDate)) {
                        $(this).css('color', '#000').append(`<span class="calendar-underline" style="position: absolute; bottom: 2px; left: 50%; width: 50%; height: 2px; background-color: ${markedDate.color}; transform: translateX(-50%);"></span>`);
                        highlightedDates.push(formattedDate);
                    }
                }
            });
        }, 300);
    }
    function isValidDateInCurrentMonth(date, monthYear) {
        const [monthName, year] = monthYear.split(" ");
        const months = {
            'Январь': '01', 'Февраль': '02', 'Март': '03', 'Апрель': '04', 'Май': '05', 'Июнь': '06',
            'Июль': '07', 'Август': '08', 'Сентябрь': '09', 'Октябрь': '10', 'Ноябрь': '11', 'Декабрь': '12'
        };
        const month = months[monthName];
        const dateObj = moment(date, "YYYY-MM-DD");
        return dateObj.month() + 1 === parseInt(month) && dateObj.year() === parseInt(year);
    }
    function convertToFullDate(day, monthYear) {
        const months = {
            'Январь': '01', 'Февраль': '02', 'Март': '03', 'Апрель': '04', 'Май': '05', 'Июнь': '06',
            'Июль': '07', 'Август': '08', 'Сентябрь': '09', 'Октябрь': '10', 'Ноябрь': '11', 'Декабрь': '12'
        };
        let [monthName, year] = monthYear.split(" ");
        let month = months[monthName];
        let formattedDay = day.padStart(2, '0');
        return `${year}-${month}-${formattedDay}`;
    }
    async function fetchTableData(startDate, endDate, garnizon) {
        try {
            $('#main-content').append('<div id="loading">Загрузка...</div>');
            const response = await fetch('../api/output_sel_deg.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    start_date: moment(startDate, 'DD.MM.YYYY').format('YYYY-MM-DD'),
                    end_date: moment(endDate, 'DD.MM.YYYY').format('YYYY-MM-DD'),
                    garnizon: garnizon
                })
            });
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            const data = await response.json();
            if (!data.success) throw new Error(data.error || 'Server returned unsuccessful response');
            if (data.success && data.data) {
                data.data = data.data.map(item => ({
                    ...item,
                    number_naim: String(item.number_naim)
                }));
            }
            $('#loading').remove();
            return data;
        } catch (error) {
            $('#loading').remove();
            logAction(`Ошибка при получении данных таблицы: ${error.message}`);
            console.error('Ошибка при получении данных:', error);
            return { success: false, data: [] };
        }
    }
    function calculatePercent() {
        return '';
    }
function aggregateData(data, startDate, endDate, isMVD = false) {
    const result = {};
    if (!data || !Array.isArray(data) || data.length === 0) {
        return [];
    }
    const groupedData = {};
    data.forEach(item => {
        if (!item || !item.id_svodki || !item.garnizon || !item.data_start || !item.data_end) {
            return;
        }
        // Пропускаем строку, где id_svodki (number_naim) = 2, чтобы процент не суммировался, а пересчитывался автоматически
        const itemStart = moment(item.data_start, 'DD.MM.YYYY');
        const itemEnd = moment(item.data_end, 'DD.MM.YYYY');
        const periodStart = moment(startDate, 'DD.MM.YYYY');
        const periodEnd = moment(endDate, 'DD.MM.YYYY');
        if (itemStart.isValid() && itemEnd.isValid() &&
            itemStart.isSameOrBefore(periodEnd) && itemEnd.isSameOrAfter(periodStart)) {
            const key = isMVD ? `${item.number_naim}` : `${item.number_naim}-${item.garnizon}`;
            if (!groupedData[key]) {
                groupedData[key] = [];
            }
            groupedData[key].push(item);
        }
    });
    Object.keys(groupedData).forEach(key => {
        const items = groupedData[key];
        items.sort((a, b) => {
            const dateA = a.data_sozdan ? moment(a.data_sozdan, 'YYYY-MM-DD') : moment();
            const dateB = b.data_sozdan ? moment(b.data_sozdan, 'YYYY-MM-DD') : moment();
            return dateB - dateA;
        });
        const latestItem = items[0];
        result[key] = {
            id_svodki: latestItem.id_svodki,
            number_naim: String(latestItem.number_naim),
            naimenov: latestItem.naimenov,
            pole: latestItem.pole,
            garnizon: isMVD ? '88' : String(latestItem.garnizon),
            data_start: moment(startDate, 'DD.MM.YYYY').format('YYYY-MM-DD'),
            data_end: moment(endDate, 'DD.MM.YYYY').format('YYYY-MM-DD'),
            neraskr: 0,
            raskr: 0,
            kolichestvo: 0,
            data_sozdan: latestItem.data_sozdan
        };
        items.forEach(item => {
            if (latestItem.pole == 1) {
                const currentKolichestvo = parseInt(item.kolichestvo) || 0;
                result[key].kolichestvo += currentKolichestvo;
            } else if (latestItem.pole == 2) {
                result[key].neraskr += parseInt(item.neraskr) || 0;
                result[key].raskr += parseInt(item.raskr) || 0;
            }
        });
    });
    const aggregated = Object.values(result).sort((a, b) => {
        return String(a.number_naim).localeCompare(String(b.number_naim), undefined, { numeric: true });
    });
    return aggregated;
}
async function showSelectedDates(weeks) {
    if (selectedGarnisonIndex === null) {
        $('#data-table-pole2, #data-table-pole1').hide();
        $('.save-button-container').empty();
        $('#additional-input-section').hide();
        return;
    }
    const startDate = moment(weeks[0].start, 'DD.MM.YYYY');
    const endDate = moment(weeks[weeks.length - 1].end, 'DD.MM.YYYY');
    let markedDates = [];
    try {
        const garnizonIds = ((accessLevel === '2' || accessLevel === '4') && selectedGarnisonIndex === '88') ? ['6', '5', '31', '10', '13', '29', '17', '88'] : [selectedGarnisonIndex];
        for (let garnizon of garnizonIds) {
            const response = await fetch('../api/marked_dates.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ garnizon: garnizon, mode: 'svodki' })
            });
            const data = await response.json();
            if (data.success && data.dates) {
                markedDates = markedDates.concat(data.dates.map(item => ({
                    start: moment(item.start, "YYYY-MM-DD"),
                    end: moment(item.end, "YYYY-MM-DD"),
                    created_at: item.created_at ? moment(item.created_at, "YYYY-MM-DD") : moment(item.end, "YYYY-MM-DD")
                })));
            }
        }
    } catch (error) {
        logAction(`Ошибка при загрузке занятых дат: ${error.message}`);
        console.error("Ошибка при загрузке занятых дат:", error);
    }
    const rules = CompassState.editRules('svodki');
    const isEditable = rules.canEdit;
    isEditableGlobal = isEditable;
    let aggregatedData = [];
    if ((accessLevel === '2' || accessLevel === '4') && selectedGarnisonIndex === '88') {
        const overallStart = weeks[0].start;
        const overallEnd = weeks[weeks.length - 1].end;
        const responseData = await fetchTableData(overallStart, overallEnd, 88);
        if (responseData && responseData.success && responseData.data) {
            aggregatedData = responseData.data;
        }
    } else {
        const overallStart = weeks[0].start;
        const overallEnd = weeks[weeks.length - 1].end;
        const responseData = await fetchTableData(overallStart, overallEnd, selectedGarnisonIndex);
        if (responseData && responseData.success && responseData.data) {
            aggregatedData = aggregateData(responseData.data, overallStart, overallEnd, false);
        }
    }
    await updateTable(aggregatedData, isEditable);
    $('#data-table-pole2, #data-table-pole1').css({ display: 'table', opacity: 0 }).animate({ opacity: 1 }, 300).addClass('fade-in');
    if ($('#data-table-pole2').is(':visible') && isEditable) {
        $('.save-button-container').html('<button class="btn btn-primary">СОХРАНИТЬ</button>');
        $('#additional-input-section').show();
    } else {
        $('.save-button-container').empty();
        $('#additional-input-section').hide();
    }
    await loadIncidentCards(startDate, endDate);
}
async function updateTable(data, isEditable) {
    const allRows = await fetchAllRows();
    let headerContentPole2 = `<tr><th>№</th><th>Результаты работы</th></tr>`;
    let headerContentPole1 = `<tr><th>№</th><th>Результаты работы</th></tr>`;
    $('#data-table-pole2 thead').html(headerContentPole2);
    $('#data-table-pole1 thead').html(headerContentPole1);

    allRows.sort((a, b) => String(a.number_naim).localeCompare(String(b.number_naim), undefined, { numeric: true }));
    let pole2Rows = allRows.filter(row => {
        const num = parseFloat(row.number_naim);
        return !isNaN(num) && num < 3;
    });
    let pole1Rows = allRows.filter(row => {
        const num = parseFloat(row.number_naim);
        return !isNaN(num) && num >= 3;
    });

    const startDate = $('#date-range').data('daterangepicker').startDate.format('DD.MM.YYYY');
    const endDate = $('#date-range').data('daterangepicker').endDate.format('DD.MM.YYYY');
    const numberOneData = data.find(d => String(d.number_naim) === '1' && String(d.garnizon) === String(selectedGarnisonIndex));

    function calculatePercentValue(rowData) {
        const neraskr = parseInt(rowData?.neraskr ?? 0, 10) || 0;
        const raskr = parseInt(rowData?.raskr ?? 0, 10) || 0;
        if (!neraskr) return '0.00%';
        return `${((raskr / neraskr) * 100).toFixed(2)}%`;
    }

    function buildRow(row, rowData, tableType) {
        const number_naim = String(row.number_naim);
        const chartUrl = `chart_sel_deg.php?garnizon=${selectedGarnisonIndex}&start=${startDate}&end=${endDate}&number_naim=${number_naim}`;
        const neraskrValue = rowData && rowData.neraskr != null && rowData.neraskr !== 0 ? rowData.neraskr : '';
        const raskrValue = rowData && rowData.raskr != null && rowData.raskr !== 0 ? rowData.raskr : '';
        const splitValue = (neraskrValue !== '' || raskrValue !== '') ? `${neraskrValue || 0}/${raskrValue || 0}` : '';
        const singleValue = rowData && rowData.kolichestvo != null && rowData.kolichestvo !== 0 ? rowData.kolichestvo : '';
        const isPercentRow = number_naim === '2';
        const isSplitRow = row.pole == 2 && !isPercentRow;
        const resultsContent = `
            <div class="results-cell">
                <span class="naimenov">
                    <a href="${chartUrl}"
                       onclick="event.stopPropagation(); logAction(${JSON.stringify(`Переход к графику number_naim=${number_naim} для гарнизона ${selectedGarnisonIndex} за ${startDate} - ${endDate}`)})">
                        ${row.naimenov}
                    </a>
                </span>
                <div class="input-container">
                    ${isPercentRow
                        ? `<span class="readonly">${rowData ? (parseInt(rowData.neraskr) || 0) : 0}</span>`
                        : (isSplitRow
                            ? (isEditable
                                ? `<input type="text" class="form-control split-value-input" value="${splitValue}">`
                                : `<span class="readonly">${splitValue}</span>`)
                            : (isEditable
                                ? `<input type="text" class="form-control single-value-input" value="${singleValue}">`
                                : `<span class="readonly">${singleValue}</span>`))}
                </div>
            </div>`;
        return `<tr data-id="${number_naim}"><td>${row.number_naim}</td><td>${resultsContent}</td></tr>`;
    }

    $('#results-table-body-pole2').html(pole2Rows.map(row => buildRow(row, data.find(d => String(d.number_naim) === String(row.number_naim) && String(d.garnizon) === String(selectedGarnisonIndex)), 'left')).join(''));
    $('#results-table-body-pole1').html(pole1Rows.map(row => buildRow(row, data.find(d => String(d.number_naim) === String(row.number_naim) && String(d.garnizon) === String(selectedGarnisonIndex)), 'right')).join(''));

    $(document).off('input.svodkiDigits').on('input.svodkiDigits', '#results-table-body-pole2 tr .results-cell input, #results-table-body-pole1 tr .results-cell input', function() {
        let inputValue = $(this).val();
        const isLeftInput = $(this).hasClass('split-value-input');
        if (isLeftInput) {
            $(this).val(inputValue.replace(/[^0-9\/]/g, ''));
            const parts = $(this).val().split('/');
            if (parts.length > 2) {
                $(this).val(parts[0] + '/' + parts[1]);
            }
        } else if (!/^[0-9]*$/.test(inputValue)) {
            $(this).val(inputValue.replace(/[^0-9]/g, ''));
        }
        const rowId = String($(this).closest('tr').data('id') || '');
    });

    $(document).off('keydown.svodkiNav').on('keydown.svodkiNav', '#results-table-body-pole2 tr .results-cell input, #results-table-body-pole1 tr .results-cell input', function(e) {
        if (e.keyCode === 38 || e.keyCode === 40) {
            e.preventDefault();
            let $currentInput = $(this);
            let $allInputs = $('#results-table-body-pole2 tr .results-cell input, #results-table-body-pole1 tr .results-cell input');
            let currentIndex = $allInputs.index($currentInput);
            if (e.keyCode === 38 && currentIndex > 0) {
                $allInputs.eq(currentIndex - 1).focus();
            } else if (e.keyCode === 40 && currentIndex < $allInputs.length - 1) {
                $allInputs.eq(currentIndex + 1).focus();
            }
        }
    });

    $('#data-table-pole2, #data-table-pole1').css({ display: 'table', opacity: 0 }).animate({ opacity: 1 }, 300).addClass('fade-in');
}
        async function fetchAllRows() {
            try {
                const response = await fetch('../api/dropdown_sel_deg.php');
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                const data = await response.json();
                if (!Array.isArray(data)) throw new Error('Invalid data format');
                const processedRows = data.map(row => ({
                    number_naim: String(row.number_naim).trim(),
                    naimenov: row.naimenov,
                    pole: row.pole
                })).sort((a, b) => String(a.number_naim).localeCompare(String(b.number_naim), undefined, { numeric: true }));
                return processedRows;
            } catch (error) {
                logAction(`Ошибка загрузки списка пунктов: ${error.message}`);
                console.error('Ошибка загрузки списка пунктов:', error);
                return [];
            }
        }
async function fetchData(selectedGarnison, garnisonIndex) {
    try {
        const response = await $.ajax({
            url: '../api/output_sel_deg.php',
            type: 'POST',
            dataType: 'json',
            data: { garnison: selectedGarnison }
        });
        let headerContentPole2 = `<tr><th>№</th><th>Результаты работы</th></tr>`;
        let headerContentPole1 = `<tr><th>№</th><th>Результаты работы</th></tr>`;
        $('#data-table-pole2 thead').html(headerContentPole2);
        $('#data-table-pole1 thead').html(headerContentPole1);
        if (!Array.isArray(response) || response.length === 0) {
            response = [];
        }
        response.sort((a, b) => String(a.number_naim).localeCompare(String(b.number_naim), undefined, { numeric: true }));
        let pole2Rows = response.filter(row => {
            const num = parseFloat(row.number_naim);
            return !isNaN(num) && num < 16;
        });
        let pole1Rows = response.filter(row => {
            const num = parseFloat(row.number_naim);
            return row.pole == 1 && !isNaN(num) && num >= 16;
        });
        // Получаем даты из date-range picker
        const startDate = $('#date-range').data('daterangepicker').startDate.format('DD.MM.YYYY');
        const endDate = $('#date-range').data('daterangepicker').endDate.format('DD.MM.YYYY');
        let pole2Html = [];
        pole2Rows.forEach((row, index) => {
            let number_naim = String(row.number_naim);
            const chartUrl = `chart_sel_deg.php?garnizon=${garnisonIndex}&start=${startDate}&end=${endDate}&number_naim=${number_naim}`;
            let resultsContent = '';
            if (row.pole == 1 && number_naim == "2") {
                resultsContent = `
                    <div class="results-cell">
                        <span class="naimenov">
                            <a href="${chartUrl}"
                               onclick="event.stopPropagation(); logAction(${JSON.stringify(`Переход к графику number_naim=${number_naim} для гарнизона ${garnisonIndex} за ${startDate} - ${endDate}`)})">
                                ${row.naimenov}
                            </a>
                        </span>
                        <div class="input-container">
                            <input type="text" class="form-control" value="">
                        </div>
                    </div>`;
            } else {
                resultsContent = `
                    <div class="results-cell">
                        <span class="naimenov">
                            <a href="${chartUrl}"
                               onclick="event.stopPropagation(); logAction(${JSON.stringify(`Переход к графику number_naim=${number_naim} для гарнизона ${garnisonIndex} за ${startDate} - ${endDate}`)})">
                                ${row.naimenov}
                            </a>
                        </span>
                        <div class="input-container">
                            <input type="text" class="form-control" value="">
                        </div>
                    </div>`;
            }
            let rowContent = `<tr data-id="${number_naim}"><td>${row.number_naim}</td><td>${resultsContent}</td></tr>`;
            pole2Html.push(rowContent);
        });
        $('#results-table-body-pole2').html(pole2Html.join(''));
        let pole1Html = [];
        pole1Rows.forEach((row, index) => {
            let number_naim = String(row.number_naim);
            const chartUrl = `chart_sel_deg.php?garnizon=${garnisonIndex}&start=${startDate}&end=${endDate}&number_naim=${number_naim}`;
            let resultsContent = `
                <div class="results-cell">
                    <span class="naimenov">
                        <a href="${chartUrl}"
                           onclick="event.stopPropagation(); logAction(${JSON.stringify(`Переход к графику number_naim=${number_naim} для гарнизона ${garnisonIndex} за ${startDate} - ${endDate}`)})">
                            ${row.naimenov}
                        </a>
                    </span>
                    <div class="input-container">
                        <input type="text" class="form-control" value="">
                    </div>
                </div>`;
            let rowContent = `<tr data-id="${number_naim}"><td>${row.number_naim}</td><td>${resultsContent}</td></tr>`;
            pole1Html.push(rowContent);
        });
        $('#results-table-body-pole1').html(pole1Html.join(''));
$(document).on('input', '#results-table-body-pole1 tr .results-cell input', function() {
    let inputValue = $(this).val();
    if (!/^[0-9]+$/.test(inputValue)) {
        $(this).val(inputValue.replace(/[^0-9]/g, ''));
        inputValue = $(this).val();
    }
    let number = parseInt(inputValue) || 0;
    if (number < 0) {
        $(this).val('');
    } else {
        $(this).val(number);
    }
});
$(document).on('keydown', '#results-table-body-pole2 tr .results-cell input, #results-table-body-pole1 tr .results-cell input', function(e) {
    if (e.keyCode === 38 || e.keyCode === 40) {
        e.preventDefault();
        let $currentInput = $(this);
        let $allInputs = $('#results-table-body-pole2 tr .results-cell input, #results-table-body-pole1 tr .results-cell input');
        let currentIndex = $allInputs.index($currentInput);
        if (e.keyCode === 38 && currentIndex > 0) {
            $allInputs.eq(currentIndex - 1).focus();
        } else if (e.keyCode === 40 && currentIndex < $allInputs.length - 1) {
            $allInputs.eq(currentIndex + 1).focus();
        }
    }
});
        toggleFields(garnisonIndex);
        $('#data-table-pole2, #data-table-pole1').fadeIn().addClass('fade-in');
        const startDateMoment = $('#date-range').data('daterangepicker').startDate;
        const endDateMoment = $('#date-range').data('daterangepicker').endDate;
        const weeks = splitIntoWeeks(startDateMoment, endDateMoment);
        showSelectedDates(weeks);
    } catch (error) {
        logAction(`Ошибка в fetchData: ${error.message}`);
        console.error('Ошибка в fetchData:', error);
        $('#results-table-body-pole2').empty();
        $('#results-table-body-pole1').empty();
        $('#data-table-pole2, #data-table-pole1').hide();
    }
}
        function toggleFields(garnisonIndex) {
            $('.field-tiraspol, .field-orovd').hide();
        }
        $('#number-input, #exampleFormControlTextarea1').on('input', checkFormCompletion);
        $('#type_proicsh_menu .dropdown-item').on('click', checkFormCompletion);
(function() {
async function saveData() {
    if (!isButtonClicked) {
        console.log('Сохранение возможно только через интерфейс!');
        return;
    }
    if (!isEditableGlobal) {
        console.log('Редактирование заблокировано');
        return;
    }
    if (selectedGarnisonIndex === null) {
        alert('Пожалуйста, выберите гарнизон.');
        return;
    }
    const startDate = $('#date-range').data('daterangepicker').startDate;
    const endDate = $('#date-range').data('daterangepicker').endDate;
    if (!startDate.isSame(endDate, 'day')) {
        alert('Сохранение возможно только за один день. Пожалуйста, выберите период, охватывающий ровно один день.');
        return;
    }
    if (accessLevel === '5') {
        const maxDate = moment().subtract(1, 'days');
        if (startDate.isAfter(maxDate)) {
            alert('Дата не может быть позже вчерашнего дня.');
            return;
        }
    }
    const editAction = (accessLevel === '5') ? 'edit_svodki_before_special' : 'edit_svodki_1day';
    const accessResponse = await checkAccess(editAction, accessLevel, userGarnizon, selectedGarnisonIndex, startDate.format('YYYY-MM-DD'));
    if (!accessResponse.success || !accessResponse.access) {
        alert('Нет доступа к сохранению данных.');
        return;
    }
    const weeks = splitIntoWeeks(startDate, endDate);
    let formData = [];
    let data_start = moment(weeks[0].start, 'DD.MM.YYYY').format('YYYY-MM-DD');
    let data_end = moment(weeks[weeks.length - 1].end, 'DD.MM.YYYY').format('YYYY-MM-DD');
    let hasError = false;
    let errorMessage = '';
    const allRows = await fetchAllRows();
    let logDetails = [];
    let deleteData = [];
    const existingResponse = await fetch('../api/output_sel_deg.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            start_date: data_start,
            end_date: data_end,
            garnizon: selectedGarnisonIndex
        })
    });
    const existingData = await existingResponse.json();
    let existingRecords = existingData.success ? existingData.data.map(item => ({
        id_zapisi: item.id_zapisi,
        id_svodki: item.id_svodki,
        garnizon: item.garnizon,
        data_start: moment(item.data_start, 'DD.MM.YYYY').format('YYYY-MM-DD'),
        data_end: moment(item.data_end, 'DD.MM.YYYY').format('YYYY-MM-DD'),
        number_naim: String(item.number_naim)
    })) : [];
    $('#results-table-body-pole2 tr, #results-table-body-pole1 tr').each(function() {
        let number_naim = String($(this).data('id'));
        if (!number_naim || number_naim === '2') {
            return;
        }
        const isLeftTable = $(this).closest('tbody').attr('id') === 'results-table-body-pole2';
        let neraskr = 0;
        let raskr = 0;
        let kolichestvo = 0;
        if (isLeftTable) {
            let splitInputValue = ($(this).find('.split-value-input').val() || '').trim();
            let splitParts = splitInputValue.split('/');
            let neraskrValue = (splitParts[0] || '').trim();
            let raskrValue = (splitParts[1] || '').trim();
            neraskr = parseInt(neraskrValue, 10) || 0;
            raskr = parseInt(raskrValue, 10) || 0;
            kolichestvo = neraskr + raskr;
        } else {
            let singleValue = ($(this).find('.single-value-input').val() || '').trim();
            kolichestvo = parseInt(singleValue, 10) || 0;
        }

        let existing = existingRecords.find(record =>
            String(record.number_naim) === number_naim &&
            record.garnizon == selectedGarnisonIndex &&
            moment(record.data_start).isSame(moment(data_start, 'YYYY-MM-DD'), 'day') &&
            moment(record.data_end).isSame(moment(data_end, 'YYYY-MM-DD'), 'day')
        );

        if (kolichestvo <= 0) {
            if (existing && existing.id_zapisi) {
                deleteData.push({ id_zapisi: existing.id_zapisi });
            }
            return;
        }

        logDetails.push(`Пункт ${number_naim}: ${kolichestvo}`);
        formData.push({
            number_naim,
            neraskr: isLeftTable ? neraskr : null,
            raskr: isLeftTable ? raskr : null,
            kolichestvo,
            data_start,
            data_end,
            garnizon: selectedGarnisonIndex,
            data_sozdan: moment().format('YYYY-MM-DD HH:mm:ss')
        });
    });

    if (hasError) {
        alert(errorMessage);
        return;
    }
    await logAction(`Сохранение данных для гарнизона ${$('#garnizon-display').text()} за ${startDate.format('DD.MM.YYYY')}: ${logDetails.join(', ')}`);
    try {
        let updateData = [];
        let insertData = [];
        formData.forEach(item => {
            let existing = existingRecords.find(record =>
                String(record.number_naim) === String(item.number_naim) &&
                record.garnizon == item.garnizon &&
                moment(record.data_start).isSame(moment(item.data_start, 'YYYY-MM-DD'), 'day') &&
                moment(record.data_end).isSame(moment(item.data_end, 'YYYY-MM-DD'), 'day')
            );
            if (existing && existing.id_zapisi) {
                updateData.push({
                    id_zapisi: existing.id_zapisi,
                    neraskr: item.neraskr,
                    raskr: item.raskr,
                    kolichestvo: item.kolichestvo
                });
            } else {
                insertData.push(item);
            }
        });
        const response = await fetch('../api/update_sel_deg.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                update: updateData,
                insert: insertData,
                delete: deleteData
            })
        });
        const result = await response.json();
        if (result.success) {
            alert('Данные успешно сохранены!');
            const weeks = splitIntoWeeks(startDate, endDate);
            showSelectedDates(weeks);
        } else {
            logAction(`Ошибка при сохранении данных таблицы: ${result.error || 'Неизвестная ошибка'}`);
            alert('Ошибка при сохранении данных: ' + (result.error || 'Неизвестная ошибка'));
        }
    } catch (error) {
        logAction(`Ошибка при сохранении данных таблицы: ${error.message}`);
        console.error('Ошибка при сохранении данных:', error);
        alert('Ошибка при сохранении данных!');
    } finally {
        isButtonClicked = false;
    }
}
    window.saveData = saveData;
})();

    function escapeHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // Вызов AI-провайдера (Groq или Gemini) из браузера
    async function callAiProvider(provider, apiKey, prompt) {
        if (provider === 'groq') {
            const r = await fetch('https://api.groq.com/openai/v1/chat/completions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${apiKey}` },
                body: JSON.stringify({ model: 'llama-3.3-70b-versatile', messages: [{ role: 'user', content: prompt }], max_tokens: 1400, temperature: 0.3 })
            });
            const d = await r.json();
            if (d.error) throw new Error(d.error.message || 'Groq API error');
            return d?.choices?.[0]?.message?.content || '';
        } else {
            const r = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${apiKey}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ contents: [{ parts: [{ text: prompt }] }], generationConfig: { maxOutputTokens: 1400, temperature: 0.3 } })
            });
            const d = await r.json();
            if (d.error) throw new Error(d.error.message || 'Gemini API error');
            return d?.candidates?.[0]?.content?.parts?.[0]?.text || '';
        }
    }

    // Шапка с метаданными
    function statsHeader(stats) {
        return `<div style="background:#2c3e50;color:#ecf0f1;padding:10px 14px;border-radius:4px;margin-bottom:14px;font-size:.9rem;">
            <strong>Регион:</strong> ${escapeHtml(stats.garnizon || '')} &nbsp;&nbsp;
            <strong>Период:</strong> ${escapeHtml(stats.period || '')} &nbsp;&nbsp;
            <strong>Зарег. преступлений:</strong> ${stats.total || 0}
        </div>`;
    }

    // Рендер разделов [ЗАГОЛОВОК] → карточки
    function renderSections(text) {
        const defs = [
            { key: 'АНАЛИЗ ОБСТАНОВКИ',         bg: '#1e3d6e' },
            { key: 'ОСНОВНЫЕ УГРОЗЫ',            bg: '#6b1a24' },
            { key: 'РЕКОМЕНДАЦИИ',               bg: '#0d4d30' },
            { key: 'ПРОГНОЗ НА СЛЕДУЮЩИЙ МЕСЯЦ', bg: '#7a4000' },
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

    // Рендер подробного анализа по гарнизонам
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

    // Итоговый рендер (один гарнизон)
    function renderAiAnalysis(data) {
        return statsHeader(data.stats || {}) + renderSections(data.analysis || '');
    }

    </script>

<!-- AI Analysis Modal -->
<style>#aiModal .card,#aiModal .card:hover,#aiModal .card:active{transform:none!important;transition:none!important;border-color:transparent!important;box-shadow:none!important;}</style>
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
</body>
</html>



