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
$initialSubMode = '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Селектор</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="../assets/js/compass_state.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.1/daterangepicker.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="../assets/css/style_selector.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.1/daterangepicker.css">
<style>
    .container {
        padding-bottom: 30px;
        min-width: 800px;
        margin: 0 auto;
        max-width: 1520px !important;
    }
    #accordion-container {
        --accordion-width: 1000px;
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
    /* Фиксация панели выбора дат (sticky) */
    .date-selector {
        position: sticky;
        top: 0;
        background: #fff;
        z-index: 999;
        padding: 15px 20px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-bottom: 1px solid #dee2e6;
    }
    /* Отступ для контента под фиксированной панелью */
    .table-container {
        margin-top: 20px;
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

    .daterangepicker {
        z-index: 10000 !important;
    }
    .drp-calendar.right { display:none !important; }
    .daterangepicker { min-width:auto !important; }
    #instruction-btn {
        position: fixed;
        bottom: 20px;
        right: 20px;
        display: block;
        width: 40px;
        height: 40px;
        background-color: #4682B4;
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 24px;
        font-weight: bold;
        line-height: 40px;
        text-align: center;
        cursor: pointer;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        transition: background-color 0.3s, transform 0.3s;
        z-index: 998;
    }
    #instruction-btn:hover {
        background-color: #355f8d;
        transform: scale(1.1);
    }
    #back-to-top {
        display: none;
        position: fixed;
        bottom: 70px;
        right: 20px;
        z-index: 1000;
        width: 40px;
        height: 40px;
        background-color: #4682B4;
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 30px;
        line-height: 30px;
        text-align: center;
        cursor: pointer;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        transition: opacity 0.3s, transform 0.3s;
    }
    #back-to-top:hover {
        background-color: #355f8d;
        transform: scale(1.1);
    }
    .modal-content {
        border: none;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(70, 130, 180, 0.25);
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    }
    .modal-header {
        background: linear-gradient(135deg, #4682B4 0%, #355f8d 100%);
        color: white;
        border: none;
        border-radius: 12px 12px 0 0;
        padding: 25px;
        box-shadow: 0 2px 8px rgba(70, 130, 180, 0.15);
    }
    .modal-header .modal-title {
        font-weight: 700;
        font-size: 22px;
        letter-spacing: 0.5px;
    }
    .instruction-body {
        background-color: #fff;
        padding: 30px;
        max-height: 75vh;
        overflow-y: auto;
        border-radius: 0;
        font-size: 16px;
        line-height: 1.8;
        color: #333;
    }
    .instruction-body::-webkit-scrollbar {
        width: 8px;
    }
    .instruction-body::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    .instruction-body::-webkit-scrollbar-thumb {
        background: #4682B4;
        border-radius: 4px;
    }
    .instruction-body::-webkit-scrollbar-thumb:hover {
        background: #355f8d;
    }
    .instruction-section {
        margin-bottom: 30px;
    }
    .instruction-section:last-child {
        margin-bottom: 0;
    }
    .instruction-section h2 {
        color: #355f8d;
        font-size: 18px;
        font-weight: 700;
        margin: 0 0 15px 0;
        padding-bottom: 10px;
        border-bottom: 3px solid #4682B4;
        display: inline-block;
    }
    .instruction-section p {
        margin: 15px 0;
        font-size: 16px;
        line-height: 1.8;
    }
    .instruction-section code {
        background-color: #f0f4f8;
        color: #c7254e;
        padding: 2px 6px;
        border-radius: 4px;
        font-family: 'Courier New', monospace;
        font-weight: 600;
    }
    .instruction-list {
        list-style: none;
        padding-left: 0;
        margin: 15px 0;
    }
    .instruction-list li {
        margin: 12px 0;
        padding-left: 28px;
        position: relative;
        font-size: 16px;
        line-height: 1.8;
    }
    .instruction-list li:before {
        content: "▸";
        position: absolute;
        left: 0;
        color: #4682B4;
        font-weight: bold;
        font-size: 18px;
    }
    .instruction-sublist {
        list-style: none;
        padding-left: 20px;
        margin: 10px 0 10px 0;
    }
    .instruction-sublist li {
        margin: 8px 0;
        padding-left: 24px;
        position: relative;
        font-size: 15px;
        line-height: 1.7;
        color: #555;
    }
    .instruction-sublist li:before {
        content: "◦";
        position: absolute;
        left: 0;
        color: #4682B4;
        font-weight: bold;
    }
    .instruction-section strong {
        color: #4682B4;
        font-weight: 700;
    }
    .modal-footer {
        background-color: #f8f9fa;
        border-top: 1px solid #e9ecef;
        border-radius: 0 0 12px 12px;
        padding: 20px;
    }
    .modal-footer .btn {
        font-weight: 600;
        font-size: 16px;
        padding: 12px 40px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    .modal-footer .btn-primary {
        background-color: #4682B4;
        border-color: #4682B4;
        color: white;
    }
    .modal-footer .btn-primary:hover {
        background-color: #355f8d;
        border-color: #355f8d;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(70, 130, 180, 0.3);
    }
    .modal.fade .modal-dialog {
        transition: transform 0.3s ease-out;
    }
    .mvd-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        padding: 8px 12px;
    }
    .mvd-text {
        flex: 1;
    }
    .arrow-4 {
        margin-left: 20px;
        display: flex;
        align-items: center;
    }
    .section-header {
        position: relative;
        background: linear-gradient(135deg,#ffffff 0%,#f8f9fa 100%);
        padding: 28px 25px;
        font-size: 26px;
        font-weight: bold;
        color: #4682B4;
        border-left: 6px solid #4682B4;
        border-bottom: 2px solid #e9f2fa;
        cursor: pointer;
        user-select: none;
        transition: all 0.3s ease;
        margin: 15px auto 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        min-width: var(--accordion-width);
        max-width: var(--accordion-width);
        box-sizing: border-box;
    }
    .section-header:hover {
        background: linear-gradient(135deg,#4682B4 0%,#355f8d 100%);
        color: white;
        box-shadow: 0 6px 16px rgba(70,130,180,0.4);
    }
    .section-header.open {
        background: linear-gradient(135deg,#4682B4 0%,#355f8d 100%);
        color: white;
        box-shadow: 0 6px 16px rgba(70,130,180,0.4);
    }
    .section-header .toggle-icon {
        position: relative;
        width: 32px;
        height: 32px;
        margin-left: 15px;
    }
    .section-header .toggle-icon::before,
    .section-header .toggle-icon::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 100%;
        height: 4px;
        background-color: currentColor;
        transition: transform 0.4s ease;
    }
    .section-header .toggle-icon::before {
        transform: translate(-50%, -50%) rotate(0deg);
    }
    .section-header .toggle-icon::after {
        transform: translate(-50%, -50%) rotate(90deg);
    }
    .section-header.open .toggle-icon::before {
        transform: translate(-50%, -50%) rotate(45deg);
    }
    .section-header.open .toggle-icon::after {
        transform: translate(-50%, -50%) rotate(-45deg);
    }
    .section-content {
        display: none;
        padding: 30px;
        background: linear-gradient(135deg,#fafbfc 0%,#f5f7fa 100%);
        border-bottom: 3px solid #e9f2fa;
        margin: 0 auto 10px auto;
        overflow-x: auto;
        min-width: var(--accordion-width);
        max-width: var(--accordion-width);
        box-sizing: border-box;
    }
    .section-table {
        width: 100%;
        margin-bottom: 0;
        border-collapse: collapse;
        background-color: #fff;
        table-layout: fixed;
    }
    .section-table tbody tr:nth-child(odd) {
        background-color: #ffffff;
    }
    .section-table tbody tr:nth-child(even) {
        background-color: #f8f9fa;
    }
    .section-table tbody tr:hover {
        background-color: #e9f2fa !important;
    }
    .section-table tbody td {
        background-color: inherit;
    }
    .section-table th,
    .section-table td {
        text-align: center;
        border: 1px solid #dee2e6;
        font-size: 18px;
        font-weight: 400;
        vertical-align: middle;
        height: 40px;
        box-sizing: border-box;
    }
    .section-table th {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: 600;
        background-color: #f8f9fa;
        color: #4682B4;
    }
    .section-table td:nth-child(2) {
        text-align: left;
    }
    .section-table tbody tr {
        height: 60px;
    }
    .sub-point {
        padding-left: 30px;
        display: inline-block;
        color: #555;
        font-style: italic;
    }
.section-table td input {
    text-align: center;
    width: 100%;
    box-sizing: border-box;
    font-size: 18px;
    height: 40px;
    padding: 2px;
    border: 1px solid #dee2e6 !important;
    background-color: #fff !important;
    transition: border-color 0.2s, background-color 0.2s;
}
.section-table td input:focus {
    border-color: #4682B4 !important;
    background-color: #fff !important;
    outline: none;
}
.section-table tbody input:disabled,
.section-table tbody input[readonly] {
    background-color: #f8f9fa !important;
    cursor: not-allowed;
    color: #666;
    border-color: #dee2e6 !important;
}
    .section-table.mvd-table-mode {
        width: 100% !important;
        min-width: unset !important;
        table-layout: fixed;
    }
    .section-table.mvd-table-mode th,
    .section-table.mvd-table-mode td {
        font-size: 18px;
        padding: 4px 2px;
        word-wrap: break-word;
        overflow: hidden;
    }
    .section-table.mvd-table-mode th:nth-child(1),
    .section-table.mvd-table-mode td:nth-child(1) { width: 4%; }
    .section-table.mvd-table-mode th:nth-child(2),
    .section-table.mvd-table-mode td:nth-child(2) {
        width: 24%;
        text-align: left;
        padding-left: 8px;
        word-break: break-word;
        white-space: normal;
    }
    .section-table.mvd-table-mode th:nth-child(n+3),
    .section-table.mvd-table-mode td:nth-child(n+3) {
        width: 9%;
        text-align: center;
    }
.mismatch {
        position: relative;
        color: red;
    }
    .mismatch input {
        color: red !important;
        border: 2px solid red !important;
        background-color: #ffeeee !important;
    }
    .expected-text {
        display: block;
        color: red;
        font-weight: bold;
        font-size: 14px;
        margin-top: 4px;
        white-space: nowrap;
</style>
</head>
<body>
    <div class="header" id="top">
        <div class="header-content">
            <h1>СЕЛЕКТОР</h1>
        </div>
    </div>
    <div class="container mt-3">
        <div class="date-selector">
            <button class="btn btn-primary" id="back-button" onclick="window.location.href='../index.php'">НАЗАД</button>
            <div class="dropdown">
                <button class="btn btn-primary" type="button" id="garnizon-dropdown">
                    <?php echo htmlspecialchars($initialGarnisonText); ?>
                </button>
                <ul class="dropdown-menu" style="display: none;">
                    
                    <li class="dropdown-submenu">
                        <div class="dropdown-item mvd-item" data-index="88" style="cursor: pointer;">
                            <span class="mvd-text">МВД</span>
                            <div class="arrow-4">
                                <span class="arrow-4-left"></span>
                                <span class="arrow-4-right"></span>
                            </div>
                        </div>
                        <ul class="dropdown-submenu-items" style="display: none;">
                            <li><a class="dropdown-item submenu-item" href="javascript:void(0);" data-index="88" data-mode="1">Сумма</a></li>
                            <li><a class="dropdown-item submenu-item" href="javascript:void(0);" data-index="88" data-mode="0">Таблица</a></li>
                        </ul>
                    </li>
                    <li><a class="dropdown-item" href="javascript:void(0);" data-index="6">Тирасполь</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);" data-index="5">Бендеры</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);" data-index="31">Слободзея</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);" data-index="10">Григориополь</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);" data-index="13">Дубоссары</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);" data-index="29">Рыбница</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);" data-index="17">Каменка</a></li>
                    
                </ul>
            </div>
            <input type="text" id="date-range" class="form-control" value="<?php echo htmlspecialchars($startDate . ' по ' . $endDate); ?>">
            <button id="show-button" class="btn btn-primary">ГРАФИК</button>
            <button id="ai-button" class="btn btn-primary">ИИ</button>
            <button id="geo-button" class="btn btn-primary">ГЕО</button>
        </div>
        <div id="readonly-banner"></div>
        <div class="table-container">
            <div id="accordion-container"></div>
        </div>
        <div class="save-button-container">
            <button class="btn btn-primary" id="save-button" style="display: none;" onclick="saveData()">СОХРАНИТЬ</button>
        </div>
    </div>
    <button id="back-to-top" title="Наверх">&uarr;</button>
    <button id="instruction-btn" title="Инструкция">i</button>
<div class="modal fade" id="instructionModal" tabindex="-1" role="dialog" aria-labelledby="instructionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="instructionModalLabel">📖 Инструкция по использованию системы отчета "Селектор"</h5>
            </div>
            <div class="modal-body instruction-body">
                <!-- Новый раздел в самом начале -->
                <div class="instruction-section">
                    <h2>🆕 Новое в системе</h2>
                    <ul class="instruction-list">
                        <li><strong>Добавлена проверка данных на соответствие ежедневному отчёту «Сводка»</strong></li>
                        <li>При редактировании и перед сохранением система автоматически сравнивает введённые значения с данными из ежедневного отчёта «Сводка».</li>
                        <li>При любых расхождениях появится предупреждение.</li>
                        <li>Это помогает избежать ошибок и обеспечивает точность отчётности.</li>
                    </ul>
                </div>
                <div class="instruction-section">
                    <h2>Важные общие правила</h2>
                    <ul class="instruction-list">
                        <li>Данные всегда относятся к <strong>вчерашнему дню</strong> или прошлым датам (нельзя выбирать текущий или будущий день).</li>
                        <li>Сохранение данных возможно только для <strong>редактируемых периодов</strong>. Если период пересекается с существующими данными или выходит за лимиты, сохранение заблокировано.</li>
                        <li>Максимальный период для просмотра и редактирования — <strong>14 дней</strong>.</li>
                        <li>Если в периоде есть <strong>"пустые" дни</strong> (без данных), система может предупредить, но сохранение возможно.</li>
                    </ul>
                </div>
                <div class="instruction-section">
                    <h2>1. Выбор периода дат</h2>
                    <p>Нажмите на поле с датами (по умолчанию — вчерашний день, формат <code>ДД.ММ.ГГГГ по ДД.ММ.ГГГГ</code>).</p>
                    <p>Откроется календарь, в котором:</p>
                    <ul class="instruction-list">
                        <li>Выберите <strong>начало и конец периода</strong> (кликните на даты)</li>
                        <li><strong>Ограничения:</strong>
                            <ul class="instruction-sublist">
                                <li>Нельзя выбрать сегодняшний день или будущее.</li>
                                <li>Максимум 14 дней (если больше — система сократит).</li>
                                <li>Существующие периоды в базе подчеркиваются цветными линиями (разные цвета для разных периодов).</li>
                                <li>Если выбранный интервал пересекается с существующими периодами, система автоматически расширит его до границ.</li>
                            </ul>
                        </li>
                        <li>Таблица обновится с данными за выбранный период.</li>
                        <li>Если период содержит несколько под-периодов (пересечения), редактирование невозможно.</li>
                    </ul>
                </div>
                <div class="instruction-section">
                    <h2>2. Просмотр и редактирование данных</h2>
                    <p><strong>Как редактировать:</strong></p>
                    <ul class="instruction-list">
                        <li>Для пунктов с <strong>двумя значениями</strong> (например, "123 / 456") вводите через слэш (<code>/</code>). Допустимы только цифры и один слэш. Пробелы вокруг слэша добавляются автоматически.</li>
                        <li>Для <strong>одиночных значений</strong> — только цифры.</li>
                        <li>Некоторые поля (расчетные, как 2.2, 9.3) — <strong>только для просмотра</strong> (серый фон, нельзя редактировать). Они рассчитываются автоматически на основе других данных.</li>
                        <li>Используйте клавиши: <strong>Enter / стрелки / Tab</strong> для навигации по ячейкам.</li>
                        <li>Редактирование заблокировано, если старый период, серые ячейки. В остальных случаях поля будут доступны для исправления.</li>
                        <li>При вводе данных система автоматически проверяет их на соответствие ожидаемым значениям из ежедневного отчёта «Сводка». При расхождениях может появиться предупреждение.</li>
                    </ul>
                </div>
                <div class="instruction-section">
                    <h2>3. Сохранение данных</h2>
                    <ul class="instruction-list">
                        <li>Кнопка <strong>СОХРАНИТЬ</strong> появляется только если период доступен для редактирования.</li>
                        <li><strong>Ограничения сохранения:</strong>
                            <ul class="instruction-sublist">
                                <li>Сохраняется сразу за весь период (до 14 дней максимум).</li>
                                <li>Если период пересекается с существующими данными — сохранение заблокировано (нужно выбрать другой интервал).</li>
                                <li>Нулевые значения ("0" или "0 / 0") не сохраняются, если не обязательно.</li>
                                <li>Перед сохранением проводится проверка на соответствие ожидаемым данным из отчёта «Сводка». При выявлении расхождений сохранение может быть заблокировано с соответствующим сообщением.</li>
                                <li>После сохранения таблица обновится, и период отметится в календаре.</li>
                            </ul>
                        </li>
                        <li>Если ошибка — проверьте период и данные.</li>
                    </ul>
                </div>
                <div class="instruction-section">
                    <h2>💡 Полезные советы</h2>
                    <ul class="instruction-list">
                        <li>Если данные не отображаются — проверьте <strong>гарнизона</strong> и <strong>период</strong>.</li>
                        <li>Период расширился автоматически — это нужно для отображения данных за выбранные периоды.</li>
                        <li>Обновляйте страницу <strong>(F5)</strong>, если таблица не обновилась.</li>
                        <li>При появлении предупреждений о расхождениях с отчётом «Сводка» проверьте исходные данные в сводке и убедитесь, что введённые значения обоснованы.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
    <script>
class ViewHistory {
    constructor() {
        this.storageKey = 'selector_view_history';
        this.maxHistorySize = 50;
    }
    savePosition(garnizon, subMode, startDate, endDate, openedSection = null) {
        const position = {garnizon: String(garnizon), subMode: String(subMode), startDate, endDate, openedSection, timestamp: new Date().getTime()};
        let history = this.getHistory();
        // Удаляем старое положение того же гарнизона/периода, чтобы не дублировать
        history = history.filter(p => !(p.garnizon === garnizon && p.startDate === startDate && p.endDate === endDate));
        history.unshift(position);
        history = history.slice(0, this.maxHistorySize);
        try { localStorage.setItem(this.storageKey, JSON.stringify(history)); } catch (e) {}
    }
    getLastPosition() {
        try {
            const history = localStorage.getItem(this.storageKey);
            if (history) return JSON.parse(history)[0] || null;
        } catch (e) {}
        return null;
    }
    getHistory() {
        try { const history = localStorage.getItem(this.storageKey); return history ? JSON.parse(history) : []; } catch (e) { return []; }
    }
    clear() {
        try { localStorage.removeItem(this.storageKey); } catch (e) {}
    }
}
const viewHistory = new ViewHistory();
        let selectedGarnizonIndex = "<?php echo htmlspecialchars($initialGarnizonIndex); ?>";
        let previousGarnizonIndex = "<?php echo htmlspecialchars($initialGarnizonIndex); ?>";
        let selectedSubMode = "<?php echo htmlspecialchars($initialSubMode); ?>";
        let previousSubMode = selectedSubMode;
        let accessLevel = "3";
        let userGarnizon = "";
        let isEditableGlobal = false;
        let allRows = [];
        let markedDatesCacheByGarnizon = {};
        let tableDataCache = {};
        let highlightedDatesCache = {};
        let isTableBuilt = false;
        const garnizonNames = {
            '88': 'МВД',
            '6': 'ТИРАСПОЛЬ',
            '5': 'БЕНДЕРЫ',
            '31': 'СЛОБОДЗЕЯ',
            '10': 'ГРИГОРИОПОЛЬ',
            '13': 'ДУБОССАРЫ',
            '29': 'РЫБНИЦА',
            '17': 'КАМЕНКА',
            '93': 'ОРОВД'
        };
        const allSubGarnizons = ['6', '5', '31', '10', '13', '29', '17', '93'];
function getNumericValue($input, firstOnly = false) {
    let val = $input.val().trim();
    if (!val) return 0;
   
    // Заменяем запятую на точку для корректного парсинга float
    val = val.replace(/,/g, '.');
   
    let parts = val.split(' / ').map(v => {
        const trimmed = v.trim();
        return trimmed ? parseFloat(trimmed) : 0;
    });
   
    if (firstOnly) {
        return parts[0];
    }
    return parts.reduce((a, b) => a + b, 0);
}
        function getRowByNaim(naim) {
            return $('#accordion-container tr[data-number_naim="' + naim + '"]');
        }
        const calculations = [
            { naim: '2.3.3', deps: [{naim: '2.3.1', firstOnly: true}, {naim: '2.3', firstOnly: true}], formula: (val21, val2First) => {
                if (val2First <= 0) return '';
                const perc = (val21 / val2First * 100).toFixed(2);
                return perc === '0.00' ? '' : perc + '%';
            }},
{
  naim: '2.7.2',
  deps: [
    { naim: '2.7.1', firstOnly: false }, // сумма колонок 2.7.1 (left + right)
    { naim: '2.7.1', firstOnly: true }, // только левая колонка 2.7.1
    { naim: '2.7', firstOnly: true } // только левая колонка 2.7 (base)
  ],
  formula: (valSum, valLeft, base) => {
    const right = valSum - valLeft; // чистое значение 2.7.1 справа
    if (right <= 0) return '';
   
    const increment = right - valLeft; // прирост = right - left
    if (increment <= 0) return '';
   
    const denom = increment + base; // right - left + base (левая 2.7)
    if (denom <= 0) return '';
   
    const perc = (right / denom * 100).toFixed(2);
    return perc === '0.00' ? '' : perc + '%';
  }
},
// Для 2.7.5
{
  naim: '2.7.5',
  deps: [
    { naim: '2.7.4', firstOnly: false }, // сумма колонок 2.7.4 (left + right)
    { naim: '2.7.4', firstOnly: true }, // только левая колонка 2.7.4
    { naim: '2.7.3', firstOnly: true } // только левая колонка 2.7.3 (base)
  ],
  formula: (sumCurrent, leftCurrent, base) => {
    const right = sumCurrent - leftCurrent; // чистое значение справа 2.7.4
    if (right <= 0) return '';
   
    const increment = right - leftCurrent; // прирост
    if (increment <= 0) return '';
   
    const denom = increment + base; // right - left + base
    if (denom <= 0) return '';
   
    const perc = (right / denom * 100).toFixed(2);
    return perc === '0.00' ? '' : perc + '%';
  }
},
// Для 2.7.8
{
  naim: '2.7.8',
  deps: [
    { naim: '2.7.7', firstOnly: false }, // сумма колонок 2.7.7 (left + right)
    { naim: '2.7.7', firstOnly: true }, // только левая колонка 2.7.7
    { naim: '2.7.6', firstOnly: true } // только левая колонка 2.7.6 (base)
  ],
  formula: (sumCurrent, leftCurrent, base) => {
    const right = sumCurrent - leftCurrent; // чистое значение справа 2.7.7
    if (right <= 0) return '';
   
    const increment = right - leftCurrent; // прирост
    if (increment <= 0) return '';
   
    const denom = increment + base; // right - left + base
    if (denom <= 0) return '';
   
    const perc = (right / denom * 100).toFixed(2);
    return perc === '0.00' ? '' : perc + '%';
  }
}
        ];
        function setCellValue($td, value) {
            const $input = $td.find('input');
            if ($input.length) {
                $input.val(value);
            } else {
                $td.text(value);
            }
        }
        function updateFormulas() {
            const isMVDTable = selectedGarnizonIndex === '88' && selectedSubMode === '0';
            const isMVDSum = selectedGarnizonIndex === '88' && selectedSubMode === '1';
            let columns = [];
            if (isMVDTable) {
                columns = [
                    {name: '6', selector: '.field-6-center'},
                    {name: '93', selector: '.field-6-orovd'},
                    {name: '6-total', selector: '.field-6-total'},
                    {name: '5', selector: '.field-5'},
                    {name: '31', selector: '.field-31'},
                    {name: '10', selector: '.field-10'},
                    {name: '13', selector: '.field-13'},
                    {name: '29', selector: '.field-29'},
                    {name: '17', selector: '.field-17'},
                    {name: '88', selector: '.field-88'}
                ];
            } else if (isMVDSum) {
                columns = [{name: '88', selector: '.field-center'}];
            } else if (selectedGarnizonIndex === '6') {
                columns = [
                    {name: '6', selector: '.field-center'},
                    {name: '93', selector: '.field-orovd'},
                    {name: '6-total', selector: '.field-tiraspol'}
                ];
            } else {
                columns = [{name: selectedGarnizonIndex, selector: '.field-center'}];
            }
            calculations.forEach(calc => {
                const targetRow = getRowByNaim(calc.naim);
                if (!targetRow.length) return;
                columns.forEach(col => {
const deps = calc.deps.map(dep => {
    const depRow = getRowByNaim(dep.naim);
    const $td = depRow.find(col.selector);
    const $input = $td.find('input');
   
    // Для пункта 16 и других используем parseFloat
    const val = $input.length
        ? getNumericValue($input, dep.firstOnly || false)
        : parseFloat($td.text().trim().replace(',', '.')) || 0;
    return val;
});
                    const result = calc.formula(...deps);
                    const $targetTd = targetRow.find(col.selector);
                    setCellValue($targetTd, result);
                });
            });
        }
        function setCalculatedReadonly() {
            const calculatedNaim = ['2.3.3', '2.7.2', '2.7.5', '2.7.8'];
            const isMVDTable = selectedGarnizonIndex === '88' && selectedSubMode === '0';
            const isMVDSum = selectedGarnizonIndex === '88' && selectedSubMode === '1';
            let columns = [];
            if (isMVDTable) {
                columns = [
                    '.field-6-center',
                    '.field-6-orovd',
                    '.field-6-total',
                    '.field-5',
                    '.field-31',
                    '.field-10',
                    '.field-13',
                    '.field-29',
                    '.field-17',
                    '.field-88'
                ];
            } else if (isMVDSum) {
                columns = ['.field-center'];
            } else if (selectedGarnizonIndex === '6') {
                columns = [
                    '.field-center',
                    '.field-orovd',
                    '.field-tiraspol'
                ];
            } else {
                columns = ['.field-center'];
            }
            calculatedNaim.forEach(naim => {
                const $tr = getRowByNaim(naim);
                if (!$tr.length) return;
                columns.forEach(sel => {
                    const $td = $tr.find(sel);
                    const $input = $td.find('input');
                    if ($input.length) {
                        $input.prop('readonly', true)
                            .prop('disabled', true)
                            .addClass('calculated-field')
                            .css({
                                'background-color': '#f8f9fa',
                                'cursor': 'not-allowed',
                                'color': '#666',
                                'font-weight': 'bold'
                            });
                    } else {
                        $td.css({
                            'background-color': '#f8f9fa',
                            'cursor': 'not-allowed',
                            'color': '#666',
                            'font-weight': 'bold'
                        });
                    }
                });
            });
        }
function updateTiraspolSum($tr) {
    const numParts = parseInt($tr.data('num_parts')) || 1;
    const $centerTd = $tr.find('.field-center');
    const $orovdTd = $tr.find('.field-orovd');
    const $tiraspolTd = $tr.find('.field-tiraspol');
   
    let centerValue = $centerTd.find('input').length ? $centerTd.find('input').val() : $centerTd.text();
    let orovdValue = $orovdTd.find('input').length ? $orovdTd.find('input').val() : $orovdTd.text();
   
    // ИСПРАВЛЕНИЕ: Парсим как вещественные числа
let centerParts = centerValue.split(' / ').map(v => {
    let val = parseFloat(v.trim().replace(',', '.')) || 0;
    return Math.round(val * 10000000) / 10000000; // 7 знаков
});
let orovdParts = orovdValue.split(' / ').map(v => {
    let val = parseFloat(v.trim().replace(',', '.')) || 0;
    return Math.round(val * 10000000) / 10000000; // 7 знаков
});
   
    while (centerParts.length < numParts) centerParts.push(0);
    while (orovdParts.length < numParts) orovdParts.push(0);
   
    const totalParts = centerParts.map((c, i) => {
        const sum = c + orovdParts[i];
        if (sum === 0) return '';
        // ИСПРАВЛЕНИЕ: Правильное форматирование для вещественных чисел
        if (Number.isInteger(sum)) return String(sum);
        return String(sum).match(/\d+\.?\d{1,4}/) ? String(sum) : String(sum.toFixed(4)).replace(/\.?0+$/, '');
    });
   
    const hasData = totalParts.some(v => v !== '');
    const tiraspolTotal = hasData ? totalParts.join(' / ') : '';
   
    setCellValue($tiraspolTd, tiraspolTotal);
}
function buildTableStructure(isTableMode = false) {
    $('#accordion-container').empty();
    const groupedBySection = {};
    const sectionTitles = {
        1: 'Регистрация преступлений и правонарушений',
        2: 'Расследовано и вынесено постановлений',
        3: 'Криминогенность',
        4: 'Общественная безопасность и правопорядок',
        5: 'Безопасность дорожного движения',
        6: 'Профилактика',
        7: 'Чрезвычайные ситуации',
        8: 'Миграционная политика',
        9: 'Кадровая политика',
        10: 'Прочие (Криминогенность)'
    };
    allRows.forEach(row => {
        let section = parseInt(row.section);
        if (isNaN(section) || section <= 0) {
            section = parseInt(String(row.number_naim).split('.')[0]);
        }
        if (section >= 1 && section <= 10) {
            if (!groupedBySection[section]) groupedBySection[section] = [];
            groupedBySection[section].push(row);
        }
    });
    const isViewOnly = selectedGarnizonIndex === '88';
    const exceptionNums = ['2.3', '2.7', '4.12'];
    for (let sec = 1; sec <= 10; sec++) {
        if (!groupedBySection[sec] || groupedBySection[sec].length === 0) continue;
        const $header = $(`<div class="section-header" data-section="${sec}"><span>${sectionTitles[sec]}</span> <span class="toggle-icon"></span></div>`);
        const $content = $(`<div class="section-content"></div>`);
        let tableClass = 'section-table';
        if (isTableMode) tableClass += ' mvd-table-mode';
        const $table = $(`<table class="${tableClass}"><thead></thead><tbody></tbody></table>`);
        let headerContent = '';
        if (isTableMode) {
            headerContent = `<tr><th rowspan="2">№</th><th rowspan="2">Результаты оперативно-служебной деятельности</th><th colspan="3">Тирасполь</th><th rowspan="2">Бендеры</th><th rowspan="2">Слободзея</th><th rowspan="2">Григориополь</th><th rowspan="2">Дубоссары</th><th rowspan="2">Рыбница</th><th rowspan="2">Каменка</th><th rowspan="2">по ПМР</th></tr><tr><th>Центр</th><th>ОРОВД</th><th>Сумма</th></tr>`;
        } else if (selectedGarnizonIndex === '6') {
            headerContent = `<tr><th style="width:5%">№</th><th style="width:45%">Результаты оперативно-служебной деятельности</th><th style="width:15%">Центр</th><th style="width:15%">ОРОВД</th><th style="width:15%">Сумма</th></tr>`;
        } else {
            headerContent = `<tr><th style="width:5%">№</th><th style="width:75%">Результаты оперативно-служебной деятельности</th><th style="width:20%">Центр</th></tr>`;
        }
        $table.find('thead').append(headerContent);
        const subGrouped = {};
        groupedBySection[sec].forEach(row => {
            const num = String(row.number_naim);
            if (!subGrouped[num]) subGrouped[num] = [];
            subGrouped[num].push(row);
        });
        Object.keys(subGrouped).sort((a, b) => a.localeCompare(b, undefined, {numeric: true})).forEach(num => {
            let group = subGrouped[num];
            group = group.sort((a, b) => a.id - b.id);
            if (exceptionNums.includes(num) && group.length === 2) {
                group = [group[1], group[0]];
            }
            let ids = group.map(r => r.id).join(',');
            let numParts = group.length;
            let naimenovParts = group.map(r => {
                let text = r.naimenov.trim();
                if (text.startsWith('-')) {
                    return `<span class="sub-point">${text}</span>`;
                }
                return text;
            });
            let naimenovs = naimenovParts.join(' / ');
            const checkingLeft = group[0]?.checking ?? '';
            const checkingRight = group.length > 1 ? (group[1]?.checking ?? '') : '';
            let rowContent = `<tr data-ids="${ids}"
                data-number_naim="${num}"
                data-num_parts="${numParts}"
                data-checking-left="${checkingLeft}"
                data-checking-right="${checkingRight}"
                style="height:60px">
                <td style="text-align:center">${num}</td>
                <td style="text-align:left; padding-left: 12px;"><div class="results-cell"><span class="naimenov"><a href="javascript:void(0);" class="chart-link" data-number-naim="${num}">${naimenovs}</a></span></div></td>`;
            if (isTableMode) {
                const fields = [{c:'field-6-center',g:'6'},{c:'field-6-orovd',g:'93'},{c:'field-6-total',g:'6-total'},{c:'field-5',g:'5'},{c:'field-31',g:'31'},{c:'field-10',g:'10'},{c:'field-13',g:'13'},{c:'field-29',g:'29'},{c:'field-17',g:'17'},{c:'field-88',g:'88'}];
                fields.forEach(f => {
                    const edit = f.g !== '6-total' && f.g !== '88';
                    rowContent += `<td class="${f.c}" style="text-align:center">${isViewOnly ? '' : `<input type="text" class="form-control${numParts > 1 ? ' combined' : ''}" data-garnizon="${f.g}" ${edit ? '' : 'readonly disabled'}>`}</td>`;
                });
            } else if (selectedGarnizonIndex === '6') {
                rowContent += `<td class="field-center" style="text-align:center">${isViewOnly ? '' : `<input type="text" class="form-control${numParts > 1 ? ' combined' : ''}" data-garnizon="6">`}</td>`;
                rowContent += `<td class="field-orovd" style="text-align:center">${isViewOnly ? '' : `<input type="text" class="form-control${numParts > 1 ? ' combined' : ''}" data-garnizon="93">`}</td>`;
                rowContent += `<td class="field-tiraspol" style="text-align:center"><input type="text" class="form-control disabled${numParts > 1 ? ' combined' : ''}" data-garnizon="6-total" disabled readonly></td>`;
            } else {
                rowContent += `<td class="field-center" style="text-align:center">${isViewOnly ? '' : `<input type="text" class="form-control${numParts > 1 ? ' combined' : ''}" data-garnizon="${selectedGarnizonIndex}">`}</td>`;
            }
            rowContent += `</tr>`;
            $table.find('tbody').append(rowContent);
        });
        $content.append($table);
        $('#accordion-container').append($header).append($content);
    }
    let width = '1000px';
    let containerMaxWidth = '1520px';
    if (isTableMode) {
        width = '1520px';
        containerMaxWidth = '1600px';
    } else if (selectedGarnizonIndex === '6' || selectedGarnizonIndex === '93') {
        width = '1300px';
    }
    $('#accordion-container').css('--accordion-width', width);
    $('.container').css('max-width', containerMaxWidth);
}

        function updateReadonlyBanner(isEditable, rules) {
            const $banner = $('#readonly-banner');
            if (isEditable) {
                $banner.removeClass('show').text('');
            } else {
                let msg = '';
                if (rules && rules.reason === 'mvd_sum')    msg = '⚠ Гарнизон МВД — суммарные данные, редактирование недоступно';
                else if (rules && rules.reason === 'over_14') msg = `⚠ Период ${rules.days} дн. > 14 дней — редактирование недоступно`;
                else                                          msg = '⚠ Режим просмотра — редактирование недоступно';
                $banner.addClass('show').text(msg);
            }
        }

        function applyGarnizonDropdownScope() {
            $('.dropdown-menu > li').show();
            $('.dropdown-item').show();
            $('.dropdown-submenu').show();
        }

        async function setIsEditableGlobal(startDate, endDate, hasOverlap = false) {
            // Используем CompassState.editRules для унифицированной логики
            const rules = CompassState.editRules('selector');
            let isEditable = rules.canEdit;
            isEditableGlobal = isEditable;
            updateReadonlyBanner(isEditable, rules);
            return isEditable;
        }
        async function checkAccess(action, userLevel, userGarnizon, targetGarnizon, date = null) {
            try {
                const payload = { action, userLevel, garrison: userGarnizon, targetGarrison: targetGarnizon };
                if (date) payload.start_date = date;
                const response = await fetch('../api/stub.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                if (!data.success) {
                    await logAction(`Ошибка проверки доступа для ${action}: ${data.error}`);
                }
                return data;
            } catch (error) {
                console.error(`Ошибка проверки доступа для ${action}:`, error);
                await logAction(`Ошибка проверки доступа для ${action}: ${error.message}`);
                return { success: false, access: false, error: error.message };
            }
        }
        async function markDatesInCalendar() {
            if (selectedGarnizonIndex === null) {
                await logAction('markDatesInCalendar: selectedGarnizonIndex is null');
                return;
            }
            try {
                const response = await fetch('../api/marked_dates.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ garnizon: selectedGarnizonIndex }),
                });
                const data = await response.json();
                if (data.success && Array.isArray(data.periods)) {
                    markedDatesCacheByGarnizon[selectedGarnizonIndex] = data.periods;
                    await logAction(`Загружены периоды для гарнизона ${selectedGarnizonIndex}: ${data.periods.length} периодов`);
                } else {
                    markedDatesCacheByGarnizon[selectedGarnizonIndex] = [];
                }
            } catch (error) {
                console.error(`Ошибка загрузки периодов для ${selectedGarnizonIndex}:`, error);
                markedDatesCacheByGarnizon[selectedGarnizonIndex] = [];
                await logAction(`Ошибка загрузки периодов: ${error.message}`);
            }
        }
        function applyColorsToCalendar(periods) {
            if (!$('.daterangepicker').is(':visible')) return;
            $('.drp-calendar tbody td').css('position', 'relative').find('.calendar-underline').remove();
            highlightedDatesCache[selectedGarnizonIndex] = [];
            $('.drp-calendar').each(function() {
                const $calendar = $(this);
                const monthYearText = $calendar.find('.month').text().trim();
                if (!monthYearText) return;
                $calendar.find('tbody td').each(function() {
                    if ($(this).hasClass('week') || !$(this).text().trim()) return;
                    const dateText = $(this).text().trim();
                    const formattedDate = convertToFullDate(dateText, monthYearText);
                    if (!formattedDate || !isValidDateInCurrentMonth(formattedDate, monthYearText)) return;
                    const currentDate = moment(formattedDate, "YYYY-MM-DD");
                    const yesterday = moment().subtract(1, 'day');
                    if (!$(this).hasClass('off') && currentDate.isValid()) {
                        const containingPeriod = periods.find(period => {
                            const periodStart = moment(period.start, "YYYY-MM-DD");
                            const periodEnd = moment(period.end, "YYYY-MM-DD");
                            return currentDate.isSameOrAfter(periodStart) && currentDate.isSameOrBefore(periodEnd);
                        });
                        if (containingPeriod) {
                            $(this).css({
                                'color': '#000',
                                'text-decoration': 'none',
                                'cursor': 'pointer',
                                'position': 'relative'
                            }).removeClass('disabled off unavailable').addClass('available').append(`
                                <span class="calendar-underline" style="
                                    position: absolute; bottom: 2px; left: 50%; width: 50%; height: 2px; z-index: 100;
                                    background-color: ${containingPeriod.color}; transform: translateX(-50%);
                                "></span>
                            `);
                            highlightedDatesCache[selectedGarnizonIndex].push(formattedDate);
                        } else if (currentDate.isSame(yesterday, 'day')) {
                            $(this).removeClass('disabled off unavailable').addClass('available').css({
                                'color': '#000',
                                'text-decoration': 'none',
                                'cursor': 'pointer'
                            });
                            highlightedDatesCache[selectedGarnizonIndex].push(formattedDate);
                        }
                    }
                });
            });
        }
        function isValidDateInCurrentMonth(date, monthYear) {
            try {
                const months = {
                    'Январь': '01', 'Февраль': '02', 'Март': '03', 'Апрель': '04', 'Май': '05', 'Июнь': '06',
                    'Июль': '07', 'Август': '08', 'Сентябрь': '09', 'Октябрь': '10', 'Ноябрь': '11', 'Декабрь': '12'
                };
                const monthMatch = Object.keys(months).find(month => monthYear.includes(month));
                const yearMatch = monthYear.match(/\d{4}/);
                if (!monthMatch || !yearMatch) return false;
                const dateObj = moment(date, "YYYY-MM-DD", true);
                return dateObj.isValid() && dateObj.format('MM') === months[monthMatch] && dateObj.format('YYYY') === yearMatch[0];
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
                return testDate.isValid() ? testDate.format('YYYY-MM-DD') : null;
            } catch (error) {
                return null;
            }
        }
        async function logAction(action) {
            try {
                const response = await fetch('../api/output_selector.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action })
                });
                const data = await response.json();
                if (!data.success) {
                    console.warn(`Логирование не удалось: ${data.error || 'Неизвестная ошибка'}`);
                }
                return data;
            } catch (error) {
                console.warn(`Ошибка логирования: ${error.message}`);
                return { success: false, error: error.message };
            }
        }
        async function fetchTableData(url, startDate, endDate, garnizon) {
            try {
                const response = await fetch(`${url}?t=${new Date().getTime()}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        start_date: startDate,
                        end_date: endDate,
                        garnizon: String(garnizon)
                    })
                });
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}, StatusText: ${response.statusText}`);
                }
                const data = await response.json();
                if (!data.success) {
                    console.warn(`fetchTableData failed for garnizon ${garnizon}: ${data.error || 'No error message'}`);
                    return { success: false, data: [], period_count: 0, has_empty_days: false, error: data.error || 'Unknown error' };
                }
                const result = {
                    success: data.success,
                    data: Array.isArray(data.data) ? data.data.map(item => ({
                        ...item,
                        number_naim: String(item.number_naim),
                        garnizon: String(item.garnizon),
                        kolichestvo: item.kolichestvo !== null ? String(item.kolichestvo) : '',
                        data_start: item.data_start,
                        data_end: item.data_end,
                        podrazdel: item.podrazdel || ''
                    })) : [],
                    period_count: data.period_count || 0,
                    has_empty_days: data.has_empty_days || false
                };
                tableDataCache[`${garnizon}_${startDate}_${endDate}`] = result;
                return result;
            } catch (error) {
                console.error(`fetchTableData error for garnizon ${garnizon}:`, error);
                await logAction(`Ошибка получения данных для гарнизона ${garnizon}: ${error.message}`);
                return { success: false, data: [], period_count: 0, has_empty_days: false, error: error.message };
            }
        }
