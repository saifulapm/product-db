@php
    $subtasks = $record->subtasks;
    $hasSubtasks = $subtasks->count() > 0;
    $taskId = $record->id;
@endphp

<tr 
    x-data="{ expanded_{{ $taskId }}: false }"
    class="bg-blue-50 hover:bg-blue-100 transition-colors"
>
    {{ $slot }}
</tr>

@if($hasSubtasks)
    <template x-if="expanded_{{ $taskId }}">
        @foreach($subtasks as $subtask)
            <tr class="bg-white hover:bg-gray-50 border-l-4 border-blue-300">
                <td class="px-4 py-3">
                    <div class="flex items-center pl-8">
                        <span class="text-gray-500 mr-2">└─</span>
                        <span class="text-sm">{{ $subtask->title }}</span>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <span class="text-sm text-gray-600">{{ $subtask->assignedUser->name ?? 'Unassigned' }}</span>
                </td>
                <td class="px-4 py-3">
                    <span class="text-sm text-gray-600">{{ $subtask->due_date ? $subtask->due_date->format('M d, Y') : '-' }}</span>
                </td>
                <td class="px-4 py-3">
                    <span class="text-sm">
                        <span class="px-2 py-1 rounded text-xs {{ $subtask->is_completed ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $subtask->is_completed ? 'Completed' : 'Incomplete' }}
                        </span>
                    </span>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <button 
                            type="button"
                            wire:click="$dispatch('open-modal', { id: 'view-task-{{ $subtask->id }}' })"
                            class="text-blue-600 hover:text-blue-800 text-sm"
                        >
                            View
                        </button>
                    </div>
                </td>
            </tr>
        @endforeach
    </template>
@endif

