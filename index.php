<?php
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');

$yesterday = date('d.m.Y', strtotime('-1 day'));
$startDate = $yesterday;
$endDate   = $yesterday;
$garnizonNames = [
    '88'=>'МВД','6'=>'Тирасполь','5'=>'Бендеры','31'=>'Слободзея',
    '10'=>'Григориополь','13'=>'Дубоссары','29'=>'Рыбница','17'=>'Каменка'
];
$initialGarnizonIndex = '88';
$initialGarnisonText  = 'МВД';
$default_selections   = ['1','2'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Компас</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="assets/js/compass_state.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="assets/js/daterangepicker.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.1/daterangepicker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    * { box-sizing:border-box; margin:0; padding:0; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif; }
    body { background-color:#f4f4f4; color:#333; padding-top:10px; display:flex; flex-direction:column; position:relative; justify-content:flex-start; min-height:100vh; }
    .header { display:flex; justify-content:center; align-items:center; position:relative; height:60px; }
    .header-content { display:flex; align-items:center; justify-content:center; width:100%; position:relative; }
    h1 { margin:0; font-size:36px; color:#4682B4; text-align:center; font-weight:bold; line-height:1; }
    .date-selector { display:flex; align-items:center; justify-content:center; gap:10px; margin:15px auto; margin-bottom:0; transition:transform .3s ease; }
    .date-selector .dropdown { position:relative; }
    .date-selector .dropdown button { border-radius:8px; border:1px solid #4682B4; font-size:18px; font-weight:600; text-align:center; width:180px; height:46px; cursor:pointer; box-sizing:border-box; background-color:#4682B4; color:#fff; transition:background-color .3s,border-color .3s,color .3s; }
    .date-selector .dropdown-menu { width:180px; }
    .date-selector input { padding:10px; border-radius:8px; border:1px solid #4682B4; font-size:18px; font-weight:600; text-align:center; width:35%; min-width:300px; height:46px; cursor:pointer; box-sizing:border-box; background-color:#fff; color:#333; transition:border-color .3s; }
    .date-selector .dropdown button:hover { background-color:#fff; color:#4682B4; border-color:#4682B4; }
    .date-selector input:hover { border-color:#0056b3; }
    .container { display:flex; flex-wrap:wrap; justify-content:center; gap:5px; margin-top:20px; margin-bottom:auto; }
    .card-wrapper { flex:0 0 33%; min-width:33%; max-width:49%; position:relative; display:flex; justify-content:center; align-items:stretch; padding:10px; }
    .card { background-color:#fff; border-radius:12px; padding:20px; text-align:center; transition:transform .2s,border .3s,box-shadow .3s; border:2px solid transparent; cursor:pointer; width:100%; height:225px; overflow:hidden; position:relative; display:flex; flex-direction:column; justify-content:space-between; transform-origin:center; }
    .card:hover { transform:scale(1.02); border:3px solid #4682B4; box-shadow:0 6px 12px rgba(0,0,0,.2); }
    .card:active { transform:scale(1); border:2px solid #0056b3; box-shadow:none; }
    .card .title { white-space:normal; overflow:visible; margin-bottom:10px; font-size:19px; line-height:1.2; display:-webkit-box; -webkit-box-orient:vertical; -webkit-line-clamp:3; font-weight:700; }
    .card .line { position:relative; width:0; height:1.5px; background-color:#4682B4; margin-top:calc(1em - 20px); left:50%; transform:translateX(-50%); transition:width .2s; }
    .card:hover .line { width:100%; }
    .card .details { margin-bottom:10px; display:flex; justify-content:space-between; white-space:nowrap; font-weight:600; font-size:16px; align-items:flex-end; }
    .card .details span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:91%; }
    .footer { text-align:center; font-size:14px; color:#777; margin:10px; }
    .report-button { border-radius:8px; border:1px solid #4682B4; font-size:18px; font-weight:600; text-align:center; min-width:300px; height:46px; cursor:pointer; box-sizing:border-box; background-color:#4682B4; color:#fff; transition:background-color .3s,border-color .3s,color .3s; margin-bottom:15px; }
    .report-button:hover { background-color:#fff; color:#4682B4; border-color:#4682B4; }
    .card.trust-card { position:relative; z-index:1; transition:height .3s; height:225px; }
    .card.trust-card.expanded { height:435px; cursor:default; }
    .svodki-list-container { display:none; max-height:150px; overflow-y:auto; border:1px solid #ddd; background:#fff; width:370px; }
    .svodki-list-container.show { display:block; }
    .svodki-list-container .list-group-item { display:flex; align-items:center; padding:8px 12px; font-size:14px; }
    .svodki-list-container .list-group-item input[type="checkbox"] { margin-right:10px; }
    .svodki-list-container .list-group-item label { cursor:pointer; margin:0; flex:1; }
    .card-actions { display:none; text-align:center; }
    .card-actions.show { display:flex; justify-content:center; gap:15px; }
    .card-actions .save-btn { padding:5px 15px; font-size:14px; background-color:#4682B4; color:#fff; font-weight:550; height:33px; width:110px; border:1px solid #4682B4; }
    .card-actions .save-btn:hover { background-color:#fff; color:#4682B4; }
    .card-actions .cancel-btn { padding:5px 15px; font-size:14px; background-color:#6c757d; color:#fff; height:33px; width:110px; }
    .card-actions .cancel-btn:hover { background-color:#fff; color:#6c757d; border:1px solid #6c757d; }
    .custom-arrow-btn { background:none; border:none; padding:0; cursor:pointer; position:absolute; bottom:2px; left:50%; transform:translateX(-50%); width:30px; height:20px; opacity:0; transition:opacity .3s; z-index:2; }
    .card:hover .custom-arrow-btn { opacity:1; }
    .custom-arrow-btn::after { content:""; position:absolute; top:0; left:50%; transform:translateX(-50%); width:0; height:0; border-left:15px solid transparent; border-right:15px solid transparent; border-top:15px solid #4682B4; transition:border-top-color .3s,transform .3s; }
    .custom-arrow-btn.expanded::after { border-top:none; border-bottom:15px solid #4682B4; }
    .calendar-underline { position:absolute; bottom:2px; left:50%; width:50%; height:2px; transform:translateX(-50%); }
    #garnizon-dropdown { text-transform:uppercase; }
    @media screen and (max-width:1200px) { .date-selector { flex-direction:column; } .card-wrapper { flex:0 0 40%; max-width:40%; } }
    @media screen and (max-width:770px) { .card-wrapper { flex:0 0 100%; max-width:100%; } }
    </style>
    <script>
    // ── Единое состояние ────────────────────────────────────────────────────────
    const _initState = CompassState.initFromURL();
    let selectedGarnisonIndex = String(_initState.garnizon || <?php echo json_encode($initialGarnizonIndex); ?>);
    let cardSelections = { 'criminogenicity': <?php echo json_encode($default_selections); ?> };
    let tempSelections = {};
    const isMVD = false;
    const isSpecial = false;

    $(document).ready(function() {
        const today = moment();
        const yesterday = today.clone().subtract(1, 'days');
        const currentState = CompassState.get();
        const savedStart = currentState.startDate ? moment(currentState.startDate, 'DD.MM.YYYY', true) : null;
        const savedEnd = currentState.endDate ? moment(currentState.endDate, 'DD.MM.YYYY', true) : null;
        const initialStartDate = (savedStart && savedStart.isValid()) ? savedStart : yesterday.clone();
        const initialEndDate = (savedEnd && savedEnd.isValid()) ? savedEnd : yesterday.clone();

        const currentLabel = CompassState.garnizonNames[selectedGarnisonIndex] || '<?php echo $initialGarnisonText; ?>';
        $('#garnizon-dropdown').text(String(currentLabel).toUpperCase());

        loadSvodkiList().then(svodkiList => {
            $('.trust-card[data-card-id="criminogenicity"]').each(function() {
                const cardId = $(this).data('card-id');
                populateSvodkiList(cardId, $(this).find('.svodki-list-container'), svodkiList);
                updateCardDetails(cardId, $(this), svodkiList);
            });
        });

        try {
            $('#date-range').daterangepicker({
                startDate: initialStartDate,
                endDate:   initialEndDate,
                locale: { format:'DD.MM.YYYY', separator:' по ', applyLabel:'Применить', cancelLabel:'Отмена',
                    monthNames:['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
                    daysOfWeek:['Вс','Пн','Вт','Ср','Чт','Пт','Сб'], firstDay:1 },
                opens:'center', autoUpdateInput:true, linkedCalendars:false, maxDate:yesterday, autoApply:true
            });
        } catch(e) { console.error('DateRangePicker init failed:', e); }

        CompassState.set({
            garnizon: selectedGarnisonIndex,
            startDate: initialStartDate.format('DD.MM.YYYY'),
            endDate: initialEndDate.format('DD.MM.YYYY')
        });

        $('#date-range').on('apply.daterangepicker', function(ev, picker) {
            CompassState.set({
                startDate: picker.startDate.format('DD.MM.YYYY'),
                endDate:   picker.endDate.format('DD.MM.YYYY'),
            });
            const weeks = splitIntoWeeks(picker.startDate, picker.endDate);
            updateCards(weeks);
        });

        // Гарнизон dropdown
        $('#garnizon-dropdown').click(function(e) {
            e.preventDefault();
            $(this).siblings('.dropdown-menu').toggle();
        });

        $('.dropdown-item[data-index]').click(function(e) {
            e.preventDefault();
            selectedGarnisonIndex = String($(this).data('index'));
            CompassState.set({ garnizon: selectedGarnisonIndex });
            $('#garnizon-dropdown').text($(this).text().toUpperCase());
            $('.date-selector .dropdown-menu').hide();
            const sd = $('#date-range').data('daterangepicker')?.startDate;
            const ed = $('#date-range').data('daterangepicker')?.endDate;
            updateCards(splitIntoWeeks(sd, ed));
        });

        $(document).click(function(e) {
            if (!$(e.target).closest('.date-selector .dropdown').length) {
                $('.date-selector .dropdown-menu').hide();
            }
        });

        // Клик по простым карточкам → переход по data-link
        $('.card:not(.trust-card)[data-link]').click(function() {
            const link = $(this).data('link');
            if (link) window.location.href = CompassState.buildURL(link);
        });

        // Клик по карточке КРИМИНОГЕННОСТЬ → переход на krim.php
        $('.trust-card').click(function(e) {
            if ($(this).hasClass('expanded')) return;
            if ($(e.target).closest('.custom-arrow-btn,.svodki-list-container,.card-actions').length) return;
            const link = $(this).data('link');
            if (link) window.location.href = CompassState.buildURL(link);
        });

        // Стрелка раскрытия
        $('.trust-card .custom-arrow-btn').click(function(e) {
            e.stopPropagation();
            const $card  = $(this).closest('.trust-card');
            const cardId = $card.data('card-id');
            const exp    = $card.hasClass('expanded');
            if (exp) {
                $card.removeClass('expanded');
                $(this).removeClass('expanded');
                $card.find('.svodki-list-container').removeClass('show');
                $card.find('.card-actions').removeClass('show');
                tempSelections[cardId] = [...(cardSelections[cardId] || <?php echo json_encode($default_selections); ?>)];
                loadSvodkiList().then(sl => { populateSvodkiList(cardId, $card.find('.svodki-list-container'), sl); updateCardDetails(cardId, $card, sl); });
            } else {
                $card.addClass('expanded'); $(this).addClass('expanded');
                $card.find('.svodki-list-container').addClass('show');
                $card.find('.card-actions').addClass('show');
            }
        });

        // Сохранить
        $('.trust-card .save-btn').click(function(e) {
            e.stopPropagation();
            const $card  = $(this).closest('.trust-card');
            const cardId = $card.data('card-id');
            cardSelections[cardId] = [...(tempSelections[cardId] || cardSelections[cardId])];
            // Сохраняем в localStorage (без БД)
            try { localStorage.setItem('compass_card_' + cardId, JSON.stringify(cardSelections[cardId])); } catch(_){}
            loadSvodkiList().then(sl => {
                updateCardDetails(cardId, $card, sl);
                $card.removeClass('expanded');
                $card.find('.custom-arrow-btn').removeClass('expanded');
                $card.find('.svodki-list-container').removeClass('show');
                $card.find('.card-actions').removeClass('show');
            });
        });

        // Отмена
        $('.trust-card .cancel-btn').click(function(e) {
            e.stopPropagation();
            const $card  = $(this).closest('.trust-card');
            const cardId = $card.data('card-id');
            tempSelections[cardId] = [...(cardSelections[cardId])];
            loadSvodkiList().then(sl => {
                populateSvodkiList(cardId, $card.find('.svodki-list-container'), sl);
                $card.removeClass('expanded');
                $card.find('.custom-arrow-btn').removeClass('expanded');
                $card.find('.svodki-list-container').removeClass('show');
                $card.find('.card-actions').removeClass('show');
            });
        });

        // Чекбоксы
        $(document).on('change', '.svodki-checkbox', function(e) {
            e.stopPropagation();
            const id     = $(this).data('id-svodki').toString();
            const cardId = $(this).closest('.trust-card').data('card-id');
            const $card  = $(this).closest('.trust-card');
            if (!tempSelections[cardId]) tempSelections[cardId] = [...(cardSelections[cardId] || [])];
            if ($(this).is(':checked') && tempSelections[cardId].length >= 3) {
                alert('Можно выбрать не более 3 пунктов.');
                $(this).prop('checked', false); return;
            }
            if ($(this).is(':checked')) tempSelections[cardId].push(id);
            else tempSelections[cardId] = tempSelections[cardId].filter(x => x !== id);
            loadSvodkiList().then(sl => updateCardDetails(cardId, $card, sl, true));
        });

        // Восстанавливаем сохранённый выбор из localStorage
        try {
            const saved = localStorage.getItem('compass_card_criminogenicity');
            if (saved) cardSelections['criminogenicity'] = JSON.parse(saved);
        } catch(_){}

        // Начальная загрузка карточек
        const weeks = splitIntoWeeks(initialStartDate, initialEndDate);
        updateCards(weeks);
    });

    async function loadSvodkiList() {
        try {
            const res  = await fetch('api/get_svodki_list.php', { method:'POST', headers:{'Content-Type':'application/json'} });
            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'error');
            return data.data.map(item => ({ ...item, id_svodki: String(item.id_svodki) }));
        } catch(err) {
            console.error('loadSvodkiList error:', err);
            return [
                {id_svodki:'1', number_naim:'1', naimenov:'Преступления', pole:1},
                {id_svodki:'2', number_naim:'2', naimenov:'% Раскрываемости', pole:1}
            ];
        }
    }

    function populateSvodkiList(cardId, $listContainer, svodkiList) {
        $listContainer.empty();
        const defaults = <?php echo json_encode($default_selections); ?>;
        const sorted = [...svodkiList].sort((a,b) => {
            const aD = defaults.includes(a.id_svodki), bD = defaults.includes(b.id_svodki);
            if (aD && !bD) return -1; if (!aD && bD) return 1;
            if (aD && bD) return defaults.indexOf(a.id_svodki) - defaults.indexOf(b.id_svodki);
            return parseFloat(a.number_naim) - parseFloat(b.number_naim);
        });
        sorted.forEach(item => {
            const checked = (tempSelections[cardId] || cardSelections[cardId] || defaults).includes(item.id_svodki);
            $listContainer.find('ul, .list-group').length === 0 && $listContainer.append('<ul class="list-group"></ul>');
            $listContainer.find('ul').append(`
                <li class="list-group-item">
                    <input type="checkbox" class="svodki-checkbox" id="svodki-${item.id_svodki}" data-id-svodki="${item.id_svodki}" ${checked?'checked':''}>
                    <label for="svodki-${item.id_svodki}">${item.naimenov} (${item.number_naim})</label>
                </li>`);
        });
    }

    async function updateCardDetails(cardId, $card, svodkiList, useTempSelections = false) {
        const defaults = <?php echo json_encode($default_selections); ?>;
        let selectedIds = useTempSelections ? (tempSelections[cardId] || cardSelections[cardId]) : (cardSelections[cardId] || defaults);

        const picker   = $('#date-range').data('daterangepicker');
        const startDate= picker ? picker.startDate : moment().subtract(1,'days');
        const endDate  = picker ? picker.endDate   : moment().subtract(1,'days');

        // Запрашиваем данные из суточной сводки
        let tableData = [];
        try {
            const res  = await fetch('api/output_sel_deg.php', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({
                    start_date: startDate.format('YYYY-MM-DD'),
                    end_date:   endDate.format('YYYY-MM-DD'),
                    garnizon:   parseInt(selectedGarnisonIndex) || 6
                })
            });
            const data = await res.json();
            if (data.success && data.data) tableData = data.data;
        } catch(e) { console.error('updateCardDetails fetch error:', e); }

        // Агрегируем
        const agg = {};
        tableData.forEach(item => {
            const k = item.id_svodki;
            if (!agg[k]) agg[k] = { ...item, kolichestvo:0, neraskr:0, raskr:0 };
            agg[k].kolichestvo += parseInt(item.kolichestvo)||0;
            agg[k].neraskr     += parseInt(item.neraskr)||0;
            agg[k].raskr       += parseInt(item.raskr)||0;
        });

        // Берём до 3 выбранных пунктов
        const displayItems = selectedIds.slice(0,3).map(id => {
            const sv = svodkiList.find(x => x.id_svodki === id) || {naimenov:'-', pole:1};
            const d  = agg[id] || {kolichestvo:0, neraskr:0, raskr:0};
            return { id_svodki:id, naimenov:sv.naimenov, pole:sv.pole, ...d };
        });
        while (displayItems.length < 3) displayItems.push({id_svodki:null, naimenov:'-', pole:1, kolichestvo:0, neraskr:0, raskr:0});

        const hasCrimes = selectedIds.includes('1');
        const crimesItem = agg['1'] || {kolichestvo:0, neraskr:0, raskr:0};

        $card.find('.details').each(function(index) {
            let label, value, color;
            const item = displayItems[index];
            if (index === 1 && hasCrimes) {
                label = 'НЕ РАСКРЫТЫ';
                value = (crimesItem.neraskr||0) - (crimesItem.raskr||0);
                color = value > 0 ? 'red' : 'green';
            } else if (index === 2 && selectedIds.includes('2')) {
                label = '% РАСКРЫВАЕМОСТИ';
                const total = crimesItem.kolichestvo || crimesItem.neraskr || 0;
                value = total > 0 ? ((crimesItem.raskr/total)*100).toFixed(2) : '0.00';
                color = parseFloat(value) > 0 ? 'green' : 'red';
            } else {
                label = item.naimenov.toUpperCase();
                value = item.pole === 2 ? item.neraskr : item.kolichestvo;
                color = parseFloat(value) > 0 ? 'red' : 'green';
            }
            $(this).find('span').eq(0).text(label);
            $(this).find('span').eq(1).text(value).css('color', color);
        });
    }

    async function updateCards(weeks) {
        const yesterday = moment().subtract(1,'days');
        $('.trust-card[data-card-id="criminogenicity"]').each(function() {
            const cardId = $(this).data('card-id');
            loadSvodkiList().then(sl => updateCardDetails(cardId, $(this), sl));
        });
    }

    function splitIntoWeeks(startDate, endDate) {
        const weeks = [];
        let cur = moment(startDate, ['DD.MM.YYYY','YYYY-MM-DD']).clone();
        const yesterday = moment().subtract(1,'days');
        while (cur.isSameOrBefore(endDate) && cur.isSameOrBefore(yesterday)) {
            let we = cur.clone().add(6,'days');
            if (we.isAfter(endDate)) we = endDate.clone();
            if (we.isAfter(yesterday)) we = yesterday.clone();
            weeks.push({ start:cur.format('DD.MM.YYYY'), end:we.format('DD.MM.YYYY') });
            cur.add(7,'days');
        }
        return weeks;
    }
    </script>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>КОМПАС</h1>
        </div>
    </div>

    <div class="date-selector">
        <div class="dropdown">
            <button class="btn" type="button" id="garnizon-dropdown">
                <?php echo $initialGarnisonText; ?>
            </button>
            <ul class="dropdown-menu" style="display:none;">
                <?php foreach ($garnizonNames as $idx => $name): ?>
                    <li><a class="dropdown-item" href="javascript:void(0);" data-index="<?php echo $idx; ?>"><?php echo $name; ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <input type="text" id="date-range" class="form-control" placeholder="Выберите дату">
    </div>

    <div class="container">
        <div class="card-wrapper">
            <div class="card trust-card" data-card-id="criminogenicity" data-link="pages/krim.php">
                <div class="title">КРИМИНОГЕННОСТЬ И ПРАВОПОРЯДОК</div>
                <div class="line"></div>
                <div class="details" data-detail-index="0">
                    <span>ПРЕСТУПЛЕНИЯ</span>
                    <span style="color:red;">0</span>
                </div>
                <div class="details" data-detail-index="1">
                    <span>НЕ РАСКРЫТЫ</span>
                    <span style="color:red;">0</span>
                </div>
                <div class="details" data-detail-index="2">
                    <span>% РАСКРЫВАЕМОСТИ</span>
                    <span style="color:green;">0.00</span>
                </div>
                <div class="svodki-list-container">
                    <ul class="list-group"></ul>
                </div>
                <div class="card-actions">
                    <button class="btn btn-primary save-btn">Сохранить</button>
                    <button class="btn btn-secondary cancel-btn">Отмена</button>
                </div>
                <button class="custom-arrow-btn" type="button"></button>
            </div>
        </div>
        <div class="card-wrapper">
            <div class="card" data-card-id="road_safety">
                <div class="title">БЕЗОПАСНОСТЬ ДОРОЖНОГО ДВИЖЕНИЯ</div>
                <div class="line"></div>
                <div class="details"><span>ДТП</span><span style="color:red;">0</span></div>
                <div class="details"><span>ПОГИБЛО</span><span style="color:red;">0</span></div>
                <div class="details"><span>РАНЕНО</span><span style="color:red;">0</span></div>
            </div>
        </div>
        <div class="card-wrapper">
            <div class="card" data-card-id="public_interaction" data-link="pages/krim.php">
                <div class="title">ВЗАИМОДЕЙСТВИЕ С ОБЩЕСТВОМ И ГРАЖДАНАМИ</div>
                <div class="line"></div>
                <div class="details"><span>РЕЙТИНГ ДОВЕРИЯ</span><span style="color:red;">0</span></div>
                <div class="details"><span>КОЛ-ВО ПОЗИТИВНЫХ ОТЗЫВОВ</span><span style="color:red;">0</span></div>
                <div class="details"><span>СТЕПЕНЬ УДОВЛЕТВОРЕНИЯ</span><span style="color:red;">0</span></div>
            </div>
        </div>
        <div class="card-wrapper">
            <div class="card" data-card-id="coordination">
                <div class="title">КООРДИНАЦИЯ И ВЗАИМОДЕЙСТВ., ГОС. СТРОИТЕЛЬСТВО</div>
                <div class="line"></div>
                <div class="details"><span>СОВМЕСТНЫХ СОГЛАШЕНИЙ</span><span style="color:red;">0</span></div>
                <div class="details"><span>ИЗМЕНЕНИЙ В ЗАКОНОДАТЕЛЬСТВО</span><span style="color:red;">0</span></div>
                <div class="details"><span>УЧАСТИЕ В ГОС. ПРОЕКТАХ</span><span style="color:red;">0</span></div>
            </div>
        </div>
    </div>

    <div class="footer">
        <button class="report-button" onclick="location.href=CompassState.buildURL('pages/report.php')">ОТЧЁТ О ДЕЯТЕЛЬНОСТИ</button>
        <p>© <?php echo date('Y'); ?> Компас. Все права защищены.</p>
    </div>
</body>
</html>