async function fetchAllRows() {
    try {
        const response = await fetch('../api/get_svodki_list.php?format=flat&t=' + new Date().getTime(), {
            headers: { 'Content-Type': 'application/json' }
        });
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        const data = await response.json();
        if (!Array.isArray(data) || data.length === 0) {
            console.warn('fetchAllRows: empty or invalid response');
            return [];
        }
return data.map(item => ({
    id: parseInt(item.id),
    number_naim: String(item.number_naim),
    naimenov: item.naimenov ?? '',
    section: parseInt(item.section) || 0,
    checking: item.checking ?? ''
}));
    } catch (error) {
        console.error('fetchAllRows error:', error);
        await logAction(`Ошибка загрузки списка пунктов: ${error.message}`);
        return [];
    }
}
        function findContainingPeriods(selectedDate, endDate, periods) {
            return periods.filter(period => {
                const periodStart = moment(period.start, "YYYY-MM-DD");
                const periodEnd = moment(period.end, "YYYY-MM-DD");
                return (
                    (selectedDate.isSameOrAfter(periodStart) && endDate.isSameOrBefore(periodEnd)) ||
                    (selectedDate.isSameOrAfter(periodStart) && selectedDate.isSameOrBefore(periodEnd)) ||
                    (endDate.isSameOrAfter(periodStart) && endDate.isSameOrBefore(periodEnd)) ||
                    (periodStart.isSameOrAfter(selectedDate) && periodEnd.isSameOrBefore(endDate))
                );
            });
        }
        function expandToPeriodBoundaries(selectedDate, endDate, containingPeriods) {
            if (containingPeriods.length === 0) {
                return { start: selectedDate, end: endDate };
            }
            let newStart = selectedDate.clone();
            let newEnd = endDate.clone();
            containingPeriods.forEach(period => {
                const periodStart = moment(period.start, "YYYY-MM-DD");
                const periodEnd = moment(period.end, "YYYY-MM-DD");
                if (periodStart.isBefore(newStart)) newStart = periodStart;
                if (periodEnd.isAfter(newEnd)) newEnd = periodEnd;
            });
            return { start: newStart, end: newEnd };
        }
        function hasTwoRecords(number_naim) {
            const records = allRows.filter(row => String(row.number_naim) === String(number_naim));
            return records.length === 2;
        }
        function getCurrentChartUrl(num) {
            const picker = $('#date-range').data('daterangepicker');
            if (!picker) {
                const yesterday = moment().subtract(1, 'days').format('DD.MM.YYYY');
                return `chart_selector.php?garnizon=${selectedGarnizonIndex}&start=${yesterday}&end=${yesterday}&number_naim=${num}`;
            }
            const currentStart = picker.startDate.format('DD.MM.YYYY');
            const currentEnd = picker.endDate.format('DD.MM.YYYY');
            return `chart_selector.php?garnizon=${selectedGarnizonIndex}&start=${currentStart}&end=${currentEnd}&number_naim=${num}`;
        }
