@php
    $record = $getRecord();
    $isBlocked = $record?->is_blocked ?? $getState() ?? false;
@endphp

<div class="flex items-center">
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $isBlocked ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
        {{ $isBlocked ? 'Blocked' : 'Active' }}
    </span>
</div>

