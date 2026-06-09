<?php
/**
 * JSON-хранилище данных (замена БД)
 * Файлы хранятся в папке /data/
 */

define('DATA_DIR', __DIR__ . '/../data/');

// Убедимся, что папка data существует
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

/**
 * Загружает JSON-файл и возвращает массив
 */
function loadJson(string $file): array {
    $path = DATA_DIR . $file;
    if (!file_exists($path)) return [];
    $content = file_get_contents($path);
    if (!$content) return [];
    if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) {
        $content = substr($content, 3);
    }
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

/**
 * Сохраняет массив в JSON-файл
 */
function saveJson(string $file, array $data): bool {
    $path = DATA_DIR . $file;
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    return file_put_contents($path, $json) !== false;
}

/**
 * Инициализирует справочные данные, если их ещё нет
 */
function initDefaultData(): void {
    // ===== Справочник для SELECTOR (еженедельные отчёты) =====
    if (!file_exists(DATA_DIR . 'text_selector.json')) {
        $items = [
            ['id' => 1,  'number_naim' => '1',    'naimenov' => 'Поступило заявлений и сообщений',         'section' => 1],
            ['id' => 2,  'number_naim' => '2',    'naimenov' => 'Зарегистрировано преступлений',            'section' => 1],
            ['id' => 3,  'number_naim' => '2.1',  'naimenov' => 'Тяжкие и особо тяжкие',                   'section' => 1],
            ['id' => 4,  'number_naim' => '2.2',  'naimenov' => 'Преступления против личности',            'section' => 1],
            ['id' => 5,  'number_naim' => '2.3',  'naimenov' => 'Убийства (покушения)',                     'section' => 1],
            ['id' => 6,  'number_naim' => '2.4',  'naimenov' => 'Причинение тяжкого вреда здоровью',       'section' => 1],
            ['id' => 7,  'number_naim' => '2.5',  'naimenov' => 'Изнасилования (покушения)',                'section' => 1],
            ['id' => 8,  'number_naim' => '2.6',  'naimenov' => 'Кражи',                                    'section' => 1],
            ['id' => 9,  'number_naim' => '2.7',  'naimenov' => 'Грабежи (разбои)',                         'section' => 1],
            ['id' => 10, 'number_naim' => '2.8',  'naimenov' => 'Мошенничество',                            'section' => 1],
            ['id' => 11, 'number_naim' => '3',    'naimenov' => 'Раскрыто преступлений',                    'section' => 1],
            ['id' => 12, 'number_naim' => '4',    'naimenov' => 'Нераскрытые преступления',                 'section' => 1],
            ['id' => 13, 'number_naim' => '5',    'naimenov' => 'Раскрываемость %',                         'section' => 1],
            ['id' => 14, 'number_naim' => '6',    'naimenov' => 'Задержано подозреваемых',                  'section' => 2],
            ['id' => 15, 'number_naim' => '7',    'naimenov' => 'Изъято оружия (единиц)',                   'section' => 2],
            ['id' => 16, 'number_naim' => '8',    'naimenov' => 'Административные правонарушения',           'section' => 2],
        ];
        saveJson('text_selector.json', $items);
    }

    // ===== Справочник для СВОДКИ (суточные отчёты) =====
    if (!file_exists(DATA_DIR . 'svodki_select.json')) {
        $items = [
            ['id' => 1,  'number_naim' => '1',    'naimenov' => 'Поступило заявлений',                       'pole' => 1],
            ['id' => 2,  'number_naim' => '2',    'naimenov' => 'Зарегистрировано преступлений',              'pole' => 2],
            ['id' => 3,  'number_naim' => '2.1',  'naimenov' => 'Тяжкие и особо тяжкие',                     'pole' => 2],
            ['id' => 4,  'number_naim' => '2.3',  'naimenov' => 'Убийства',                                   'pole' => 2],
            ['id' => 5,  'number_naim' => '2.7',  'naimenov' => 'Грабежи',                                    'pole' => 2],
            ['id' => 6,  'number_naim' => '3',    'naimenov' => 'Раскрыто',                                   'pole' => 1],
            ['id' => 7,  'number_naim' => '4',    'naimenov' => 'Нераскрытые',                                'pole' => 1],
            ['id' => 8,  'number_naim' => '5',    'naimenov' => 'Задержано лиц',                              'pole' => 1],
        ];
        saveJson('svodki_select.json', $items);
    }

    // ===== Пустые хранилища данных =====
    if (!file_exists(DATA_DIR . 'itog_selector.json')) {
        saveJson('itog_selector.json', []);
    }
    if (!file_exists(DATA_DIR . 'itog_sel_deg.json')) {
        saveJson('itog_sel_deg.json', []);
    }
    if (!file_exists(DATA_DIR . 'incidents.json')) {
        saveJson('incidents.json', []);
    }
}

initDefaultData();

// ====== Вспомогательные функции ======

/**
 * Генерирует следующий уникальный ID для записей
 */
function nextId(array $records): int {
    if (empty($records)) return 1;
    return max(array_column($records, 'id')) + 1;
}

/**
 * Парсит дату из формата dd.mm.yyyy или yyyy-mm-dd в объект DateTime
 */
function parseDate(string $date): ?DateTime {
    $dt = DateTime::createFromFormat('d.m.Y', $date);
    if ($dt) return $dt;
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt ?: null;
}