function normalizeDisplayValue(value, numParts) {
    value = (value || '').trim();
    if (!value) return '';
    if (numParts > 1) {
        let parts = value.split(/\/| /).map(p => p.trim()).filter(p => p !== '');
        if (parts.length === 0) return '';
        if (parts.length === 1) return parts[0];
        return parts.join(' / ');
    }
    return value;
}
        function isZeroValue(value, numParts) {
            if (!value || value.trim() === '') return true;
            if (numParts > 1) {
                const parts = value.split('/').map(part => part.trim());
                return parts.every(part => part === '' || part === '0');
            } else {
                return value === '' || value === '0';
            }
        }
        function toggleFields(garnizonIndex, show6and93Response) {
            const shouldShowOrovd = show6and93Response.success && show6and93Response.access &&
                                   (garnizonIndex === '6' || (garnizonIndex === '88' && selectedSubMode === '0'));
            if (shouldShowOrovd) {
                $('.field-orovd, .field-tiraspol').show().removeClass('hidden-column');
                if (garnizonIndex === '6') {
                    $('.field-orovd input').prop('disabled', !isEditableGlobal)
                                          .prop('readonly', !isEditableGlobal);
                } else if (garnizonIndex === '88' && selectedSubMode === '0') {
                    $('.field-orovd input').prop('disabled', false)
                                          .prop('readonly', false);
                }
            } else {
                $('.field-orovd, .field-tiraspol').hide().addClass('hidden-column');
            }
            $('th.field-orovd, th.field-tiraspol').toggle(shouldShowOrovd);
        }
