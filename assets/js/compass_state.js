const CompassState = (() => {
    const KEY = 'compass_state';

    const defaults = {
        garnizon: '88',
        garnizonName: 'МВД',
        startDate: null,
        endDate: null,
        subMode: ''
    };

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

    function load() {
        try {
            const raw = sessionStorage.getItem(KEY);
            return raw ? Object.assign({}, defaults, JSON.parse(raw)) : Object.assign({}, defaults);
        } catch (_) {
            return Object.assign({}, defaults);
        }
    }

    function save(state) {
        try {
            sessionStorage.setItem(KEY, JSON.stringify(state));
        } catch (_) {}
    }

    function get() {
        return load();
    }

    function set(patch) {
        const current = load();
        const state = Object.assign({}, current, patch || {});

        if (Object.prototype.hasOwnProperty.call(patch || {}, 'garnizon')) {
            state.garnizon = String(state.garnizon ?? defaults.garnizon);
            state.garnizonName = garnizonNames[state.garnizon] || state.garnizon;
        }

        if (Object.prototype.hasOwnProperty.call(patch || {}, 'startDate')) {
            state.startDate = patch.startDate || null;
        }

        if (Object.prototype.hasOwnProperty.call(patch || {}, 'endDate')) {
            state.endDate = patch.endDate || null;
        }

        if (Object.prototype.hasOwnProperty.call(patch || {}, 'subMode')) {
            state.subMode = patch.subMode || '';
        }

        save(state);
        return state;
    }

    function initFromURL() {
        const params = new URLSearchParams(window.location.search);
        const patch = {};

        if (params.has('garnizon')) patch.garnizon = params.get('garnizon');
        if (params.has('start')) patch.startDate = params.get('start');
        if (params.has('end')) patch.endDate = params.get('end');
        if (params.has('submode')) patch.subMode = params.get('submode');

        return Object.keys(patch).length ? set(patch) : load();
    }

    function buildURL(page, overrides = {}) {
        const state = Object.assign({}, load(), overrides);
        const params = new URLSearchParams();

        if (state.garnizon) params.set('garnizon', state.garnizon);
        if (state.startDate) params.set('start', state.startDate);
        if (state.endDate) params.set('end', state.endDate);
        if (state.subMode) params.set('submode', state.subMode);

        const query = params.toString();
        return query ? `${page}?${query}` : page;
    }

    function editRules(mode) {
        const state = load();
        const garnizon = String(state.garnizon || defaults.garnizon);

        if (garnizon === '88') {
            return { canEdit: false, reason: 'mvd_sum' };
        }

        if (!state.startDate || !state.endDate) {
            return { canEdit: false, reason: 'no_dates' };
        }

        const start = moment(state.startDate, 'DD.MM.YYYY', true);
        const end = moment(state.endDate, 'DD.MM.YYYY', true);
        if (!start.isValid() || !end.isValid()) {
            return { canEdit: false, reason: 'bad_dates' };
        }

        const days = end.diff(start, 'days') + 1;

        if (mode === 'svodki') {
            return days === 1 ? { canEdit: true, days } : { canEdit: false, reason: 'multi_day', days };
        }

        if (mode === 'selector') {
            return days <= 14 ? { canEdit: true, days } : { canEdit: false, reason: 'over_14', days };
        }

        return { canEdit: true, days };
    }

    return { get, set, initFromURL, buildURL, editRules, garnizonNames };
})();

