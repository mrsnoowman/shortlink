<x-filament::page>
    <div class="space-y-4">
        <div class="text-sm text-gray-600">
            Overview of the access rights for each role:
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <x-filament::section heading="Master" icon="heroicon-o-shield-exclamation">
                <x-slot name="description">Full access</x-slot>
                <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                    <li>Can manage all users (create/edit/delete) and all roles.</li>
                    <li>Can create shortlinks for any user.</li>
                    <li>Can create domain checks for any user.</li>
                    <li>Can see all shortlinks and domain checks.</li>
                    <li>Not limited by user selection in forms.</li>
                </ul>
            </x-filament::section>

            <x-filament::section heading="Admin" icon="heroicon-o-shield-check">
                <x-slot name="description">Limited access</x-slot>
                <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                    <li>Can only create new users with the <strong>user</strong> role.</li>
                    <li>Can create shortlinks only for themselves.</li>
                    <li>Can create domain checks only for themselves.</li>
                    <li>Can see only their own shortlinks.</li>
                    <li>Short/domain/domain‑check limits still apply per user.</li>
                </ul>
            </x-filament::section>

            <x-filament::section heading="User" icon="heroicon-o-user">
                <x-slot name="description">Basic access</x-slot>
                <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                    <li>Can create shortlinks only for themselves.</li>
                    <li>Can add domains (target URLs) to their own shortlinks within their limits.</li>
                    <li>Can create domain checks only for themselves.</li>
                    <li>Can see only their own shortlinks and domain checks.</li>
                </ul>
            </x-filament::section>
        </div>
    </div>
</x-filament::page>