async function aggregateData(data, startDate, endDate, isMVDSum = false, isMVDTable = false) {
    if (!Array.isArray(data)) {
        console.warn('aggregateData: data is not an array', data);
        return [];
    }
    const groupedByNum = {};
    const exceptionNums = ['2.3', '2.7', '4.12'];
 
    data.forEach(item => {
        if (!item || !item.number_naim || !item.garnizon) return;
        const num = String(item.number_naim);
        const gid = String(item.garnizon);
        const id = parseInt(item.id_svodki) || 0;
        const kol = String(item.kolichestvo || '');
        if (!groupedByNum[num]) groupedByNum[num] = {};
        if (!groupedByNum[num][gid]) groupedByNum[num][gid] = {};
        if (!groupedByNum[num][gid][id]) groupedByNum[num][gid][id] = [];
        groupedByNum[num][gid][id].push({ kol, data_start: item.data_start, data_end: item.data_end, podrazdel: item.podrazdel || '' });
    });
   
    const result = {};
    const precision = 7;
    const multiplier = Math.pow(10, precision);
   
    for (let num in groupedByNum) {
        const allIds = new Set();
        for (let gid in groupedByNum[num]) {
            for (let id in groupedByNum[num][gid]) {
                allIds.add(parseInt(id));
            }
        }
        let uniqueIds = Array.from(allIds).sort((a, b) => a - b);
        if (exceptionNums.includes(num) && uniqueIds.length === 2) {
            uniqueIds = [uniqueIds[1], uniqueIds[0]];
        }
        const numParts = uniqueIds.length || 1;
        if (isMVDSum) {
            const kols = uniqueIds.map(id => {
                let sumInt = 0;
                for (let gid in groupedByNum[num]) {
                    if (groupedByNum[num][gid][id]) {
                        groupedByNum[num][gid][id].forEach(record => {
                            let val = parseFloat((record.kol || '0').replace(',', '.')) || 0;
                            sumInt += Math.round(val * multiplier);
                        });
                    }
                }
                let sum = sumInt / multiplier;
                if (sum === 0) return '';
                let fixed = Number(sum.toFixed(precision));
                return Number.isInteger(fixed) ? String(fixed) : fixed.toFixed(precision).replace(/\.?0+$/, '');
            });
            const joined = kols.join(' / ');
            result[`${num}`] = { number_naim: num, garnizon: '88', kolichestvo: joined, data_start: startDate.format('DD.MM.YYYY'), data_end: endDate.format('DD.MM.YYYY'), naimenov: '', id_svodki: uniqueIds[0] || 0 };
        } else if (isMVDTable) {
            const gids = ['6', '93', '5', '31', '10', '13', '29', '17'];
            const garnizonTotals = {};
            for (let gid of gids) {
                const subData = groupedByNum[num][gid] || {};
                const kols = uniqueIds.map(id => {
                    let sumInt = 0;
                    if (subData[id]) {
                        subData[id].forEach(record => {
                            let val = parseFloat((record.kol || '0').replace(',', '.')) || 0;
                            sumInt += Math.round(val * multiplier);
                        });
                    }
                    let sum = sumInt / multiplier;
                    if (sum === 0) return '';
                    let fixed = Number(sum.toFixed(precision));
                    return Number.isInteger(fixed) ? String(fixed) : fixed.toFixed(precision).replace(/\.?0+$/, '');
                });
                garnizonTotals[gid] = kols.map(k => k === '' ? 0 : parseFloat(k)); // сохраняем числом для дальнейших сумм
                const joined = kols.join(' / ');
                result[`${num}-${gid}`] = { number_naim: num, garnizon: gid, kolichestvo: joined, data_start: startDate.format('DD.MM.YYYY'), data_end: endDate.format('DD.MM.YYYY'), naimenov: '', id_svodki: uniqueIds[0] || 0, podrazdel: Object.values(groupedByNum[num][gid] || {})[0]?.[0]?.podrazdel || '' };
            }
           
            // МВД-сумма (88)
            const mvdKols = uniqueIds.map((id, idx) => {
                let sumInt = 0;
                for (let gid of gids) {
                    sumInt += Math.round(garnizonTotals[gid][idx] * multiplier);
                }
                let sum = sumInt / multiplier;
                if (sum === 0) return '';
                let fixed = Number(sum.toFixed(precision));
                return Number.isInteger(fixed) ? String(fixed) : fixed.toFixed(precision).replace(/\.?0+$/, '');
            });
            result[`${num}-88`] = { number_naim: num, garnizon: '88', kolichestvo: mvdKols.join(' / '), data_start: startDate.format('DD.MM.YYYY'), data_end: endDate.format('DD.MM.YYYY'), naimenov: '', id_svodki: uniqueIds[0] || 0 };
           
            // Тирасполь-сумма (6-total)
            if (garnizonTotals['6'] || garnizonTotals['93']) {
                const totalKols = uniqueIds.map((id, idx) => {
                    let sumInt = Math.round(garnizonTotals['6'][idx] * multiplier) + Math.round(garnizonTotals['93'][idx] * multiplier);
                    let sum = sumInt / multiplier;
                    if (sum === 0) return '';
                    let fixed = Number(sum.toFixed(precision));
                    return Number.isInteger(fixed) ? String(fixed) : fixed.toFixed(precision).replace(/\.?0+$/, '');
                });
                result[`${num}-6-total`] = { number_naim: num, garnizon: '6-total', kolichestvo: totalKols.join(' / '), data_start: startDate.format('DD.MM.YYYY'), data_end: endDate.format('DD.MM.YYYY'), naimenov: '', id_svodki: uniqueIds[0] || 0 };
            }
        } else {
            // Обычные гарнизоны
            const gids = Object.keys(groupedByNum[num]);
            for (let gid of gids) {
                const subData = groupedByNum[num][gid] || {};
                const kols = uniqueIds.map(id => {
                    let sumInt = 0;
                    if (subData[id]) {
                        subData[id].forEach(record => {
                            let val = parseFloat((record.kol || '0').replace(',', '.')) || 0;
                            sumInt += Math.round(val * multiplier);
                        });
                    }
                    let sum = sumInt / multiplier;
                    if (sum === 0) return '';
                    let fixed = Number(sum.toFixed(precision));
                    return Number.isInteger(fixed) ? String(fixed) : fixed.toFixed(precision).replace(/\.?0+$/, '');
                });
                const joined = kols.join(' / ');
                result[`${num}-${gid}`] = { number_naim: num, garnizon: gid, kolichestvo: joined, data_start: startDate.format('DD.MM.YYYY'), data_end: endDate.format('DD.MM.YYYY'), naimenov: '', id_svodki: uniqueIds[0] || 0, podrazdel: Object.values(subData)[0]?.[0]?.podrazdel || '' };
            }
           
            // Тирасполь-сумма внутри обычного режима
            if (selectedGarnizonIndex === '6' && (groupedByNum[num]['6'] || groupedByNum[num]['93'])) {
                const centerKols = uniqueIds.map(id => {
                    let sumInt = 0;
                    if (groupedByNum[num]['6'] && groupedByNum[num]['6'][id]) {
                        groupedByNum[num]['6'][id].forEach(record => {
                            let val = parseFloat((record.kol || '0').replace(',', '.')) || 0;
                            sumInt += Math.round(val * multiplier);
                        });
                    }
                    return sumInt / multiplier;
                });
                const orovdKols = uniqueIds.map(id => {
                    let sumInt = 0;
                    if (groupedByNum[num]['93'] && groupedByNum[num]['93'][id]) {
                        groupedByNum[num]['93'][id].forEach(record => {
                            let val = parseFloat((record.kol || '0').replace(',', '.')) || 0;
                            sumInt += Math.round(val * multiplier);
                        });
                    }
                    return sumInt / multiplier;
                });
                const totalKols = centerKols.map((c, i) => {
                    let sumInt = Math.round(c * multiplier) + Math.round(orovdKols[i] * multiplier);
                    let sum = sumInt / multiplier;
                    if (sum === 0) return '';
                    let fixed = Number(sum.toFixed(precision));
                    return Number.isInteger(fixed) ? String(fixed) : fixed.toFixed(precision).replace(/\.?0+$/, '');
                });
                result[`${num}-6-total`] = { number_naim: num, garnizon: '6-total', kolichestvo: totalKols.join(' / '), data_start: startDate.format('DD.MM.YYYY'), data_end: endDate.format('DD.MM.YYYY'), naimenov: '', id_svodki: uniqueIds[0] || 0 };
            }
        }
    }
    return Object.values(result);
}
        async function updateTableData(aggregatedData, startDate, endDate, show6and93Response) {
            const isMVDTable = selectedGarnizonIndex === '88' && selectedSubMode === '0';
            const isMVDSum = selectedGarnizonIndex === '88' && selectedSubMode === '1';
            const calculatedNaim = ['2.3.3', '2.7.2', '2.7.5', '2.7.8'];
            $('#accordion-container tr').each(function() {
                const $tr = $(this);
                const number_naim = String($tr.data('number_naim'));
                const numParts = parseInt($tr.data('num_parts')) || 1;
                const isCalculated = calculatedNaim.includes(number_naim);
                if (isMVDTable) {
                    const garnizonFields = [
                        {class: 'field-6-center', garnizon: '6'},
                        {class: 'field-6-orovd', garnizon: '93'},
                        {class: 'field-6-total', garnizon: '6-total'},
                        {class: 'field-5', garnizon: '5'},
                        {class: 'field-31', garnizon: '31'},
                        {class: 'field-10', garnizon: '10'},
                        {class: 'field-13', garnizon: '13'},
                        {class: 'field-29', garnizon: '29'},
                        {class: 'field-17', garnizon: '17'},
                        {class: 'field-88', garnizon: '88'}
                    ];
                    garnizonFields.forEach(field => {
                        const data = aggregatedData.find(d =>
                            String(d.number_naim) === number_naim && d.garnizon === field.garnizon
                        );
                        const $td = $tr.find(`.${field.class}`);
                        let value = data ? data.kolichestvo : '';
                        value = normalizeDisplayValue(value, numParts);
                        setCellValue($td, value);
                        if (isCalculated) {
                            const $input = $td.find('input');
                            if ($input.length) {
                                $input.prop('readonly', true)
                                      .prop('disabled', true)
                                      .css('background-color', '#f8f9fa');
                            } else {
                                $td.css('background-color', '#f8f9fa');
                            }
                        }
                    });
                } else if (isMVDSum) {
                    const data = aggregatedData.find(d => String(d.number_naim) === number_naim && d.garnizon === '88');
                    const $td = $tr.find('.field-center');
                    let value = data ? data.kolichestvo : '';
                    value = normalizeDisplayValue(value, numParts);
                    setCellValue($td, value);
                    if (isCalculated) {
                        const $input = $td.find('input');
                        if ($input.length) {
                            $input.prop('readonly', true)
                                  .prop('disabled', true)
                                  .css('background-color', '#f8f9fa');
                        } else {
                            $td.css('background-color', '#f8f9fa');
                        }
                    }
                } else {
                    const dataCenter = aggregatedData.find(d => String(d.number_naim) === number_naim && d.garnizon === selectedGarnizonIndex);
                    const dataOrovd = aggregatedData.find(d => String(d.number_naim) === number_naim && d.garnizon === '93');
                    const dataTotal = aggregatedData.find(d => String(d.number_naim) === number_naim && d.garnizon === '6-total');
                    const $centerTd = $tr.find('.field-center');
                    const $orovdTd = $tr.find('.field-orovd');
                    const $tiraspolTd = $tr.find('.field-tiraspol');
                    let centerValue = dataCenter ? dataCenter.kolichestvo : '';
                    let orovdValue = dataOrovd ? dataOrovd.kolichestvo : '';
                    let totalValue = dataTotal ? dataTotal.kolichestvo : '';
                    centerValue = normalizeDisplayValue(centerValue, numParts);
                    orovdValue = normalizeDisplayValue(orovdValue, numParts);
                    totalValue = normalizeDisplayValue(totalValue, numParts);
                    setCellValue($centerTd, centerValue);
                    setCellValue($orovdTd, orovdValue);
                    setCellValue($tiraspolTd, totalValue);
                    if (isCalculated) {
                        const $centerInput = $centerTd.find('input');
                        if ($centerInput.length) {
                            $centerInput.val('').prop('readonly', true).prop('disabled', true).css('background-color', '#f8f9fa');
                        } else {
                            $centerTd.text('').css('background-color', '#f8f9fa');
                        }
                        const $orovdInput = $orovdTd.find('input');
                        if ($orovdInput.length) {
                            $orovdInput.val('').prop('readonly', true).prop('disabled', true).css('background-color', '#f8f9fa');
                        } else {
                            $orovdTd.text('').css('background-color', '#f8f9fa');
                        }
                        const $tiraspolInput = $tiraspolTd.find('input');
                        if ($tiraspolInput.length) {
                            $tiraspolInput.val('').prop('readonly', true).prop('disabled', true).css('background-color', '#f8f9fa');
                        } else {
                            $tiraspolTd.text('').css('background-color', '#f8f9fa');
                        }
                    } else if (selectedGarnizonIndex === '6' && show6and93Response.success && show6and93Response.access) {
                        updateTiraspolSum($tr);
                    }
                }
                if (!isCalculated) {
                    $tr.find('input:not(.disabled):not([readonly])').prop('disabled', !isEditableGlobal);
                }
            });
            toggleFields(selectedGarnizonIndex, show6and93Response);
            if ($('#accordion-container tr').length === 0) {
                $('#accordion-container').html('<tr><td colspan="5">Нет данных для отображения</td></tr>');
            }
        }
