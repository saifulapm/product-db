@php
    use App\Models\Task;
    $tasks = Task::whereNull('parent_task_id')->with('subtasks')->get();
@endphp

<div>
<script>
    document.addEventListener('alpine:init', () => {
        // Initialize Alpine stores for each task with subtasks
        @foreach($tasks as $task)
            @if($task->subtasks()->count() > 0)
                if (!Alpine.store('subtasks{{ $task->id }}')) {
                    Alpine.store('subtasks{{ $task->id }}', {
                        expanded: false
                    });
                }
            @endif
        @endforeach
    });

    // Function to render subtasks dynamically after main task row
    function renderSubtasks(taskId, subtasks) {
        const taskRow = document.querySelector(`tr[data-task-id="${taskId}"]`);
        if (!taskRow) return;

        // Remove existing subtask rows for this task
        const existingSubtasks = document.querySelectorAll(`tr[data-parent-task-id="${taskId}"]`);
        existingSubtasks.forEach(row => row.remove());

        // Create and insert subtask rows
        subtasks.forEach((subtask, index) => {
            const row = document.createElement('tr');
            row.setAttribute('data-parent-task-id', taskId);
            row.className = 'bg-white hover:bg-gray-50 border-l-4 border-blue-300';
            row.setAttribute('x-show', `$store.subtasks${taskId}.expanded`);
            row.style.display = 'none';

            const viewUrl = `/admin/tasks/${subtask.id}`;
            const dueDate = subtask.due_date ? new Date(subtask.due_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '-';
            const assignedTo = subtask.assigned_user && subtask.assigned_user.name ? subtask.assigned_user.name : 'Unassigned';
            const status = subtask.is_completed ? 'Completed' : 'Incomplete';
            const statusClass = subtask.is_completed ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';

            row.innerHTML = `
                <td class="px-4 py-3">
                    <div class="flex items-center pl-8">
                        <span class="text-gray-500 mr-2 text-sm">└─</span>
                        <a href="/admin/tasks/${subtask.id}" class="text-sm font-medium text-blue-600 hover:text-blue-800 underline">${subtask.title}</a>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <span class="text-sm text-gray-600">${assignedTo}</span>
                </td>
                <td class="px-4 py-3">
                    <span class="text-sm text-gray-600">${dueDate}</span>
                </td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-1 rounded ${statusClass}">${status}</span>
                </td>
                <td class="px-4 py-3"></td>
                <td class="px-4 py-3"></td>
            `;

            taskRow.insertAdjacentElement('afterend', row);
        });
    }

    // Initialize after page load and Alpine is ready
    function initializeSubtasks() {
        // Add data-task-id to rows and apply blue background
        document.querySelectorAll('td[data-task-id]').forEach(cell => {
            const row = cell.closest('tr');
            if (row) {
                const taskId = cell.getAttribute('data-task-id');
                row.setAttribute('data-task-id', taskId);
                row.classList.add('bg-blue-50');
                row.classList.add('hover:bg-blue-100');
            }
        });

        @foreach($tasks as $task)
            @if($task->subtasks()->count() > 0)
                @php
                    $subtasksData = $task->subtasks->map(function($subtask) {
                        return [
                            'id' => $subtask->id,
                            'title' => $subtask->title,
                            'due_date' => $subtask->due_date ? $subtask->due_date->format('Y-m-d') : null,
                            'assigned_user' => $subtask->assignedUser ? ['name' => $subtask->assignedUser->name] : null,
                            'is_completed' => $subtask->is_completed,
                        ];
                    })->toArray();
                @endphp
                // Ensure Alpine store exists
                if (!Alpine.store('subtasks{{ $task->id }}')) {
                    Alpine.store('subtasks{{ $task->id }}', { expanded: false });
                }
                
                // Render subtasks immediately
                const subtasks{{ $task->id }} = @json($subtasksData);
                renderSubtasks({{ $task->id }}, subtasks{{ $task->id }});
            @endif
        @endforeach
    }

    // Wait for both DOM and Alpine to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            if (window.Alpine) {
                initializeSubtasks();
            } else {
                document.addEventListener('alpine:init', () => {
                    setTimeout(initializeSubtasks, 100);
                });
            }
        });
    } else {
        if (window.Alpine) {
            initializeSubtasks();
        } else {
            document.addEventListener('alpine:init', () => {
                setTimeout(initializeSubtasks, 100);
            });
        }
    }
</script>

<style>
    /* Style main task rows with blue background */
    table tbody tr[data-task-id] {
        background-color: #eff6ff !important;
    }
    table tbody tr[data-task-id]:hover {
        background-color: #dbeafe !important;
    }
</style>
</div>
