{{-- The native <select>, rebuilt in-page: a .field trigger button (or the
     opt-in capsule shell filter toolbars use) plus an
     absolutely-positioned iOS-menu option panel (x-select-menu). It exists
     because laptop-browser device-inspect draws NATIVE select popups outside
     the simulated phone frame — an artifact of the browser itself, not the
     page — so the app's dropdowns render as real DOM instead and can never
     leave the layout. Behavior lives in the registered `selectMenu` Alpine
     data component (resources/js/app.js); the form contract is the hidden
     input synced to the selection.
     Progressive both ways: with JS on, <noscript> renders nothing and the
     hidden input carries the value; with JS off, `x-if` renders nothing and
     the <noscript> select (same options, native `required`) submits. --}}
@props([
    'name',
    'options',              // flat entries {value,label} or groups {label,options:[…]}
    'value' => '',          // the current/old selection
    'placeholder' => '',    // shown (and submitted as '') when nothing chosen
    'id' => null,           // trigger id — the <label for> target, like the old select
    'nativeLabel' => null,  // accessible name for the JS-off <select>
    'searchable' => false,  // opt-in: a search field above a long option list
    'searchPlaceholder' => 'Cari…',
    'searchEmpty' => 'Tidak ada pilihan yang cocok',
    'capsule' => false,     // opt-in: the filter-toolbar shell (pill family)
    'active' => false,      // capsule only: the filter is currently applied
    'placeholderWhenEmpty' => false, // filter triggers name their dimension ("Kategori") until a value is picked
])

@php
    $uid = 'select-menu-'.str()->uuid();

    // A group entry renders a header then its options; a flat entry is its
    // own headerless row.
    $groups = collect($options)->map(fn ($entry) => is_array($entry['options'] ?? null)
        ? ['label' => $entry['label'] ?? null, 'options' => $entry['options']]
        : ['label' => null, 'options' => [$entry]]);

    $flat = $groups->flatMap(fn ($group) => $group['options']);

    $selected = (string) $value;
    $selectedLabel = $flat
        ->first(fn ($option) => (string) ($option['value'] ?? '') === $selected)['label'] ?? '';

    // Filter triggers name the dimension they filter ("Kategori") while the
    // menu's first row stays the descriptive clear row ("Semua kategori") —
    // the same split the period control makes ("Periode" / "Semua periode").
    $triggerText = ($placeholderWhenEmpty && $selected === '')
        ? $placeholder
        : ($selectedLabel !== '' ? $selectedLabel : $placeholder);

    // Opt-in auto-submit: pick commits the hidden input then submits the
    // surrounding form. Filter pickers (GET search forms) set this; POST
    // forms like the complaint or user forms must never.
    $autosubmit = ($attributes->offsetExists('form-autosubmit'))
        || boolval($attributes->get('data-autosubmit', false));

    $rpm = $attributes->filter(fn ($v, $k) => ! in_array($k, ['form-autosubmit', 'data-autosubmit'], true));

    // Filter toolbars read as one family of pill triggers, while form pickers
    // keep the .field skin that lines up with the inputs around them — so the
    // shell is opt-in, never a global restyle. `active` is capsule-only and
    // paints an applied filter the way the period trigger does.
    $triggerClass = $capsule
        ? 'flex min-h-11 w-full cursor-pointer select-none items-center justify-between gap-2 rounded-full border px-4 text-sm font-semibold transition-colors '.($active
            ? 'bg-primary border-primary text-on-primary hover:bg-primary/90'
            : 'border-outline bg-surface text-primary hover:bg-primary-container/40')
        : 'field flex min-h-11 cursor-pointer items-center justify-between gap-2 text-start';
@endphp

<div
    x-data="selectMenu"
    data-autosubmit="{{ $autosubmit ? '1' : '' }}"
    data-uid="{{ $uid }}"
    data-placeholder="{{ $placeholder }}"
    data-value="{{ $selected }}"
    data-options="{{ json_encode($groups->all(), JSON_UNESCAPED_UNICODE) }}"
    data-searchable="{{ $searchable ? '1' : '' }}"
    data-search-empty="{{ $searchEmpty }}"
    data-placeholder-when-empty="{{ $placeholderWhenEmpty ? '1' : '' }}"
    @keydown.escape="if (!open) return; close(); $refs.trigger.focus()"
    @click.outside="close()"
    {{ $rpm->merge(['class' => 'relative']) }}
