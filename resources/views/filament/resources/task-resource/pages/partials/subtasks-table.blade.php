@php
    $subtasks = $record->subtasks;
@endphp

<div class="fi-ta-content overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
        <thead class="divide-y divide-gray-200 dark:divide-white/5">
            <tr class="bg-gray-50 dark:bg-white/5">
                <th class="px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                    <span class="group flex w-full items-center gap-x-1.5">
                        <span class="fi-ta-header-cell-label text-xs font-semibold text-gray-950 dark:text-white">
                            Task Title
                        </span>
                    </span>
                </th>
                <th class="px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                    <span class="group flex w-full items-center gap-x-1.5">
                        <span class="fi-ta-header-cell-label text-xs font-semibold text-gray-950 dark:text-white">
                            Task Type
                        </span>
                    </span>
                </th>
                <th class="px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                    <span class="group flex w-full items-center gap-x-1.5">
                        <span class="fi-ta-header-cell-label text-xs font-semibold text-gray-950 dark:text-white">
                            Assigned To
                        </span>
                    </span>
                </th>
                <th class="px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                    <span class="group flex w-full items-center gap-x-1.5">
                        <span class="fi-ta-header-cell-label text-xs font-semibold text-gray-950 dark:text-white">
                            Due Date
                        </span>
                    </span>
                </th>
                <th class="px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                    <span class="group flex w-full items-center gap-x-1.5">
                        <span class="fi-ta-header-cell-label text-xs font-semibold text-gray-950 dark:text-white">
                            Status
                        </span>
                    </span>
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
            @forelse($subtasks as $subtask)
                <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="px-3 py-4">
                            <a href="{{ \App\Filament\Resources\TaskResource::getUrl('view', ['record' => $subtask]) }}" 
                               class="fi-ta-text-item-label group/item flex flex-col gap-y-1">
                                <span class="fi-ta-text-item-label-primary font-semibold text-gray-950 dark:text-white group-hover/item:text-primary-600 dark:group-hover/item:text-primary-400">
                                    {{ $subtask->title }}
                                </span>
                            </a>
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="px-3 py-4">
                            @php
                                $title = $subtask->title;
                                $subtaskName = strpos($title, ' - ') !== false 
                                    ? trim(explode(' - ', $title)[0])
                                    : trim($title);
                                
                                $badgeColor = match($subtaskName) {
                                    'Add Products' => 'warning',
                                    'Size Grade or Thread Colors Needed' => 'success',
                                    'Website Images' => 'purple',
                                    default => 'gray',
                                };
                                
                                // Filament badge color classes
                                $badgeClasses = match($badgeColor) {
                                    'warning' => 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30',
                                    'success' => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30',
                                    'purple' => 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-400/10 dark:text-purple-400 dark:ring-purple-400/30',
                                    'danger' => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30',
                                    default => 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/30',
                                };
                            @endphp
                            <span class="fi-badge inline-flex items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $badgeClasses }}">
                                {{ $subtaskName }}
                            </span>
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="px-3 py-4">
                            @if($subtask->assignedUser)
                                <a href="#" class="fi-ta-text-item-label group/item flex flex-col gap-y-1">
                                    <span class="fi-ta-text-item-label-primary font-semibold text-primary-600 dark:text-primary-400">
                                        {{ $subtask->assignedUser->name }}
                                    </span>
                                </a>
                            @else
                                <span class="text-sm text-gray-500 dark:text-gray-400">Unassigned</span>
                            @endif
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="px-3 py-4">
                            @if($subtask->due_date)
                                <span class="text-sm text-gray-950 dark:text-white">
                                    {{ \Carbon\Carbon::parse($subtask->due_date)->format('M d, Y') }}
                                </span>
                            @else
                                <span class="text-sm text-gray-500 dark:text-gray-400">—</span>
                            @endif
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="px-3 py-4">
                            <button
                                wire:click="toggleSubtaskComplete({{ $subtask->id }})"
                                wire:confirm="{{ $subtask->is_completed ? 'Are you sure you want to mark this subtask as incomplete?' : 'Are you sure you want to mark this subtask as complete?' }}"
                                class="fi-icon-btn relative flex items-center justify-center rounded-lg text-gray-400 outline-none transition duration-75 hover:text-gray-500 focus-visible:bg-gray-50 dark:text-gray-500 dark:hover:text-gray-400 dark:focus-visible:bg-white/5"
                                title="{{ $subtask->is_completed ? 'Mark as incomplete' : 'Mark as complete' }}"
                            >
                                @if($subtask->is_completed)
                                    <svg class="fi-icon-btn-icon h-5 w-5 text-success-600 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @else
                                    <svg class="fi-icon-btn-icon h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @endif
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            No subtasks for this task.
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>


