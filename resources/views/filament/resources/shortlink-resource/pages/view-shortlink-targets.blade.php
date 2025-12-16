@php
    /** @var \App\Models\Shortlink $record */
    $record = $this->record;
@endphp

<x-filament::page>
    <div class="fi-ta">
        {{ $this->table }}
    </div>
</x-filament::page>