// ===== ИСПРАВЛЕННАЯ ФУНКЦИЯ СРАВНЕНИЯ С ПОДРОБНЫМ ЛОГИРОВАНИЕМ =====
// НОВАЯ ФУНКЦИЯ: агрегация expected-данных с сохранением деталей по дням
function aggregateExpectedDataDetailed(dailyRaw) {
    console.log('Агрегация expected-данных (itog_sel_deg) с детализацией по дням. Количество сырых записей:', dailyRaw.length);
    const grouped = {};
    dailyRaw.forEach(item => {
        const check = parseInt(item.checking, 10) || 0;
        const gid = String(item.garnizon || '').trim();
        if (check <= 0 || !gid) return;
        const kol = parseInt(item.kolichestvo, 10) || 0;
        const date = item.data_start; // для daily-записей data_start = data_end
        const key = `${check}_${gid}`;
        if (!grouped[key]) {
            grouped[key] = { total: 0, details: [] };
        }
        grouped[key].total += kol;
        grouped[key].details.push({ date, kol });
    });
    const result = [];
    for (let key in grouped) {
        const [check, gid] = key.split('_');
        // Сортируем дни по дате
        grouped[key].details.sort((a, b) => a.date.localeCompare(b.date));
        result.push({
            checking: parseInt(check),
            garnizon: gid,
            total: grouped[key].total,
            details: grouped[key].details
        });
    }
    console.log('Готово. Агрегированные expected-данные с детализацией:', result);
    return result;
}
function aggregateExpectedDataDetailed(dailyRaw) {
    console.log('🔄 Агрегация EXPECTED-данных. Сырых записей:', dailyRaw.length);
    const grouped = {};
    dailyRaw.forEach(item => {
        const check = parseInt(item.checking, 10) || 0;
        const gid = String(item.garnizon || '').trim();
        const pole = parseInt(item.pole, 10) || 2;
       
        if (check <= 0 || !gid) return;
        // ИСПРАВКА: kolichestvo уже содержит правильное значение
        const kol = parseInt(item.kolichestvo, 10) || 0;
        const key = `${check}_${gid}`;
        if (!grouped[key]) {
            grouped[key] = {
                checking: check,
                garnizon: gid,
                pole: pole,
                total: 0
            };
        }
       
        // ИСПРАВКА: просто суммируем kolichestvo (уже правильное!)
        grouped[key].total += kol;
       
        console.log(` → Обработка: checking=${check}, garnizon=${gid}, kol=${kol}, pole=${pole}`);
    });
    const result = Object.values(grouped);
    console.log('✅ Агрегированные EXPECTED:', result);
    return result;
}
function buildExpectedMap(detailedExpected) {
    const map = {};
    detailedExpected.forEach(item => {
        const key = `${item.checking}_${item.garnizon}`;
        map[key] = {
            total: item.total,
            pole: item.pole,
            checking: item.checking
        };
    });
    console.log('📊 Карта ожидаемых значений:', map);
    return map;
}
function applyMismatchHighlights(detailedExpected, isMVDTable, isMVDSum, selectedGarnizonIndex) {
    $('.expected-text').remove();
    $('#accordion-container td').removeClass('mismatch');
    const expectedMap = buildExpectedMap(detailedExpected);
   
    const mainGarnizons = ['6', '5', '31', '10', '13', '29', '17'];
    const garnizonFullNames = {
        '6': 'Тирасполь', '5': 'Бендеры', '31': 'Слободзея', '10': 'Григориополь',
        '13': 'Дубоссары', '29': 'Рыбница', '17': 'Каменка'
    };
    let mismatchCount = 0;
    $('#accordion-container tr').each(function() {
        const $tr = $(this);
        const naim = $tr.data('number_naim');
        const checkingLeft = parseInt($tr.data('checking-left') || 0);
        const checkingRight = parseInt($tr.data('checking-right') || 0);
       
        if (checkingLeft <= 0 && checkingRight <= 0) {
            return;
        }
        let poleValue = 2;
        for (let g of mainGarnizons) {
            const key = `${checkingLeft}_${g}`;
            if (expectedMap[key]) {
                poleValue = expectedMap[key].pole;
                break;
            }
        }
        console.group(`📋 Пункт ${naim} (checking: ${checkingLeft}${checkingRight > 0 ? ` / ${checkingRight}` : ''}, pole=${poleValue})`);
        const numParts = parseInt($tr.data('num_parts')) || 1;
        const effectiveNumParts = (poleValue === 1) ? 1 : numParts;
        console.log(` numParts=${numParts}, effectiveNumParts=${effectiveNumParts}`);
        let fields = [];
        if (isMVDTable) {
            fields = [
                {cls: 'field-6-center', g: '6', type: 'district'},
                {cls: 'field-6-orovd', g: '93', type: 'district'},
                {cls: 'field-6-total', g: '6', type: 'sum'},
                {cls: 'field-5', g: '5', type: 'district'},
                {cls: 'field-31', g: '31', type: 'district'},
                {cls: 'field-10', g: '10', type: 'district'},
                {cls: 'field-13', g: '13', type: 'district'},
                {cls: 'field-29', g: '29', type: 'district'},
                {cls: 'field-17', g: '17', type: 'district'},
                {cls: 'field-88', g: '88', type: 'mvd-sum'}
            ];
        } else if (isMVDSum) {
            fields = [{cls: 'field-center', g: '88', type: 'mvd-sum'}];
        } else if (selectedGarnizonIndex === '6') {
            // ИСПРАВЛЕНО: для гарнизона 6 только одно поле (сумма 6+93)
            fields = [
                {cls: 'field-tiraspol', g: '6-and-93', type: 'single'}
            ];
        } else {
            fields = [{cls: 'field-center', g: selectedGarnizonIndex, type: 'single'}];
        }
        fields.forEach(f => {
            const $td = $tr.find('.' + f.cls);
            if (!$td.length) return;
            if (f.type === 'sum') {
                console.log(` └─ ${garnizonFullNames[f.g] || f.g} (сумма) - подсчёт автоматический`);
                return;
            }
            const $input = $td.find('input');
            let currentValue = $input.length ? $input.val().trim() : $td.text().trim();
            if (!currentValue) currentValue = effectiveNumParts > 1 ? '0 / 0' : '0';
            let currentLeft = 0, currentRight = 0;
            if (effectiveNumParts > 1) {
                const parts = currentValue.split(' / ').map(p => parseInt(p.trim(), 10) || 0);
                currentLeft = parts[0] || 0;
                currentRight = parts[1] || 0;
            } else {
                currentLeft = parseInt(currentValue, 10) || 0;
            }
            console.log(` ${garnizonFullNames[f.g] || f.g}: текущая=${currentValue}`);
            let expectedLeft = 0, expectedRight = 0;
            if (f.type === 'mvd-sum') {
                /**
                 * МВД (88) - ВЕРСИЯ 4
                 * Суммируем по ВСЕМ районам (включая 6 И 93!)
                 */
                let totalExpected = 0;
                mainGarnizons.forEach(g => {
                    const valLeft = (expectedMap[`${checkingLeft}_${g}`]?.total) || 0;
                    const valRight = (effectiveNumParts > 1 && checkingRight > 0)
                        ? (expectedMap[`${checkingRight}_${g}`]?.total || 0)
                        : 0;
                    const districtTotal = valLeft + valRight;
                    totalExpected += districtTotal;
                });
                const displayText = String(totalExpected);
                let currentSum = currentLeft + currentRight;
                const isMismatch = (currentSum !== totalExpected);
                if (isMismatch) {
                    $td.addClass('mismatch');
                    $td.append(`<span class="expected-text">Ожидается: ${displayText}</span>`);
                    mismatchCount++;
                    console.log(` ❌ МВД: текущая сумма=${currentSum}, ожидается=${displayText}`);
                } else {
                    console.log(` ✅ МВД: ${currentSum}`);
                }
            } else if (f.g === '6-and-93') {
                /**
                 * ИСПРАВКА ВЕРСИЯ 4: Гарнизон 6
                 *
                 * Сравниваем с СУММОЙ (6 + 93)
                 * Потому что в сводке 6 и 93 - это разбитый один гарнизон
                 *
                 * Ожидаемое = expected[checking_6] + expected[checking_93]
                 */
               
                // Суммируем 6 и 93
                let totalExpected6 = (expectedMap[`${checkingLeft}_6`]?.total) || 0;
                let totalExpected93 = (expectedMap[`${checkingLeft}_93`]?.total) || 0;
                let totalExpected = totalExpected6 + totalExpected93;
                // Если есть вторая часть (checking 11)
                if (effectiveNumParts > 1 && checkingRight > 0) {
                    const right6 = (expectedMap[`${checkingRight}_6`]?.total) || 0;
                    const right93 = (expectedMap[`${checkingRight}_93`]?.total) || 0;
                    expectedRight = right6 + right93;
                    totalExpected += expectedRight;
                }
                expectedLeft = totalExpected6 + totalExpected93;
                let displayExpected = '';
                if (effectiveNumParts > 1) {
                    displayExpected = `${totalExpected6 + totalExpected93} / ${expectedRight}`;
                } else {
                    displayExpected = String(totalExpected);
                }
                const isMismatch = (effectiveNumParts > 1)
                    ? (currentLeft !== totalExpected6 + totalExpected93 || currentRight !== expectedRight)
                    : (currentLeft !== totalExpected);
                if (isMismatch) {
                    $td.addClass('mismatch');
                    $td.append(`<span class="expected-text">Ожидается (6+93): ${displayExpected}</span>`);
                    mismatchCount++;
                    console.log(` ❌ Гарнизон 6 (с 93): текущая=${currentValue}, ожидается=${displayExpected}`);
                } else {
                    console.log(` ✅ Гарнизон 6 (с 93): ${currentValue}`);
                }
            } else {
                /**
                 * Обычный район (не МВД, не гарнизон 6)
                 */
                expectedLeft = (expectedMap[`${checkingLeft}_${f.g}`]?.total) || 0;
                expectedRight = (effectiveNumParts > 1 && checkingRight > 0)
                    ? (expectedMap[`${checkingRight}_${f.g}`]?.total || 0)
                    : 0;
                let displayExpected = '';
                if (effectiveNumParts > 1) {
                    displayExpected = `${expectedLeft} / ${expectedRight}`;
                    if (expectedRight === 0 && expectedLeft !== 0) {
                        displayExpected = `${expectedLeft}`;
                    } else if (expectedLeft === 0 && expectedRight !== 0) {
                        displayExpected = ` / ${expectedRight}`;
                    } else if (expectedLeft === 0 && expectedRight === 0) {
                        displayExpected = '';
                    }
                } else {
                    displayExpected = String(expectedLeft);
                }
                const isMismatch = (effectiveNumParts > 1)
                    ? (currentLeft !== expectedLeft || currentRight !== expectedRight)
                    : (currentLeft !== expectedLeft);
                if (isMismatch) {
                    $td.addClass('mismatch');
                    $td.append(`<span class="expected-text">Ожидается: ${displayExpected}</span>`);
                    mismatchCount++;
                    console.log(` ❌ ${garnizonFullNames[f.g] || f.g}: текущая=${currentValue}, ожидается=${displayExpected}`);
                } else {
                    console.log(` ✅ ${garnizonFullNames[f.g] || f.g}: ${currentValue}`);
                }
            }
        });
        console.groupEnd();
    });
    console.log(`\n📊 Всего несоответствий выявлено: ${mismatchCount}`);
}
function aggregateActualDataSimple(dailyRaw) {
    // Group ONLY by (checking, garnizon)
    const grouped = {};
    console.log('Aggregating actual data, raw items:', dailyRaw.length);
    dailyRaw.forEach(item => {
        const check = parseInt(item.checking, 10) || 0;
        const gid = String(item.garnizon || '').trim();
       
        if (check <= 0 || !gid) {
            return;
        }
        const kol = parseInt(item.kolichestvo, 10) || 0;
        const key = `${check}_${gid}`;
        if (!grouped[key]) {
            grouped[key] = {
                checking: check,
                garnizon: gid,
                kolichestvo: 0
            };
        }
        grouped[key].kolichestvo += kol;
    });
    const result = Object.values(grouped);
    console.log('✅ Aggregated actual data:', result);
    return result;
}
async function getActualDataFromItogSelector(startDate, endDate, garnizonIds) {
    try {
        const promises = garnizonIds.map(gid =>
            fetch('../api/output_selector.php?t=' + new Date().getTime(), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    start_date: startDate.format('DD.MM.YYYY'),
                    end_date: endDate.format('DD.MM.YYYY'),
                    garnizon: String(gid)
                })
            }).then(res => res.ok ? res.json() : {success: false, data: []})
              .catch(() => ({success: false, data: []}))
        );
        const responses = await Promise.all(promises);
        const actualData = responses
            .filter(r => r.success)
            .flatMap(r => r.data.map(item => ({
                checking: String(item.checking || ''),
                kolichestvo: String(item.kolichestvo || '0'),
                garnizon: String(item.garnizon || '')
            })));
        console.log('Actual aggregated data:', actualData);
        return actualData;
    } catch (error) {
        console.error('Error fetching actual:', error);
        return [];
    }
}
// ===== ИСПРАВЛЕННАЯ ФУНКЦИЯ showSelectedDates =====
async function showSelectedDates(startDate, endDate) {
    $('#save-button').hide();
    if (!startDate.isValid() || !endDate.isValid()) {
        console.warn('showSelectedDates: invalid dates', startDate, endDate);
        await logAction('showSelectedDates: invalid dates');
        return;
    }
    const accessResponse = await checkAccess('view_svodki', accessLevel, userGarnizon, selectedGarnizonIndex);
    if (!accessResponse.success || !accessResponse.access) {
        alert('Нет доступа к данным выбранного гарнизона.');
        await logAction(`Нет доступа к данным гарнизона ${selectedGarnizonIndex}`);
        return;
    }
    const show6and93Response = await checkAccess('show_6_and_93', accessLevel, userGarnizon, selectedGarnizonIndex);
    const needsTableRebuild = (previousGarnizonIndex !== selectedGarnizonIndex) ||
                             (previousSubMode !== selectedSubMode);
    if (needsTableRebuild || !isTableBuilt) {
        if (!isTableBuilt) {
            allRows = await fetchAllRows();
            if (!allRows.length) {
                $('#accordion-container').html('<tr><td colspan="5">Нет данных для отображения</td></tr>');
                return;
            }
        }
        const isTableMode = selectedGarnizonIndex === '88' && selectedSubMode === '0';
        buildTableStructure(isTableMode);
        isTableBuilt = true;
        previousGarnizonIndex = selectedGarnizonIndex;
            CompassState.set({ garnizon: selectedGarnizonIndex, subMode: selectedSubMode });
        previousSubMode = selectedSubMode;
    }
    let aggregatedData = [];
    const isMVDTable = selectedGarnizonIndex === '88' && selectedSubMode === '0';
    const isMVDSum = selectedGarnizonIndex === '88' && selectedSubMode === '1';
    const cacheKey = `${selectedGarnizonIndex}_${startDate.format('DD.MM.YYYY')}_${endDate.format('DD.MM.YYYY')}`;
    delete tableDataCache[cacheKey];
    let garnizonIds = isMVDTable || isMVDSum ? allSubGarnizons :
                      (show6and93Response.success && show6and93Response.access && selectedGarnizonIndex === '6' ? ['6', '93'] : [selectedGarnizonIndex]);
    const fetchPromises = garnizonIds.map(gid =>
        fetchTableData('../api/output_selector.php', startDate.format('DD.MM.YYYY'), endDate.format('DD.MM.YYYY'), gid)
    );
    const responses = await Promise.all(fetchPromises);
    aggregatedData = responses.filter(r => r.success).flatMap(r => r.data);
    aggregatedData = await aggregateData(aggregatedData, startDate, endDate, isMVDSum, isMVDTable);
    tableDataCache[cacheKey] = aggregatedData;
    await updateTableData(aggregatedData, startDate.format('DD.MM.YYYY'), endDate.format('DD.MM.YYYY'), show6and93Response);
    // ===== FETCH DAILY EXPECTED DATA (ИСПРАВЛЕННАЯ ЛОГИКА) =====
    console.log('📥 Fetching EXPECTED data from get_daily_expected.php');
   
    const dailyPromises = garnizonIds.map(gid => {
        const startDateStr = startDate.format('DD.MM.YYYY');
        const endDateStr = endDate.format('DD.MM.YYYY');
        const garnizonStr = String(gid);
       
        const payload = {
            start_date: startDateStr,
            end_date: endDateStr,
            garnizon: garnizonStr
        };
       
        return fetch('../api/output_sel_deg.php?t=' + new Date().getTime(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        }).then(res => {
            if (!res.ok) {
                console.error(`❌ HTTP error for garrison ${gid}: ${res.status}`);
                return {success: false, data: []};
            }
            return res.json();
        })
          .catch(err => {
              console.error(`❌ Fetch error for garrison ${gid}:`, err);
              return {success: false, data: []};
          })
    });
    const dailyResponses = await Promise.all(dailyPromises);
   
    const dailyRaw = dailyResponses
        .filter(r => r.success && Array.isArray(r.data))
        .flatMap(r => r.data);
    console.log('📊 Daily raw data (all daily records):', dailyRaw);
    // ===== ИСПРАВЛЕННАЯ ЛОГИКА АГРЕГАЦИИ И ПОДСВЕЧИВАНИЯ =====
    console.log(`\n=== ПРОВЕРКА НЕСОСТЫКОВОК ДЛЯ ${startDate.format('DD.MM.YYYY')} — ${endDate.format('DD.MM.YYYY')} ===`);
   
    // Агрегируем ожидаемые данные
    const detailedExpected = aggregateExpectedDataDetailed(dailyRaw);
   
    // Применяем подсвечивание (исправленная функция)
    applyMismatchHighlights(detailedExpected, isMVDTable, isMVDSum, selectedGarnizonIndex);
    setCalculatedReadonly();
    updateFormulas();
    if (isEditableGlobal && !isMVDTable && selectedGarnizonIndex !== '88') {
        $('#save-button').show();
    }
    const lastPosition = viewHistory.getLastPosition();
    if (lastPosition && lastPosition.openedSection) {
        const $targetHeader = $(`.section-header[data-section="${lastPosition.openedSection}"]`);
        if ($targetHeader.length && !$targetHeader.hasClass('open')) {
            $targetHeader.trigger('click');
        }
    }
    if ($("#accordion-container").css('display') === 'none') {
        $("#accordion-container").css({ display: 'table', opacity: 0 }).addClass('fade-in').animate({ opacity: 1 }, 300);
    }
}
        async function saveData() {
            if (selectedGarnizonIndex === null) {
                await logAction('Ошибка сохранения: гарнизон не выбран');
                alert('Гарнизон не выбран.');
                return;
            }
            const startDateObj = $('#date-range').data('daterangepicker').startDate;
            const endDateObj = $('#date-range').data('daterangepicker').endDate;
            const data_start = startDateObj.format('DD.MM.YYYY');
            const data_end = endDateObj.format('DD.MM.YYYY');
            let formData = [];
            const isTableMode = selectedGarnizonIndex === '88' && selectedSubMode === '0';
            const isMVDSum = selectedGarnizonIndex === '88' && selectedSubMode === '1';
            if (isTableMode) {
                alert('Сохранение в режиме таблицы МВД недоступно.');
                return;
            }
            const saveAccessResponse = await checkAccess('save_data', accessLevel, userGarnizon, selectedGarnizonIndex);
            if (!saveAccessResponse.success || !saveAccessResponse.access) {
                alert('Нет доступа для сохранения данных.');
                return;
            }
            const cacheKey = `${selectedGarnizonIndex}_${data_start}_${data_end}`;
            let responseData = tableDataCache[cacheKey] || await fetchTableData('../api/output_selector.php', data_start, data_end, selectedGarnizonIndex);
            const show6and93Response = await checkAccess('show_6_and_93', accessLevel, userGarnizon, selectedGarnizonIndex);
            const calculatedNaim = ['2.3.3', '2.7.2', '2.7.5', '2.7.8'];
            $("#accordion-container tr").each(function(index) {
                const $tr = $(this);
                const number_naim = $tr.data('number_naim');
                const idsRaw = $tr.data('ids');
                const ids = Array.isArray(idsRaw)
                    ? idsRaw.map(id => parseInt(id, 10)).filter(id => !isNaN(id))
                    : String(idsRaw ?? '').split(',').map(id => parseInt(String(id).trim(), 10)).filter(id => !isNaN(id));
                const numParts = parseInt($tr.data('num_parts')) || 1;
                const isCalculated = calculatedNaim.includes(number_naim);
                if (isCalculated) {
                    return true;
                }
                const $centerTd = $tr.find('.field-center');
                const $orovdTd = $tr.find('.field-orovd');
                const centerValue = $centerTd.find('input').length ? $centerTd.find('input').val().trim() : $centerTd.text().trim();
                const orovdValue = $orovdTd.find('input').length ? $orovdTd.find('input').val().trim() : $orovdTd.text().trim();
                const shouldPreserveZeros = numParts > 1;
                if (show6and93Response.success && show6and93Response.access && selectedGarnizonIndex === '6') {
                    if (centerValue !== '' && (shouldPreserveZeros || !isZeroValue(centerValue, numParts))) {
                        formData.push({
                            id_svodki: ids[0] || 0,
                            number_naim,
                            naimenov: '',
                            kolichestvo: shouldPreserveZeros ? centerValue : centerValue.replace(/ \/ 0$/, '').replace(/^0 \/ /, ''),
                            data_start,
                            data_end,
                            garnizon: '6'
                        });
                    }
                    if (orovdValue !== '' && (shouldPreserveZeros || !isZeroValue(orovdValue, numParts))) {
                        formData.push({
                            id_svodki: ids[0] || 0,
                            number_naim,
                            naimenov: '',
                            kolichestvo: shouldPreserveZeros ? orovdValue : orovdValue.replace(/ \/ 0$/, '').replace(/^0 \/ /, ''),
                            data_start,
                            data_end,
                            garnizon: '93'
                        });
                    }
                } else {
                    if (centerValue !== '' && (shouldPreserveZeros || !isZeroValue(centerValue, numParts))) {
                        formData.push({
                            id_svodki: ids[0] || 0,
                            number_naim,
                            naimenov: '',
                            kolichestvo: shouldPreserveZeros ? centerValue : centerValue.replace(/ \/ 0$/, '').replace(/^0 \/ /, ''),
                            data_start,
                            data_end,
                            garnizon: selectedGarnizonIndex
                        });
                    }
                }
            });
            let logDetails = formData.map(d => `${d.number_naim} (${d.garnizon}): ${d.kolichestvo}`).join(', ');
            try {
                const response = await fetch('../api/update_selector.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(formData)
                });
                const result = await response.json();
                if (result.success) {
                    alert('Данные успешно сохранены.');
                    await logAction(`Сохранение данных для гарнизона ${selectedGarnizonIndex}, периода ${data_start} - ${data_end}: ${logDetails}`);
                    delete tableDataCache[cacheKey];
                    await showSelectedDates(startDateObj, endDateObj);
                    delete markedDatesCacheByGarnizon[selectedGarnizonIndex];
                } else {
                    alert('Ошибка сохранения: ' + (result.errors ? result.errors.join('\n') : result.message || 'Неизвестная ошибка'));
                }
            } catch (error) {
                console.error('Ошибка сохранения:', error);
                alert('Ошибка сохранения данных.');
            }
        }
        $(document).on('click', '.section-header', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const $header = $(this);
            const $content = $header.next('.section-content');
            const openedSection = $header.data('section');
            if ($header.hasClass('open')) {
                $header.removeClass('open');
                $content.slideUp(300);
            } else {
                $('.section-header.open').removeClass('open').next('.section-content').slideUp(300);
                $header.addClass('open');
                $content.slideDown(300, function() {
                    const offset = $header.offset().top - ($('.date-selector').outerHeight() || 0) - 15;
                    $('html, body').animate({scrollTop: offset}, 300);
                });
            }
            const picker = $('#date-range').data('daterangepicker');
            if (picker) {
                viewHistory.savePosition(selectedGarnizonIndex, selectedSubMode,
                    picker.startDate.format('DD.MM.YYYY'),
                    picker.endDate.format('DD.MM.YYYY'),
                    $header.hasClass('open') ? openedSection : null);
            }
        });
        $(document).on('click', '.chart-link', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const num = $(this).data('number-naim');
            const picker = $('#date-range').data('daterangepicker');
            const start = picker.startDate.format('DD.MM.YYYY');
            const end = picker.endDate.format('DD.MM.YYYY');
            const openedSection = $('.section-header.open').data('section') || null;
            viewHistory.savePosition(selectedGarnizonIndex, selectedSubMode, start, end, openedSection);
            const url = getCurrentChartUrl(num);
            window.location.href = url;
        });