@endphp

<div class="fi-ta-content overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
        <thead class="divide-y divide-gray-200 dark:divide-white/5">
            <tr class="bg-gray-50 dark:bg-white/5">
                <th class="px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                    <span class="group flex w-full items-center gap-x-1.5">
                        <span class="fi-ta-header-cell-label text-xs font-semibold text-gray-950 dark:text-white">
                            Task Title
                        </span>
                    </span>
                </th>
                <th class="px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                    <span class="group flex w-full items-center gap-x-1.5">
                        <span class="fi-ta-header-cell-label text-xs font-semibold text-gray-950 dark:text-white">
                            Task Type
                        </span>
                    </span>
                </th>
                <th class="px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                    <span class="group flex w-full items-center gap-x-1.5">
                        <span class="fi-ta-header-cell-label text-xs font-semibold text-gray-950 dark:text-white">
                            Assigned To
                        </span>
                    </span>
                </th>
                <th class="px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                    <span class="group flex w-full items-center gap-x-1.5">
                        <span class="fi-ta-header-cell-label text-xs font-semibold text-gray-950 dark:text-white">
                            Due Date
                        </span>
                    </span>
                </th>
                <th class="px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                    <span class="group flex w-full items-center gap-x-1.5">
                        <span class="fi-ta-header-cell-label text-xs font-semibold text-gray-950 dark:text-white">
                            Status
                        </span>
                    </span>
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
            @forelse($subtasks as $subtask)
                <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="px-3 py-4">
                            <a href="{{ \App\Filament\Resources\TaskResource::getUrl('view', ['record' => $subtask]) }}" 
                               class="fi-ta-text-item-label group/item flex flex-col gap-y-1">
                                <span class="fi-ta-text-item-label-primary font-semibold text-gray-950 dark:text-white group-hover/item:text-primary-600 dark:group-hover/item:text-primary-400">
                                    {{ $subtask->title }}
                                </span>
                            </a>
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="px-3 py-4">
                            @php
                                $title = $subtask->title;
                                $subtaskName = strpos($title, ' - ') !== false 
                                    ? trim(explode(' - ', $title)[0])
                                    : trim($title);
                                
                                $badgeColor = match($subtaskName) {
                                    'Add Products' => 'warning',
                                    'Size Grade or Thread Colors Needed' => 'success',
                                    'Website Images' => 'purple',
                                    default => 'gray',
                                };
                                
                                // Filament badge color classes
                                $badgeClasses = match($badgeColor) {
                                    'warning' => 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30',
                                    'success' => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30',
                                    'purple' => 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-400/10 dark:text-purple-400 dark:ring-purple-400/30',
                                    'danger' => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30',
                                    default => 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/30',
                                };
                            @endphp
                            <span class="fi-badge inline-flex items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $badgeClasses }}">
                                {{ $subtaskName }}
                            </span>
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="px-3 py-4">
                            @if($subtask->assignedUser)
                                <a href="#" class="fi-ta-text-item-label group/item flex flex-col gap-y-1">
                                    <span class="fi-ta-text-item-label-primary font-semibold text-primary-600 dark:text-primary-400">
                                        {{ $subtask->assignedUser->name }}
                                    </span>
                                </a>
                            @else
                                <span class="text-sm text-gray-500 dark:text-gray-400">Unassigned</span>
                            @endif
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="px-3 py-4">
                            @if($subtask->due_date)
                                <span class="text-sm text-gray-950 dark:text-white">
                                    {{ \Carbon\Carbon::parse($subtask->due_date)->format('M d, Y') }}
                                </span>
                            @else
                                <span class="text-sm text-gray-500 dark:text-gray-400">—</span>
                            @endif
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="px-3 py-4">
                            <button
                                wire:click="toggleSubtaskComplete({{ $subtask->id }})"
                                wire:confirm="{{ $subtask->is_completed ? 'Are you sure you want to mark this subtask as incomplete?' : 'Are you sure you want to mark this subtask as complete?' }}"
                                class="fi-icon-btn relative flex items-center justify-center rounded-lg text-gray-400 outline-none transition duration-75 hover:text-gray-500 focus-visible:bg-gray-50 dark:text-gray-500 dark:hover:text-gray-400 dark:focus-visible:bg-white/5"
                                title="{{ $subtask->is_completed ? 'Mark as incomplete' : 'Mark as complete' }}"
                            >
                                @if($subtask->is_completed)
                                    <svg class="fi-icon-btn-icon h-5 w-5 text-success-600 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @else
                                    <svg class="fi-icon-btn-icon h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @endif
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            No subtasks for this task.
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

