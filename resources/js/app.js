

import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

window.Alpine = Alpine;

Alpine.plugin(focus);

// The in-page dropdown behind <x-select-menu>: a trigger field plus an
// absolutely-positioned option panel replaces the native <select> popup, so
// the menu can never escape its layout — laptop-browser device-inspect mode
// draws native popups outside the simulated phone frame, the one artifact no
// page CSS can fix. Data ships on the root element's data-* attributes
// (server-rendered, so the component needs no fetch); the form contract is
// a hidden input synced to `selected`, and <noscript> still renders a plain
// native <select> from the same options when scripts are off.
Alpine.data('selectMenu', () => ({
    open: false,
    highlighted: -1,
    ready: false,

    uid: '',
    placeholder: '',
    placeholderWhenEmpty: false,
    selected: '',
    searchable: false,
    searchEmpty: 'Tidak ada pilihan yang cocok',
    query: '',
    groups: [],     // server options, grouped, unfiltered
    allOptions: [], // flat, unfiltered — the trigger's selected label source
    rows: [],       // the current panel content, in order: headers + options
    optionRows: [], // options only, each carrying its row's index for focus

    init() {
        // Anchored: inside event handlers (panel row clicks) Alpine's $el is
        // the event's element, not this root — so resolve everything the
        // auto-submit needs ONCE, at init, while $el IS the root.
        this.$_root = this.$el;
        this.$_form = this.$el.closest('form');

        this.uid = this.$el.dataset.uid;
        this.placeholder = this.$el.dataset.placeholder ?? '';
        this.placeholderWhenEmpty = this.$el.dataset.placeholderWhenEmpty === '1';
        this.selected = String(this.$el.dataset.value ?? '');
        this.searchable = this.$el.dataset.searchable === '1';
        this.searchEmpty = this.$el.dataset.searchEmpty || this.searchEmpty;

        const raw = JSON.parse(this.$el.dataset.options ?? '[]');

        // Normalise both shapes once: a group is {label, options[]}; a flat
        // entry is a headerless one-option group.
        this.groups = raw.map((entry) => Array.isArray(entry.options)
            ? { label: entry.label ?? null, options: entry.options }
            : { label: null, options: [entry] });

        // The trigger's label must survive filtering: it reads the unfiltered
        // list, so a query that hides the current room never blanks the field.
        this.allOptions = this.groups.flatMap((group) =>
            group.options.map((option) => ({ value: String(option.value), label: option.label }))
        );

        this.rebuild();
        this.ready = true;
    },

    /**
     * Re-derive the panel from the search query. A group header survives only
     * while one of its own options matches, so a search for "lab" never leaves
     * empty floor headings behind. With no matches the panel says so — an
     * empty menu reads as "no rooms", not "no match".
     */
    rebuild() {
        const query = this.query.trim().toLowerCase();

        this.rows = [];
        this.optionRows = [];

        for (const group of this.groups) {
            const matches = group.options.filter((option) =>
                query === '' || option.label.toLowerCase().includes(query)
            );

            if (! matches.length) continue;

            if (group.label) this.rows.push({ type: 'header', label: group.label });

            for (const option of matches) {
                const rowIdx = this.rows.length;
                this.rows.push({ type: 'option', label: option.label, value: option.value, rowIdx });
                this.optionRows.push({ value: String(option.value), label: option.label, rowIdx });
            }
        }

        if (this.optionRows.length === 0) {
            this.rows.push({ type: 'empty', label: this.searchEmpty });
        }

        // First match is the default pick: Enter in the search field takes it.
        this.highlighted = this.optionRows.length ? 0 : -1;
    },

    get selectedLabel() {
        const hit = this.allOptions.find((option) => option.value === this.selected);
        return hit ? hit.label : '';
    },

    /**
     * What the trigger reads: filter pickers name their dimension ("Kategori")
     * until a value is applied, then read the pick; the menu rows keep the
     * descriptive clear row ("Semua kategori").
     */
    get triggerLabel() {
        if (this.placeholderWhenEmpty && this.selected === '') return this.placeholder;

        return this.selectedLabel !== '' ? this.selectedLabel : this.placeholder;
    },

    /** The panel row (index into `rows`) that `highlighted` points at. */
    get highlightedRow() {
        return this.highlighted === -1 ? -1 : (this.optionRows[this.highlighted]?.rowIdx ?? -1);
    },

    /** Open the panel; `focusOption` is for keyboard, so arrows land in it. */
    show(focusOption = false) {
        if (this.open) return this.close();
        if (!this.optionRows.length) return;

        const hit = this.optionRows.findIndex((option) => option.value === this.selected);
        this.highlighted = hit === -1 ? 0 : hit;
        this.open = true;

        this.$nextTick(() => {
            // A searchable picker opens with the caret in the search field:
            // the menu is long, and typing is the shortest path through it.
            if (this.searchable) {
                this.$refs.search?.focus();
            } else if (focusOption) {
                this.$_focusRow(this.highlighted);
            }
        });
    },

    close() {
        this.open = false;
        this.highlighted = -1;

        // The next open starts from the full list, not the last search.
        if (this.query !== '') {
            this.query = '';
            this.rebuild();
        }
    },

    /**
     * Opt-in auto-submit (`data-autosubmit="1"` on the component root,
     * set by the blade `form-autosubmit` prop): a pick commits the hidden
     * input FIRST — Alpine flushes reactive x-bind effects only after
     * this call stack returns, so a submit cannot rely on the binding —
     * then requests the surrounding form to submit. Filter pickers only;
     * POST forms must never set the flag.
     *
     * Everything is read from `$_root`/`$_form` anchored in init(): during
     * a pick, $el is the clicked option row and sees neither the dataset
     * flag nor a stable identity. The submit runs in a macrotask so it
     * starts outside the click's dispatch stack (a synchronous
     * requestSubmit there was observed to be ignored without any error).
     */
    $_autosubmit() {
        if (this.$_root.dataset.autosubmit !== '1') return;
        const hidden = this.$_root.querySelector('input[type="hidden"]');
        if (hidden) hidden.value = this.selected;
        if (this.$_form) setTimeout(() => this.$_form.requestSubmit(), 0);
    },

    move(direction) {
        if (!this.open) return this.show(true);
        this.jump(Math.min(this.optionRows.length - 1, Math.max(0, this.highlighted + direction)));
    },

    jump(index) {
        if (!this.open) return this.show(true);
        this.highlighted = index;
        this.$nextTick(() => this.$_focusRow(index));
    },

    pick(index = this.highlighted) {
        const row = this.optionRows[index];
        if (row) this.select(row.value);
    },

    select(value) {
        this.selected = String(value);
        this.close();
        this.$_autosubmit();
        this.$dispatch('changed', { value: this.selected });
        this.$refs.trigger?.focus();
    },

    /** Move keyboard focus onto the highlighted row (scrolls it into view). */
    $_focusRow(index) {
        const row = this.optionRows[index];
        if (row) document.getElementById(`${this.uid}-${row.rowIdx}`)?.focus();
    },
}));

Alpine.start();

// Focus the first control the server flagged — a validation redirect lands the
// visitor at the top of a re-rendered page, and the field that needs them is
// usually below the fold. The rendered error list (`role="alert"`, from
// x-input-error) announces the problem on arrival; this moves the caret to the
// field it names. Progressive: without JS the page is unchanged, and every
// error still reads below its own field.
const firstFieldError = document.querySelector('[data-field-error]');

if (firstFieldError) {
    // [data-select-menu-trigger]: the native <select> is now the picker
    // trigger button, which is what a validation redirect wanted to reach.
    const control = firstFieldError
        .closest('div')
        ?.querySelector('input:not([type="hidden"]), select, textarea, [data-select-menu-trigger]');

    if (control) {
        control.focus({ preventScroll: false });
    }
}
