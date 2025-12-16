@php
    /** @var \App\Models\Shortlink $record */
    $record = $this->record;
@endphp

<x-filament::page>
    <x-slot name="heading">
        <span class="text-gray-200 dark:text-gray-300">Shortlink Analytics:</span>
        <span
            class="font-mono"
            style="color: #facc15;" {{-- force amber/yellow so it clearly differs from white --}}
        >
            {{ $record->short_code }}
        </span>
    </x-slot>

    <x-slot name="subheading">
        <span class="text-sm text-gray-500">
            Total clicks:
            <span class="font-semibold text-gray-900">{{ $record->redirectLogs()->count() }}</span>
        </span>
    </x-slot>

    <div class="space-y-6">
        <div class="flex flex-wrap gap-2 justify-start md:justify-end">
            <x-filament::button tag="a" href="{{ \App\Filament\Resources\ShortlinkResource::getUrl('index') }}" icon="heroicon-o-arrow-left">
                Back to Shortlinks
            </x-filament::button>
            <x-filament::button
                tag="a"
                href="{{ route('shortlinks.analytics.export', $record) }}"
                color="gray"
                icon="heroicon-o-arrow-down-tray"
            >
                Export to Excel
            </x-filament::button>
        </div>

        {{-- Summary info --}}
        <div class="grid gap-4 md:grid-cols-3">
            <x-filament::section>
                <x-slot name="heading">Short URL</x-slot>
                <x-slot name="description">Basic information about this shortlink.</x-slot>

                <div class="space-y-1 text-sm">
                    <p
                        class="font-mono text-xl md:text-2xl font-semibold"
                        style="color: #facc15;" {{-- force yellow so it does not follow white text color --}}
                    >
                        {{ $record->short_code }}
                    </p>
                    <p class="text-gray-500">
                        Created at:
                        <span class="font-medium text-gray-900">
                            {{ $record->created_at?->format('d M Y H:i') }}
                        </span>
                    </p>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Total Clicks</x-slot>
                <x-slot name="description">Accumulated redirects.</x-slot>

                <p class="text-3xl font-semibold text-gray-900">
                    {{ $record->redirectLogs()->count() }}
                </p>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Last Access</x-slot>
                <x-slot name="description">Time of the last click.</x-slot>

                @php
                    $lastLog = $record->redirectLogs()->latest()->first();
                @endphp

                @if ($lastLog)
                    <p class="text-sm text-gray-900">
                        {{ $lastLog->created_at->format('d M Y H:i') }}
                    </p>
                @else
                    <p class="text-sm text-gray-500">
                        No clicks yet.
                    </p>
                @endif
            </x-filament::section>
        </div>

        {{-- 30 days clicks chart --}}
        <x-filament::section>
            <x-slot name="heading">Clicks in the Last 30 Days</x-slot>

            <canvas id="clicksChart" height="120"></canvas>
        </x-filament::section>

        {{-- Redirect detail table (Filament table) --}}
        <x-filament::section>
            <x-slot name="heading">Redirect Details</x-slot>
            <x-slot name="description">List of clicks using the standard Filament table.</x-slot>

            {{ $this->table }}
        </x-filament::section>
    </div>

    {{-- Chart.js via CDN --}}
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('alpine:init', () => {
                const ctx = document.getElementById('clicksChart');
                if (!ctx) return;

                const labels = @json($this->chartLabels);
                const data = @json($this->chartData);

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Clicks',
                            data,
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34, 197, 94, 0.15)',
                            fill: true,
                            tension: 0.4,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                },
                            },
                        },
                    },
                });
            });
        </script>
    @endpush

    {{-- Print styles --}}
    <style>
        @media print {
            nav, aside, header, .fi-header, .fi-sidebar, .fi-topbar, .fi-breadcrumbs, .fi-page-sub-navigation {
                display: none !important;
            }

            body {
                background: white !important;
            }

            #clicksChart {
                max-height: 300px !important;
            }
        }
    </style>
</x-filament::page>


