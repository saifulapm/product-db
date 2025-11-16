@php
    $subtasks = $task->subtasks;
    $hasSubtasks = $subtasks->count() > 0;
    $taskId = $task->id;
@endphp

@if($hasSubtasks)
    @foreach($subtasks as $subtask)
        <tr 
            x-data="{ expanded: false }"
            x-show="$store.subtasks{{ $taskId }}.expanded"
            x-init="
                if (!Alpine.store('subtasks{{ $taskId }}')) {
                    Alpine.store('subtasks{{ $taskId }}', { expanded: false });
                }
                $watch('$store.subtasks{{ $taskId }}.expanded', value => expanded = value);
            "
            style="display: none;"
            class="bg-white hover:bg-gray-50 border-l-4 border-blue-300"
            data-parent-task-id="{{ $taskId }}"
        >
            <td class="px-4 py-3">
                <div class="flex items-center pl-8">
                    <span class="text-gray-500 mr-2 text-sm">└─</span>
                    <span class="text-sm font-medium">{{ $subtask->title }}</span>
                </div>
            </td>
            <td class="px-4 py-3">
                @if($subtask->assignedUser)
                    <span class="text-sm text-gray-600 bg-blue-100 px-2 py-1 rounded">{{ $subtask->assignedUser->name }}</span>
                @else
                    <span class="text-sm text-gray-400">Unassigned</span>
                @endif
            </td>
            <td class="px-4 py-3">
                @if($subtask->due_date)
                    <span class="text-sm text-gray-600">{{ $subtask->due_date->format('M d, Y') }}</span>
                @else
                    <span class="text-sm text-gray-400">-</span>
                @endif
            </td>
            <td class="px-4 py-3">
                <span class="text-xs px-2 py-1 rounded {{ $subtask->is_completed ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ $subtask->is_completed ? 'Completed' : 'Incomplete' }}
                </span>
            </td>
            <td class="px-4 py-3"></td>
            <td class="px-4 py-3"></td>
        </tr>
    @endforeach
@endif
