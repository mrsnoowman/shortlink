<div class="fi-ta-content divide-y divide-gray-200 overflow-x-auto dark:divide-white/5">
    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
        <thead class="divide-y divide-gray-200 dark:divide-white/5">
            <tr class="bg-gray-50 dark:bg-white/5">
                <th scope="col" class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                    <span class="group flex w-full items-center gap-x-1.5">
                        <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">
                            URL
                        </span>
                    </span>
                </th>
                <th scope="col" class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                    <span class="group flex w-full items-center gap-x-1.5">
                        <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">
                            Status
                        </span>
                    </span>
                </th>
                <th scope="col" class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                    <span class="group flex w-full items-center gap-x-1.5">
                        <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">
                            Last Updated
                        </span>
                    </span>
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
            @foreach($targetUrls as $targetUrl)
                <tr class="fi-ta-row group transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="px-3 py-4">
                            <div class="flex flex-col gap-x-3 gap-y-1">
                                <div class="fi-ta-text-item inline-flex gap-x-1.5 items-center justify-start group/context">
                                    <span class="text-sm leading-6 text-gray-950 dark:text-white break-words">
                                        {{ $targetUrl->url }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="px-3 py-4">
                            <div class="flex flex-col gap-x-3 gap-y-1">
                                <div class="fi-ta-text-item inline-flex gap-x-1.5 items-center justify-start group/context">
                                    @if($targetUrl->is_blocked)
                                        <span class="fi-badge flex items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset fi-color-danger bg-danger-50 text-danger-700 ring-danger-600/10 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30">
                                            <x-filament::icon
                                                icon="heroicon-o-x-circle"
                                                class="h-3 w-3"
                                            />
                                            Blocked
                                        </span>
                                    @else
                                        <span class="fi-badge flex items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset fi-color-success bg-success-50 text-success-700 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">
                                            <x-filament::icon
                                                icon="heroicon-o-check-circle"
                                                class="h-3 w-3"
                                            />
                                            Active
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="px-3 py-4">
                            <div class="flex flex-col gap-x-3 gap-y-1">
                                <div class="fi-ta-text-item inline-flex gap-x-1.5 items-center justify-start group/context">
                                    <span class="text-sm leading-6 text-gray-950 dark:text-white">
                                        {{ $targetUrl->updated_at->format('d M Y, H:i') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