$('#accordion-container').on('input', 'input', function() {
    const $this = $(this);
    if ($this.prop('readonly') || $this.prop('disabled')) return;
    let value = $this.val();
    const $tr = $this.closest('tr');
    const number_naim = String($tr.data('number_naim')).trim();
    const numParts = parseInt($tr.data('num_parts')) || 1;
    console.log(`🔍 INPUT: naim="${number_naim}", numParts=${numParts}, value="${value}"`);
    const isFloatingPoint = (number_naim === '16' || number_naim === '15.5');
    if (isFloatingPoint) {
        // ДЛЯ ВЕЩЕСТВЕННЫХ ЧИСЕЛ (15.5, 16)
        // ИСПРАВЛЕНИЕ: Замена запятой на точку
        value = value.replace(/,/g, '.');
       
        if (numParts > 1) {
            // С разделением (X.XX / Y.YY)
            value = value.replace(/[^0-9.\s\/]/g, '');
           
            const parts = value.split('/');
            parts.forEach((part, idx) => {
                const trimmed = part.trim();
                // В каждой части макс одна точка
                const dotParts = trimmed.split('.');
                if (dotParts.length > 2) {
                    parts[idx] = dotParts[0] + '.' + dotParts.slice(1).join('');
                } else {
                    parts[idx] = trimmed;
                }
            });
           
            value = parts.map(p => p.trim()).join(' / ').trim();
           
            // Макс один слэш
            const slashCount = (value.match(/\//g) || []).length;
            if (slashCount > 1) {
                const slashParts = value.split('/');
                value = slashParts.slice(0, 2).map(p => p.trim()).join(' / ');
            }
        } else {
            // Без разделения, просто вещественное число
            value = value.replace(/[^0-9.]/g, '');
           
            const dotParts = value.split('.');
            if (dotParts.length > 2) {
                value = dotParts[0] + '.' + dotParts.slice(1).join('');
            }
        }
    } else if (numParts > 1) {
        // ДЛЯ ЦЕЛЫХ ЧИСЕЛ С РАЗДЕЛЕНИЕМ (целые)
        value = value.replace(/[^0-9\/\s]/g, '');
        const slashCount = (value.match(/\//g) || []).length;
        if (slashCount > 1) {
            const slashParts = value.split('/');
            value = slashParts.slice(0, 2).join(' / ');
        }
        value = value.replace(/\s*\/\s*/g, ' / ').trim();
    } else {
        // ДЛЯ ЦЕЛЫХ ЧИСЕЛ БЕЗ РАЗДЕЛЕНИЯ
        value = value.replace(/\D/g, '');
    }
    $this.val(value);
    // Обновляем сумму для Тирасполя
    if (selectedGarnizonIndex === '6' && ($this.closest('td').hasClass('field-center') || $this.closest('td').hasClass('field-orovd'))) {
        const calculatedNaim = ['2.3.3', '2.7.2', '2.7.5', '2.7.8'];
        if (!calculatedNaim.includes(number_naim)) {
            updateTiraspolSum($tr);
        }
    }
    updateFormulas();
});
        let isDropdownOpen = false;
        $('#garnizon-dropdown').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const $dropdown = $(this).closest('.dropdown');
            const $menu = $dropdown.find('.dropdown-menu');
            if (isDropdownOpen) {
                $menu.hide();
                $('.dropdown-submenu').removeClass('active');
                isDropdownOpen = false;
            } else {
                $('.dropdown-menu').hide();
                $('.dropdown-submenu').removeClass('active');
                $menu.show();
                isDropdownOpen = true;
            }
        });
        $(document).on('click', '.mvd-item', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const $submenu = $(this).closest('.dropdown-submenu');
            const $submenuItems = $submenu.find('.dropdown-submenu-items');
            const isActive = $submenu.hasClass('active');
            $('.dropdown-submenu').removeClass('active');
            $('.dropdown-submenu-items').hide();
            if (!isActive) {
                $submenu.addClass('active');
                $submenuItems.show();
            }
        });
        $(document).on('click', '.submenu-item', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            const newGarnisonIndex = $(this).data('index').toString();
            const mode = $(this).data('mode').toString();
            const accessResponse = await checkAccess('dropdown', accessLevel, userGarnizon, newGarnisonIndex);
            if (!accessResponse.success || !accessResponse.access) {
                alert('Нет доступа к гарнизону МВД.');
                return;
            }
            previousGarnizonIndex = selectedGarnizonIndex;
            previousSubMode = selectedSubMode;
            selectedGarnizonIndex = newGarnisonIndex;
            selectedSubMode = mode;
            CompassState.set({ garnizon: selectedGarnizonIndex, subMode: selectedSubMode });
            $('#garnizon-dropdown').text(`МВД - ${mode === '1' ? 'Сумма' : 'Таблица'}`);
            applyGarnizonDropdownScope();
            await logAction(`Выбор гарнизона МВД - ${mode === '1' ? 'Сумма' : 'Таблица'}`);
            $('.dropdown-menu').hide();
            $('.dropdown-submenu').removeClass('active');
            isDropdownOpen = false;
            const startDate = $('#date-range').data('daterangepicker')?.startDate;
            const endDate = $('#date-range').data('daterangepicker')?.endDate;
            await showSelectedDates(startDate, endDate);
        });
        $(document).on('click', '.dropdown-item:not(.submenu-item, .mvd-item)', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            const newGarnisonIndex = $(this).data('index').toString();
            const accessResponse = await checkAccess('dropdown', accessLevel, userGarnizon, newGarnisonIndex);
            if (!accessResponse.success || !accessResponse.access) {
                alert('Нет доступа к этому гарнизону.');
                return;
            }
            if (newGarnisonIndex === selectedGarnizonIndex) return;
            previousGarnizonIndex = selectedGarnizonIndex;
            previousSubMode = selectedSubMode;
            selectedGarnizonIndex = newGarnisonIndex;
            selectedSubMode = '';
            CompassState.set({ garnizon: selectedGarnizonIndex, subMode: selectedSubMode });
            $('#garnizon-dropdown').text(garnizonNames[newGarnisonIndex]);
            applyGarnizonDropdownScope();
            await logAction(`Выбор гарнизона: ${garnizonNames[newGarnisonIndex]}`);
            $('.dropdown-menu').hide();
            $('.dropdown-submenu').removeClass('active');
            isDropdownOpen = false;
            const startDate = $('#date-range').data('daterangepicker')?.startDate;
            const endDate = $('#date-range').data('daterangepicker')?.endDate;
            await showSelectedDates(startDate, endDate);
        });
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.dropdown').length) {
                $('.dropdown-menu').hide();
                $('.dropdown-submenu').removeClass('active');
                isDropdownOpen = false;
            }
        });
        $('#instruction-btn').on('click', function(e) {
            e.preventDefault();
            $('#instructionModal').modal('show');
            logAction('Открытие инструкции');
        });
        $(document).on('click', '[data-dismiss="modal"]', function() {
            $('#instructionModal').modal('hide');
        });
        $('#back-button').on('click', function() {
            if (document.referrer && document.referrer.indexOf('report.php') !== -1) {
                window.location.href = CompassState.buildURL('../index.php');
            } else {
                window.location.href = CompassState.buildURL('../index.php');
            }
        });
        $('#show-button').on('click', async function() {
            const startDate = $('#date-range').data('daterangepicker').startDate.format('DD.MM.YYYY');
            const endDate = $('#date-range').data('daterangepicker').endDate.format('DD.MM.YYYY');
            const accessResponse = await checkAccess('show_chart', accessLevel, userGarnizon, selectedGarnizonIndex);
            if (!accessResponse.success || !accessResponse.access) {
                alert('Нет доступа к графику для выбранного гарнизона.');
                return;
            }
            window.location.href = `chart_selector.php?garnizon=${selectedGarnizonIndex}&start=${startDate}&end=${endDate}`;
        });
        $("#accordion-container").on('keydown', 'input:not([disabled])', function(e) {
            const keyCode = e.which;
            const $currentInput = $(this);
            const $row = $currentInput.closest('tr');
            const $table = $currentInput.closest('table');
            const $rows = $table.find('tr');
            const rowIndex = $rows.index($row);
            const $inputsInRow = $row.find('input:not([disabled])');
            const inputIndex = $inputsInRow.index($currentInput);
            let $nextInput;
            let nextRowIndex;
            switch (keyCode) {
                case 9: // Tab
                    e.preventDefault();
                    if (inputIndex < $inputsInRow.length - 1) {
                        $nextInput = $inputsInRow.eq(inputIndex + 1);
                    } else {
                        nextRowIndex = rowIndex + 1;
                        while (nextRowIndex < $rows.length) {
                            let $nextRowInputs = $rows.eq(nextRowIndex).find('input:not([disabled])');
                            if ($nextRowInputs.length > 0) {
                                $nextInput = $nextRowInputs.first();
                                break;
                            }
                            nextRowIndex++;
                        }
                    }
                    break;
                case 13: // Enter
                    e.preventDefault();
                    nextRowIndex = rowIndex + 1;
                    while (nextRowIndex < $rows.length) {
                        let $nextRowInputs = $rows.eq(nextRowIndex).find('input:not([disabled])');
                        if ($nextRowInputs.length > inputIndex) {
                            $nextInput = $nextRowInputs.eq(inputIndex);
                            break;
                        }
                        nextRowIndex++;
                    }
                    break;
                case 37: // Left
                    e.preventDefault();
                    if (inputIndex > 0) {
                        $nextInput = $inputsInRow.eq(inputIndex - 1);
                    }
                    break;
                case 38: // Up
                    e.preventDefault();
                    nextRowIndex = rowIndex - 1;
                    while (nextRowIndex > 0) {
                        let $nextRowInputs = $rows.eq(nextRowIndex).find('input:not([disabled])');
                        if ($nextRowInputs.length > inputIndex) {
                            $nextInput = $nextRowInputs.eq(inputIndex);
                            break;
                        }
                        nextRowIndex--;
                    }
                    break;
                case 39: // Right
                    e.preventDefault();
                    if (inputIndex < $inputsInRow.length - 1) {
                        $nextInput = $inputsInRow.eq(inputIndex + 1);
                    }
                    break;
                case 40: // Down
                    e.preventDefault();
                    nextRowIndex = rowIndex + 1;
                    while (nextRowIndex < $rows.length) {
                        let $nextRowInputs = $rows.eq(nextRowIndex).find('input:not([disabled])');
                        if ($nextRowInputs.length > inputIndex) {
                            $nextInput = $nextRowInputs.eq(inputIndex);
                            break;
                        }
                        nextRowIndex++;
                    }
                    break;
            }
            if ($nextInput && $nextInput.length) {
                $nextInput.focus();
            }
        });
        $(window).scroll(function() {
            if ($(this).scrollTop() > 200) {
                $('#back-to-top').fadeIn(300);
            } else {
                $('#back-to-top').fadeOut(300);
            }
        });
        $('#back-to-top').click(function() {
            $('html, body').animate({ scrollTop: 0 }, 500);
            return false;
        });
