# Compass — Единый проект отчётов

## Структура проекта

```
compass_unified/
├── index.php               ← Главная страница (навигация)
├── pages/
│   ├── selector.php        ← Еженедельные отчёты (селектор)
│   ├── svodki.php          ← Суточные сводки
│   ├── chart_selector.php  ← Графики по селектору
│   └── chart_svodki.php    ← Графики по сводкам
├── api/
│   ├── storage.php         ← Ядро: работа с JSON-файлами
│   ├── output_selector.php ← GET данных еженедельных отчётов
│   ├── update_selector.php ← SAVE данных еженедельных отчётов
│   ├── output_sel_deg.php  ← GET данных суточных сводок
│   ├── update_sel_deg.php  ← SAVE данных суточных сводок
│   ├── marked_dates.php    ← Отмеченные периоды для календаря
│   ├── get_chart.php       ← Данные для графиков
│   └── stub.php            ← Заглушка (access_control / log)
├── data/                   ← JSON-хранилище (создаётся автоматически)
│   ├── text_selector.json  ← Справочник пунктов (selector)
│   ├── svodki_select.json  ← Справочник пунктов (svodki)
│   ├── itog_selector.json  ← Данные еженедельных отчётов
│   └── itog_sel_deg.json   ← Данные суточных сводок
└── assets/
    └── css/
        ├── style_selector.css
        ├── style_sel_deg.css
        └── krim.css
```

## Установка

1. Разместите папку `compass_unified/` на PHP-сервере (Apache/Nginx + PHP 8.0+)
2. Убедитесь, что папка `data/` доступна для записи:
   ```bash
   chmod 755 data/
   ```
3. Откройте `index.php` в браузере

## Хранилище данных

Проект не использует базу данных. Все данные хранятся в JSON-файлах в папке `data/`.

При первом запуске файлы создаются автоматически с дефолтными справочными данными.

### Редактирование справочников

Справочник пунктов можно редактировать напрямую в JSON:

- `data/text_selector.json` — пункты для еженедельного селектора
- `data/svodki_select.json` — пункты для суточных сводок

### Резервное копирование

Достаточно скопировать папку `data/`.

## API

Все API-эндпоинты принимают POST-запросы с JSON-телом.

| Эндпоинт | Описание |
|---|---|
| `api/output_selector.php` | Получить данные еженедельного отчёта |
| `api/update_selector.php` | Сохранить данные еженедельного отчёта |
| `api/output_sel_deg.php` | Получить данные суточной сводки |
| `api/update_sel_deg.php` | Сохранить данные суточной сводки |
| `api/marked_dates.php` | Отмеченные периоды для календаря |
| `api/get_chart.php` | Данные для графиков |

## Настройка справочников

Чтобы добавить новый пункт в справочник, отредактируйте `data/text_selector.json`:

```json
{
  "id": 17,
  "number_naim": "9",
  "naimenov": "Новый показатель",
  "section": 2
}
```
