@props(['messages'])

@if ($messages)
    {{-- role="alert" makes the list a live region: a validation redirect re-renders
         the page with these already in place and assistive tech announces them on
         arrival. `data-field-error` is the handle app.js looks for to focus the
         first control that still needs the visitor — progressive: without JS the
         page is unchanged and every error still reads below its own field. --}}
    <ul {{ $attributes->merge(['class' => 'text-sm text-error space-y-1', 'role' => 'alert', 'data-field-error' => '']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