$(document).ready(function() {
    (async function() {
        try {
            // Сначала проверяем URL параметры (GET) — они имеют приоритет над localStorage и сессией
            const urlParams = new URLSearchParams(window.location.search);
            const urlGarnizon = urlParams.get('garnizon');
            const urlStart = urlParams.get('start');
            const urlEnd = urlParams.get('end');
            const urlSubMode = urlGarnizon && urlGarnizon.includes('-') ? urlGarnizon.split('-')[1] : null;
            // Если есть GET garnizon, используем его
            if (urlGarnizon && Object.keys(garnizonNames).includes(urlGarnizon) || (urlGarnizon && urlGarnizon.startsWith('88-'))) {
                selectedGarnizonIndex = urlGarnizon.startsWith('88-') ? '88' : urlGarnizon;
                selectedSubMode = urlSubMode || "<?php echo htmlspecialchars($initialSubMode); ?>";
            } else {
                // Иначе — из localStorage
                const lastPosition = viewHistory.getLastPosition();
                selectedGarnizonIndex = lastPosition?.garnizon || "<?php echo htmlspecialchars($initialGarnizonIndex); ?>";
                selectedSubMode = lastPosition?.subMode || "<?php echo htmlspecialchars($initialSubMode); ?>";
            }
            // Обновляем текст кнопки
            if (selectedGarnizonIndex === '88') {
                $('#garnizon-dropdown').text(selectedSubMode === '1' ? 'МВД - Сумма' : 'МВД - Таблица');
            } else {
                $('#garnizon-dropdown').text(garnizonNames[selectedGarnizonIndex] || '<?php echo htmlspecialchars($initialGarnisonText); ?>');
            }
            applyGarnizonDropdownScope();
            // Проверка доступа к гарнизону
            let garnizonAccessResponse = await checkAccess('dropdown', accessLevel, userGarnizon, selectedGarnizonIndex);
            if (!garnizonAccessResponse.success || !garnizonAccessResponse.access) {
                alert('Нет доступа к выбранному гарнизону.');
                selectedGarnizonIndex = userGarnizon;
                selectedSubMode = '';
                $('#garnizon-dropdown').text(garnizonNames[userGarnizon]);
                applyGarnizonDropdownScope();
                await logAction(`Попытка доступа к недоступному гарнизону: ${selectedGarnizonIndex}`);
            }
            // Загрузка строк
            allRows = await fetchAllRows();
            if (!allRows.length) {
                $('#accordion-container').html('<tr><td colspan="5">Нет данных для отображения</td></tr>');
                return;
            }
            // Построение структуры таблицы (с учётом текущего гарнизона/subMode)
            const isTableMode = selectedGarnizonIndex === '88' && selectedSubMode === '0';
            buildTableStructure(isTableMode);
            isTableBuilt = true;
            // Для дат: приоритет GET
            let initialStart = urlStart ? moment(urlStart, 'DD.MM.YYYY') : moment('<?php echo htmlspecialchars($startDate); ?>', 'DD.MM.YYYY');
            let initialEnd = urlEnd ? moment(urlEnd, 'DD.MM.YYYY') : moment('<?php echo htmlspecialchars($endDate); ?>', 'DD.MM.YYYY');
            // Если есть GET для периода, очищаем localStorage чтобы переопределить хранимое
            if (urlStart && urlEnd) {
                viewHistory.clear(); // Предполагая, что у viewHistory есть метод clear(); если нет, добавьте localStorage.removeItem('viewHistoryKey') или аналогично
            } else {
                // Иначе из localStorage
                const lastPosition = viewHistory.getLastPosition();
                if (lastPosition && lastPosition.startDate && lastPosition.endDate) {
                    initialStart = moment(lastPosition.startDate, 'DD.MM.YYYY');
                    initialEnd = moment(lastPosition.endDate, 'DD.MM.YYYY');
                }
            }
            $('#date-range').daterangepicker({
                startDate: initialStart,
                endDate: initialEnd,
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
            CompassState.set({
                garnizon: selectedGarnizonIndex,
                subMode: selectedSubMode,
                startDate: initialStart.format('DD.MM.YYYY'),
                endDate: initialEnd.format('DD.MM.YYYY')
            });
            // Обработчики datepicker
            $('#date-range').on('show.daterangepicker showCalendar.daterangepicker', async function() {
                if (!markedDatesCacheByGarnizon[selectedGarnizonIndex]) {
                    await markDatesInCalendar();
                }
                applyColorsToCalendar(markedDatesCacheByGarnizon[selectedGarnizonIndex] || []);
            });
            $('#date-range').on('apply.daterangepicker', async function(ev, picker) {
                try {
                    let selectedDate = picker.startDate.clone();
                    let endDate = picker.endDate.clone();
                    const today = moment();
                    const dateAccessResponse = await checkAccess('view_periods', accessLevel, userGarnizon, selectedGarnizonIndex, selectedDate.format('YYYY-MM-DD'));
                    if (!dateAccessResponse.success || !dateAccessResponse.access) {
                        await logAction(`Нет доступа к периоду: ${selectedDate.format('YYYY-MM-DD')}`);
                        picker.setStartDate(moment().subtract(1, 'days'));
                        picker.setEndDate(moment().subtract(1, 'days'));
                        picker.updateCalendars();
                        return;
                    }
                    if (endDate.isAfter(today.clone().subtract(1, 'days'))) {
                        picker.setEndDate(today.clone().subtract(1, 'days'));
                        if (selectedDate.isAfter(today.clone().subtract(1, 'days'))) {
                            picker.setStartDate(today.clone().subtract(1, 'days'));
                        }
                        selectedDate = picker.startDate.clone();
                        endDate = picker.endDate.clone();
                        picker.updateCalendars();
                    }
                    if (selectedDate.isAfter(endDate)) {
                        picker.setStartDate(endDate);
                        picker.setEndDate(selectedDate);
                        selectedDate = endDate.clone();
                        endDate = selectedDate.clone();
                        picker.updateCalendars();
                    }
                    /*if (accessLevel === '5') {
                        if (endDate.diff(selectedDate, 'days') > 13) {
                            alert('Для уровня 5 период не может превышать 14 дней.');
                            picker.setEndDate(selectedDate.clone().add(13, 'days'));
                            endDate = picker.endDate;
                            picker.updateCalendars();
                        }
                    }*/
                    const periods = markedDatesCacheByGarnizon[selectedGarnizonIndex] || [];
                    const containingPeriods = findContainingPeriods(selectedDate, endDate, periods);
                    let newStartDate = selectedDate;
                    let newEndDate = endDate;
                    if (containingPeriods.length > 0) {
                        const expanded = expandToPeriodBoundaries(selectedDate, endDate, containingPeriods);
                        newStartDate = expanded.start;
                        newEndDate = expanded.end;
                    }
                    picker.setStartDate(newStartDate);
                    picker.setEndDate(newEndDate);
                    $(this).val(newStartDate.format('DD.MM.YYYY') + ' по ' + newEndDate.format('DD.MM.YYYY'));
                    picker.updateCalendars();
                    CompassState.set({
                        garnizon: selectedGarnizonIndex,
                        subMode: selectedSubMode,
                        startDate: newStartDate.format('DD.MM.YYYY'),
                        endDate: newEndDate.format('DD.MM.YYYY')
                    });
                    await setIsEditableGlobal(newStartDate, newEndDate, containingPeriods.length > 1);
                    if (selectedGarnizonIndex !== '88') {
                        await markDatesInCalendar();
                    }
                    await showSelectedDates(newStartDate, newEndDate);
                    viewHistory.savePosition(selectedGarnizonIndex, selectedSubMode, newStartDate.format('DD.MM.YYYY'), newEndDate.format('DD.MM.YYYY'), $('.section-header.open').data('section') || null);
                    await logAction(`Выбор периода: ${newStartDate.format('DD.MM.YYYY')} по ${newEndDate.format('DD.MM.YYYY')} для гарнизона ${$('#garnizon-dropdown').text()}`);
                } catch (error) {
                    console.error('Ошибка при выборе периода:', error);
                    await logAction(`Ошибка при выборе периода: ${error.message}`);
                }
            });
            // Загрузка данных и отображение
            if (!markedDatesCacheByGarnizon[selectedGarnizonIndex]) {
                await markDatesInCalendar();
            }
            const initialPeriods = markedDatesCacheByGarnizon[selectedGarnizonIndex] || [];
            const initialContainingPeriods = findContainingPeriods(initialStart, initialEnd, initialPeriods);
            const initialHasOverlap = initialContainingPeriods.length > 1;
            await setIsEditableGlobal(initialStart, initialEnd, initialHasOverlap);
            await showSelectedDates(initialStart, initialEnd);
            if (isEditableGlobal && selectedGarnizonIndex !== '88') {
                $('#save-button').show();
            } else {
                $('#save-button').hide();
            }
            // Восстановление открытой секции (если была сохранена)
            const lastPosition = viewHistory.getLastPosition();
            if (lastPosition && lastPosition.openedSection !== null && lastPosition.openedSection !== undefined) {
                const $targetHeader = $(`.section-header[data-section="${lastPosition.openedSection}"]`);
                if ($targetHeader.length && !$targetHeader.hasClass('open')) {
                    $targetHeader.trigger('click');
                }
            }
        } catch (error) {
            console.error('Ошибка при инициализации:', error);
            await logAction(`Ошибка при инициализации: ${error.message}`);
            alert('Ошибка при загрузке данных. Попробуйте еще раз.');
        }
    })();
});
    </script>
</body>
</html>