>
    {{-- The form contract. Rendered only once Alpine boots (`template x-if`)
         so the noscript select is the sole same-name field without JS; the
         component keeps it in sync with the picker. --}}
    <template x-if="ready">
        <input type="hidden" name="{{ $name }}" x-bind:value="selected">
    </template>

    {{-- The trigger keeps the exact .field skin the native select wore, so
         every other control around it still lines up. Its server-rendered
         label is the pre-boot SSR text (x-text re-evaluates to the same
         thing on boot, so nothing flashes). --}}
    <button
        type="button"
        id="{{ $id ?? ($uid.'-trigger') }}"
        x-ref="trigger"
        data-select-menu-trigger
        aria-haspopup="listbox"
        @if ($capsule && $active) aria-current="true" @endif
        x-bind:aria-expanded="open ? 'true' : 'false'"
        @click="show()"
        @keydown.down.prevent="show(true)"
        @keydown.up.prevent="show(true)"
        @keydown.enter.prevent="show(true)"
        @keydown.space.prevent="show(true)"
        class="{{ $triggerClass }}"
    >
        <span
            class="min-w-0 truncate text-sm"
            x-text="triggerLabel"
        >{{ $triggerText }}</span>
        {{-- The same chevron glyph every other menu trigger in the app uses;
             it turns over while the menu is open. --}}
        <svg class="{{ $capsule ? 'h-3.5 w-3.5 shrink-0 opacity-70' : 'h-4 w-4 shrink-0 text-on-surface-variant' }} transition-transform"
            x-bind:class="open ? 'rotate-180' : ''"
            viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
    </button>

    {{-- The menu opens over the fields beneath it and scrolls with the page
         — inside the layout by construction, which is the entire point. The
         listbox lives on the inner scroller so the search field can sit above
         it without becoming a listbox child. --}}
    <div
        x-show="open"
        x-cloak
        x-trap.inert.noscroll="open"
        x-transition
        @keydown.down.prevent="move(1)"
        @keydown.up.prevent="move(-1)"
        @keydown.home.prevent="jump(0)"
        @keydown.end.prevent="jump(optionRows.length - 1)"
        @keydown.enter.prevent="pick()"
        @keydown.space.prevent="pick()"
        class="absolute inset-x-0 top-full z-20 mt-1.5 overflow-hidden rounded-2xl border border-outline-variant bg-surface-container shadow-lg shadow-scrim/5"
    >
        @if ($searchable)
            {{-- Long lists (the ~105-room picker) get a search field: options
                 stay server-rendered in data-options and Alpine filters them
                 in place. Space is stopped here so the panel's pick-on-space
                 shortcut cannot swallow a space inside a room name; the
                 first match is pre-highlighted, so Enter takes it. --}}
            <div class="border-b border-outline-variant/60 p-2">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-on-surface-variant">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </span>
                    <input
                        type="text"
                        x-ref="search"
                        :value="query"
                        @input="query = $event.target.value; rebuild()"
                        @keydown.space.stop
                        @keydown.down.prevent.stop="jump(0)"
                        @keydown.up.prevent.stop="jump(optionRows.length - 1)"
                        placeholder="{{ $searchPlaceholder }}"
                        aria-label="{{ $searchPlaceholder }}"
                        autocomplete="off"
                        spellcheck="false"
                        class="field w-full py-2 ps-9"
                    />
                </div>
            </div>
        @endif

        <div
            x-ref="list"
            role="listbox"
            tabindex="-1"
            aria-label="{{ $nativeLabel }}"
            class="max-h-72 overflow-y-auto p-1.5"
        >
            <template x-for="(row, ri) in rows" :key="ri">
                <div>
                    <template x-if="row.type === 'header'">
                        <p
                            x-text="row.label"
                            class="px-3 pt-2.5 pb-1 text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant"
                        ></p>
                    </template>
                    <template x-if="row.type === 'empty'">
                        <p
                            x-text="row.label"
                            class="px-3 py-6 text-center text-sm text-on-surface-variant"
                        ></p>
                    </template>
                    <template x-if="row.type === 'option'">
                        {{-- Keyboard focus rides this row (tabindex -1, focus()
                             from the data component); `highlightedRow` paints a
                             soft key-nav ring on top of either fill. --}}
                        <div
                            :id="uid + '-' + ri"
                            role="option"
                            tabindex="-1"
                            :aria-selected="selected === String(row.value)"
                            :class="[
                                highlightedRow === ri ? 'ring-2 ring-primary/50' : '',
                                selected === String(row.value)
                                    ? 'bg-primary-container text-on-primary-container'
                                    : 'text-on-surface hover:bg-secondary-container',
                            ]"
                            @click="select(String(row.value))"
                            class="my-0.5 flex cursor-pointer items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors"
                        >
                            <span class="min-w-0 truncate" x-text="row.label"></span>
                            <svg x-show="selected === String(row.value)"
                                class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    {{-- The no-JS leg: a plain native <select> from the same options. With
         scripts on, body <noscript> content never parses, so this select is
         absent and the hidden input above is the field; scripts off, it is
         the field itself — native required and browser validation included,
         the same submission a native dropdown would make. --}}
    <noscript>
        <select name="{{ $name }}" class="field mt-1.5" required
            @if ($nativeLabel) aria-label="{{ $nativeLabel }}" @endif>
            @if ($placeholder !== '')
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($groups as $group)
                @if ($group['label'] !== null && count($group['options']))
                    <optgroup label="{{ $group['label'] }}">
                        @foreach ($group['options'] as $option)
                            <option value="{{ $option['value'] }}"
                                @selected((string) $option['value'] === $selected)>
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </optgroup>
                @elseif (count($group['options']))
                    @foreach ($group['options'] as $option)
                        <option value="{{ $option['value'] }}"
                            @selected((string) $option['value'] === $selected)>
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                @endif
            @endforeach
        </select>
    </noscript>
</div>
