<?php
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');

$garnizonNames = [
    '88' => 'МВД', '6' => 'Тирасполь', '5' => 'Бендеры',
    '31' => 'Слободзея', '10' => 'Григориополь', '13' => 'Дубоссары',
    '29' => 'Рыбница', '17' => 'Каменка',
];
$garnizon  = filter_input(INPUT_GET, 'garnizon', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '88';
$startDate = filter_input(INPUT_GET, 'start',    FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? date('d.m.Y', strtotime('-30 days'));
$endDate   = filter_input(INPUT_GET, 'end',      FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? date('d.m.Y', strtotime('-1 day'));
if (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $startDate)) $startDate = date('d.m.Y', strtotime('-30 days'));
if (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $endDate))   $endDate   = date('d.m.Y');
$initialGarnisonText = strtoupper($garnizonNames[$garnizon] ?? 'ГАРНИЗОН');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ГЕО — карта происшествий</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="../assets/js/daterangepicker.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.min.js"></script>
    <script src="../assets/js/compass_state.js"></script>
    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Leaflet.MarkerCluster -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.1/daterangepicker.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; }
        body { background: #f4f4f4; color: #333; padding-top: 20px;
               display: flex; flex-direction: column; min-height: 100vh; }
        .header { display: flex; justify-content: center; align-items: center;
                  margin-bottom: 20px; height: 60px; }
        h1 { margin: 0; text-align: center; color: #4682B4; font-size: 36px;
             font-weight: bold; line-height: 1; }
        .container { display: flex; flex-direction: column; align-items: center;
                     gap: 20px; padding: 0 15px; max-width: 1400px; margin: 0 auto; }
        /* ── Toolbar ── */
        .date-selector { display: flex; align-items: center; gap: 10px;
                         width: 100%; max-width: 1200px; }
        .date-selector .centered-input { flex: 1; max-width: 35%; min-width: 300px; }
        .date-selector button,
        .date-selector #back-button,
        .date-selector .dropdown > button {
            width: 180px; height: 50px;
            background-color: #4682B4; color: #fff;
            border: 1px solid #4682B4; border-radius: 8px;
            font-size: 18px; font-weight: 600; padding: 10px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            cursor: pointer; text-decoration: none;
            display: inline-flex; align-items: center; justify-content: center;
            transition: background-color 0.3s, border-color 0.3s;
            text-transform: uppercase;
        }
        .date-selector button:hover,
        .date-selector #back-button:hover,
        .date-selector .dropdown > button:hover {
            background-color: #fff; color: #4682B4; border-color: #4682B4;
        }
        .date-selector input {
            padding: 10px; border: 1px solid #4682B4; border-radius: 8px;
            font-size: 18px; font-weight: 600; text-align: center;
            height: 50px; cursor: pointer;
        }
        .dropdown-toggle::after { display: none !important; }
        .dropdown-menu.show { width: 180px; }
        /* ── ИИ+ГЕО pair: 180px total ── */
        .btn-pair { display: flex; gap: 6px; width: 180px; flex-shrink: 0; }
        .btn-pair button { width: auto; flex: 1; font-size: 16px; padding: 0; }
        /* ── Map ── */
        .map-wrapper {
            width: 100%; max-width: 1200px;
            border-radius: 12px; overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            margin-bottom: 40px;
        }
        #geo-map { width: 100%; height: calc(100vh - 200px); min-height: 500px; }
        /* ── Cluster custom icons ── */
        .geo-cluster { border-radius: 50%; display: flex; align-items: center;
                       justify-content: center; font-weight: bold;
                       border: 2px solid rgba(0,0,0,0.25);
                       box-shadow: 0 2px 6px rgba(0,0,0,0.35); }
        /* ── Popup ── */
        .geo-popup { min-width: 220px; max-width: 320px; }
        .geo-popup h6 { font-size: 13px; font-weight: 700; margin-bottom: 6px;
                        color: #1a2c42; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        .geo-popup .inc-item { margin-bottom: 5px; padding: 4px 6px;
                               background: #f0f4f8; border-radius: 4px;
                               cursor: pointer; transition: filter 0.15s; }
        .geo-popup .inc-item:hover { filter: brightness(0.95); }
        .geo-popup .inc-item strong { font-size: 12px; color: #4682B4; display: block; }
        .geo-popup .inc-item.inc-important strong { color: #c0392b; }
        .geo-popup .inc-item.inc-important { background: #fff0ee;
                                             border-left: 3px solid #c0392b; }
        .geo-popup .inc-item span { font-size: 11px; color: #555; }
        .geo-popup .inc-link { font-size: 11px; color: #4682B4; text-decoration: none;
                               float: right; margin-top: 2px; pointer-events: none; }
        .geo-popup .inc-item:hover .inc-link { text-decoration: underline; }
        /* ── Legend ── */
        .map-legend { position: absolute; bottom: 30px; right: 10px; z-index: 1000;
                      background: rgba(255,255,255,0.92); border-radius: 8px;
                      padding: 10px 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                      font-size: 12px; }
        .legend-item { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
        .legend-dot { width: 18px; height: 18px; border-radius: 50%;
                      border: 2px solid rgba(0,0,0,0.2); flex-shrink: 0; }
        /* ── Loading ── */
        #map-loading { display: none; position: absolute; top: 50%; left: 50%;
                       transform: translate(-50%,-50%); background: rgba(255,255,255,0.9);
                       padding: 20px 30px; border-radius: 8px; z-index: 2000;
                       font-size: 16px; color: #4682B4; font-weight: 600;
                       box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        /* ── Stats bar ── */
        #geo-stats { width: 100%; max-width: 1200px;
                     display: flex; gap: 12px; flex-wrap: wrap; }
        .stat-chip { background: #fff; border: 1px solid #ddd; border-radius: 20px;
                     padding: 6px 14px; font-size: 13px; color: #444;
                     box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .stat-chip b { color: #4682B4; }
        /* ── Filter chips ── */
        .stat-chip.filter-chip { cursor: pointer; transition: background 0.2s, color 0.2s, border-color 0.2s; }
        .stat-chip.filter-chip:hover { border-color: #4682B4; background: #e8f0fa; }
        .stat-chip.filter-chip.active {
            background: #4682B4; color: #fff; border-color: #4682B4;
        }
        .stat-chip.filter-chip.active b { color: #fff; }
        .stat-chip.chip-all { border-style: dashed; }
        .stat-chip.chip-all.active { border-style: solid; }
        /* ── Fullscreen ── */
        .map-wrapper.fullscreen {
            position: fixed !important; top: 0; left: 0; right: 0; bottom: 0;
            width: 100% !important; max-width: none !important;
            height: 100% !important; z-index: 9000;
            border-radius: 0 !important; margin: 0 !important;
        }
        .map-wrapper.fullscreen #geo-map { height: 100% !important; }
        #fullscreen-btn {
            position: absolute; top: 10px; right: 10px; z-index: 1000;
            background: rgba(255,255,255,0.9); border: 1px solid #ccc;
            border-radius: 6px; padding: 6px 10px; cursor: pointer;
            font-size: 18px; line-height: 1; box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            transition: background 0.2s;
        }
        #fullscreen-btn:hover { background: #fff; }
        /* ── Incident modal ── */
        #incModal .modal-header { background: #1a2c42; color: #d0d8e4; }
        #incModal .modal-body { background: #f0f2f5; }
        .drp-calendar.right { display: none !important; }
        .daterangepicker { min-width: auto !important; z-index: 10000 !important; }
    </style>
</head>
<body>
<div class="header"><h1>ГЕО — КАРТА ПРОИСШЕСТВИЙ</h1></div>
<div class="container">

    <!-- ── Toolbar ── -->
    <div class="date-selector">
        <a id="back-button" href="svodki.php">НАЗАД</a>

        <div class="dropdown">
            <button class="dropdown-toggle" type="button" id="garnizon-btn" data-bs-toggle="dropdown">
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

        <input type="text" id="date-range" class="form-control centered-input" placeholder="Выберите период">

        <button id="refresh-btn">ОБНОВИТЬ</button>

        <!-- ИИ + ГЕО не нужны на гео-странице, но кнопка возврата к ним есть через НАЗАД -->
    </div>

    <!-- ── Stats ── -->
    <div id="geo-stats"></div>

    <!-- ── Map ── -->
    <div class="map-wrapper" id="map-wrapper" style="position:relative;">
        <button id="fullscreen-btn" title="На весь экран">⛶</button>
        <div id="map-loading">Загрузка данных...</div>
        <div id="geo-map"></div>
    </div>

</div>

<!-- ── Incident detail modal ── -->
<div class="modal fade" id="incModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="incModalTitle">Происшествие</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="incModalBody" style="white-space:pre-wrap;font-size:15px;line-height:1.6;"></div>
        </div>
    </div>
</div>

<script>
// ── State ──────────────────────────────────────────────────────────────────
const garnizonNames = {
    '88':'МВД','6':'ТИРАСПОЛЬ','5':'БЕНДЕРЫ','31':'СЛОБОДЗЕЯ',
    '10':'ГРИГОРИОПОЛЬ','13':'ДУБОССАРЫ','29':'РЫБНИЦА','17':'КАМЕНКА'
};
let selectedGarnizon = '<?php echo htmlspecialchars($garnizon); ?>';
let allIncidents  = [];
let currentFilter = null;  // null = все типы, string = конкретный тип

// ── Date picker ────────────────────────────────────────────────────────────
$('#date-range').daterangepicker({
    startDate: '<?php echo htmlspecialchars($startDate); ?>',
    endDate:   '<?php echo htmlspecialchars($endDate); ?>',
    maxDate: moment().subtract(1, 'days'),
    locale: {
        format: 'DD.MM.YYYY',
        separator: ' по ',
        applyLabel: 'Применить', cancelLabel: 'Отмена',
        customRangeLabel: 'Выбрать даты',
        daysOfWeek: ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'],
        monthNames: ['Январь','Февраль','Март','Апрель','Май','Июнь',
                     'Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
        firstDay: 1
    },
    opens: 'center',
    autoUpdateInput: true,
    linkedCalendars: false,
    autoApply: true,
    isInvalidDate: function(date) {
        return date.isSame(moment(), 'day') || date.isAfter(moment(), 'day');
    }
});

// Патч: перерисовываем подсветку при смене месяца
const _origUpdateCals = $.fn.daterangepicker.prototype.updateCalendars;
$.fn.daterangepicker.prototype.updateCalendars = function() {
    _origUpdateCals.apply(this, arguments);
    setTimeout(() => markDatesInCalendar(), 100);
};

$('#date-range').on('show.daterangepicker', function() {
    setTimeout(() => markDatesInCalendar(), 200);
});
$('#date-range').on('showCalendar.daterangepicker', function() {
    setTimeout(() => markDatesInCalendar(), 200);
});
$('#date-range').on('apply.daterangepicker', function() {
    loadGeoData();
});

// ── Garrison picker ────────────────────────────────────────────────────────
$('#garnizon-menu').on('click', '.dropdown-item', function() {
    selectedGarnizon = $(this).data('index').toString();
    $('#garnizon-btn').text(garnizonNames[selectedGarnizon] || 'ГАРНИЗОН');
    loadGeoData();
    markDatesInCalendar();
});

$('#refresh-btn').on('click', function() { loadGeoData(); });

// ── Map ────────────────────────────────────────────────────────────────────
const map = L.map('geo-map', {
    center: [47.1, 29.0],
    zoom: 9,
    zoomControl: true,
});

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 18,
}).addTo(map);

// Граница ПМР (приблизительный прямоугольник)
const pmrBounds = L.latLngBounds([46.5, 28.4], [48.5, 30.2]);
map.setMaxBounds(pmrBounds.pad(0.3));

let markerClusterGroup = null;

// ── Cluster icon factory ───────────────────────────────────────────────────
function clusterColor(count) {
    if (count === 1)     return { bg: '#F5DEB3', text: '#555', size: 32 };
    if (count <= 3)      return { bg: '#FFE066', text: '#555', size: 36 };
    if (count <= 7)      return { bg: '#FF8C00', text: '#fff', size: 40 };
    if (count <= 15)     return { bg: '#E84C00', text: '#fff', size: 44 };
    return               { bg: '#DC143C', text: '#fff', size: 48 };
}

function makeMarkerIcon(count) {
    const c = clusterColor(count);
    const fontSize = count === 1 ? 12 : (c.size > 40 ? 15 : 13);
    return L.divIcon({
        html: `<div class="geo-cluster" style="background:${c.bg};color:${c.text};width:${c.size}px;height:${c.size}px;font-size:${fontSize}px;">${count}</div>`,
        className: '',
        iconSize: [c.size, c.size],
        iconAnchor: [c.size / 2, c.size / 2],
    });
}

// ── Load data ──────────────────────────────────────────────────────────────
function loadGeoData() {
    const picker = $('#date-range').data('daterangepicker');
    const startISO = picker.startDate.format('YYYY-MM-DD');
    const endISO   = picker.endDate.format('YYYY-MM-DD');

    $('#map-loading').show();

    $.ajax({
        url: '../api/get_geo_incidents.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ garnizon: selectedGarnizon, startDate: startISO, endDate: endISO }),
        success: function(resp) {
            $('#map-loading').hide();
            if (!resp.success) return;
            allIncidents  = resp.incidents || [];
            currentFilter = null;
            renderMap(allIncidents);
            renderStats(allIncidents);
        },
        error: function() { $('#map-loading').hide(); }
    });
}

// ── Render map markers ─────────────────────────────────────────────────────
function renderMap(incidents) {
    if (markerClusterGroup) {
        markerClusterGroup.clearLayers();
        map.removeLayer(markerClusterGroup);
    }

    markerClusterGroup = L.markerClusterGroup({
        iconCreateFunction: function(cluster) {
            // Суммируем инциденты во всех дочерних маркерах (каждый хранит группу)
            let total = 0;
            cluster.getAllChildMarkers().forEach(m => {
                total += m._groupSize || 1;
            });
            return makeMarkerIcon(total);
        },
        maxClusterRadius: 60,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
        disableClusteringAtZoom: 16,
    });

    // Группируем инциденты по близкому адресу (3 знака ≈ 110м)
    // чтобы кружок сразу показывал кол-во на точке
    const locGroups = {};
    incidents.forEach(function(inc) {
        const key = inc.lat.toFixed(3) + ',' + inc.lng.toFixed(3);
        if (!locGroups[key]) locGroups[key] = [];
        locGroups[key].push(inc);
    });

    Object.values(locGroups).forEach(function(group) {
        const avgLat = group.reduce((s, i) => s + i.lat, 0) / group.length;
        const avgLng = group.reduce((s, i) => s + i.lng, 0) / group.length;
        const count  = group.length;
        const marker = L.marker([avgLat, avgLng], { icon: makeMarkerIcon(count) });

        marker.bindPopup(buildGroupPopup(group), { maxWidth: 360, minWidth: 240 });
        marker._groupSize = count;
        marker._groupData = group;
        markerClusterGroup.addLayer(marker);
    });

    // При клике на зум-кластер показываем список если все близко
    markerClusterGroup.on('clusterclick', function(e) {
        const cluster  = e.layer;
        const children = cluster.getAllChildMarkers();
        const lats = [...new Set(children.map(m => m.getLatLng().lat.toFixed(3)))];
        const lngs = [...new Set(children.map(m => m.getLatLng().lng.toFixed(3)))];
        // Один адресный квадрат — показываем попап вместо зума
        if (lats.length === 1 && lngs.length === 1) {
            const allInc = children.flatMap(m => m._groupData || []);
            const popup  = L.popup({ maxWidth: 360, minWidth: 240 })
                .setLatLng(cluster.getLatLng())
                .setContent(buildGroupPopup(allInc));
            popup.openOn(map);
            L.DomEvent.stop(e);
        }
        // иначе — стандартный зум
    });

    map.addLayer(markerClusterGroup);

    // Подгоняем вид под данные
    if (incidents.length > 0) {
        const group = L.featureGroup(
            incidents.map(i => L.marker([i.lat, i.lng]))
        );
        const bounds = group.getBounds().pad(0.15);
        map.fitBounds(bounds, { maxZoom: 13 });
    }
}

function buildGroupPopup(group) {
    const addr = group[0].address || '';
    let html = `<div class="geo-popup"><h6>${escapeHtml(addr)}</h6>`;
    // Важные — сначала
    const sorted = [...group].sort((a, b) => (b.is_important ? 1 : 0) - (a.is_important ? 1 : 0));
    sorted.forEach(function(inc) {
        const dateDisp = inc.date ? moment(inc.date, 'YYYY-MM-DD').format('DD.MM.YYYY') : '';
        const cls = inc.is_important ? ' inc-important' : '';
        html += `<div class="inc-item${cls}" onclick="showIncidentModal(${inc.id})">
            <strong>${escapeHtml(inc.type)}</strong>
            <span>КУЗП ${inc.numb} &bull; ${dateDisp}</span>
            <a class="inc-link" href="javascript:void(0);">Подробнее →</a>
        </div>`;
    });
    html += '</div>';
    return html;
}

// ── Stats bar + фильтры ────────────────────────────────────────────────────
function renderStats(incidents) {
    const total  = incidents.length;
    const imp    = incidents.filter(i => i.is_important).length;
    const byType = {};
    incidents.forEach(i => { byType[i.type] = (byType[i.type] || 0) + 1; });
    const sorted = Object.entries(byType).sort((a, b) => b[1] - a[1]);

    const allActive = currentFilter === null ? ' active' : '';
    let html = `<div class="stat-chip chip-all filter-chip${allActive}" data-filter="">ВСЕ: <b>${total}</b></div>`;
    if (imp) html += `<div class="stat-chip">Важных: <b>${imp}</b></div>`;
    sorted.forEach(([t, c]) => {
        const active = currentFilter === t ? ' active' : '';
        html += `<div class="stat-chip filter-chip${active}" data-filter="${escapeHtml(t)}">${escapeHtml(t)}: <b>${c}</b></div>`;
    });
    $('#geo-stats').html(html);

    // Обработчики кликов по фильтрам
    $('#geo-stats').off('click', '.filter-chip').on('click', '.filter-chip', function() {
        const type = $(this).data('filter');
        setTypeFilter(type === '' ? null : type);
    });
}

function setTypeFilter(type) {
    currentFilter = type;
    const filtered = type ? allIncidents.filter(i => i.type === type) : allIncidents;
    renderMap(filtered);
    // Обновляем активное состояние чипов
    $('#geo-stats .filter-chip').each(function() {
        const chipType = $(this).data('filter');
        const isActive = (type === null && chipType === '') || (chipType === type);
        $(this).toggleClass('active', isActive);
    });
}

// ── Incident modal ─────────────────────────────────────────────────────────
function showIncidentModal(id) {
    const inc = allIncidents.find(i => i.id === id);
    if (!inc) return;
    const dateDisp = inc.date ? moment(inc.date, 'YYYY-MM-DD').format('DD.MM.YYYY') : '';
    $('#incModalTitle').text(
        `${inc.garnizon_name.toUpperCase()}. ${inc.type}. КУЗП ${inc.numb} от ${dateDisp}г.`
    );
    $('#incModalBody').html(escapeHtml(inc.text || '').replace(/\n/g, '<br>'));
    const existingModal = bootstrap.Modal.getInstance($('#incModal')[0]);
    if (existingModal) existingModal.dispose();
    new bootstrap.Modal($('#incModal')[0]).show();
}

// ── Legend ─────────────────────────────────────────────────────────────────
const legend = L.control({ position: 'bottomright' });
legend.onAdd = function() {
    const div = L.DomUtil.create('div', 'map-legend');
    div.innerHTML = `
        <div style="font-weight:700;margin-bottom:6px;font-size:12px;color:#333;">Кол-во происшествий</div>
        <div class="legend-item"><div class="legend-dot" style="background:#F5DEB3;"></div> 1</div>
        <div class="legend-item"><div class="legend-dot" style="background:#FFE066;"></div> 2–3</div>
        <div class="legend-item"><div class="legend-dot" style="background:#FF8C00;"></div> 4–7</div>
        <div class="legend-item"><div class="legend-dot" style="background:#E84C00;"></div> 8–15</div>
        <div class="legend-item"><div class="legend-dot" style="background:#DC143C;"></div> 16+</div>
`;
    return div;
};
legend.addTo(map);

// ── Utils ──────────────────────────────────────────────────────────────────
function escapeHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Calendar date underlines ──────────────────────────────────────────────
async function markDatesInCalendar() {
    try {
        const response = await fetch('../api/marked_dates.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ garnizon: selectedGarnizon, mode: 'svodki' })
        });
        const data = await response.json();
        if (!data.success || !data.dates) return;
        const allMarkedDates = [];
        data.dates.forEach(item => {
            let current = moment(item.start, 'YYYY-MM-DD');
            const end   = moment(item.end,   'YYYY-MM-DD');
            const color = item.color || '#FF0000';
            while (current.isSameOrBefore(end) && current.isSameOrBefore(moment().subtract(1, 'days'))) {
                allMarkedDates.push({ date: current.format('YYYY-MM-DD'), color });
                current.add(1, 'days');
            }
        });
        applyColorsToCalendar(allMarkedDates);
    } catch (e) { /* silent */ }
}

function applyColorsToCalendar(markedDates) {
    $('.drp-calendar tbody td').css('position', 'relative').find('.calendar-underline').remove();
    setTimeout(() => {
        const highlighted = [];
        $('.drp-calendar tbody td').each(function() {
            if ($(this).hasClass('week') || !$(this).text().trim()) return;
            const dateText  = $(this).text().trim();
            const monthYear = $(this).closest('.drp-calendar').find('.month').text().trim();
            const formatted = convertToFullDate(dateText, monthYear);
            if (!$(this).hasClass('off') && isValidDateInCurrentMonth(formatted, monthYear)) {
                const md = markedDates.find(d => d.date === formatted);
                if (md && !highlighted.includes(formatted)) {
                    $(this).css('color', '#000').append(
                        `<span class="calendar-underline" style="position:absolute;bottom:2px;left:50%;width:50%;height:2px;background:${md.color};transform:translateX(-50%);"></span>`
                    );
                    highlighted.push(formatted);
                }
            }
        });
    }, 300);
}

function isValidDateInCurrentMonth(date, monthYear) {
    const months = {
        'Январь':'01','Февраль':'02','Март':'03','Апрель':'04','Май':'05','Июнь':'06',
        'Июль':'07','Август':'08','Сентябрь':'09','Октябрь':'10','Ноябрь':'11','Декабрь':'12'
    };
    const [monthName, year] = monthYear.split(' ');
    const month = months[monthName];
    const d = moment(date, 'YYYY-MM-DD');
    return d.month() + 1 === parseInt(month) && d.year() === parseInt(year);
}

function convertToFullDate(day, monthYear) {
    const months = {
        'Январь':'01','Февраль':'02','Март':'03','Апрель':'04','Май':'05','Июнь':'06',
        'Июль':'07','Август':'08','Сентябрь':'09','Октябрь':'10','Ноябрь':'11','Декабрь':'12'
    };
    const [monthName, year] = monthYear.split(' ');
    return `${year}-${months[monthName]}-${day.padStart(2, '0')}`;
}

// ── Fullscreen ────────────────────────────────────────────────────────────
$('#fullscreen-btn').on('click', function() {
    const wrapper = $('#map-wrapper');
    const isFs = wrapper.hasClass('fullscreen');
    wrapper.toggleClass('fullscreen', !isFs);
    $(this).text(isFs ? '⛶' : '✕');
    $(this).attr('title', isFs ? 'На весь экран' : 'Свернуть');
    setTimeout(function() { map.invalidateSize(); }, 100);
});
$(document).on('keydown', function(e) {
    if (e.key === 'Escape' && $('#map-wrapper').hasClass('fullscreen')) {
        $('#map-wrapper').removeClass('fullscreen');
        $('#fullscreen-btn').text('⛶').attr('title', 'На весь экран');
        setTimeout(function() { map.invalidateSize(); }, 100);
    }
});

// ── Init ───────────────────────────────────────────────────────────────────
$(function() {
    // Синхронизируем garnizon из CompassState если есть
    if (window.CompassState) {
        const st = CompassState.getState();
        if (st && st.garnizon) selectedGarnizon = String(st.garnizon);
    }
    loadGeoData();
});
</script>
</body>
</html>
