<div class="space-y-2">
    @foreach($tasks as $task)
        @php
            $isSubtask = !empty($task->parent_task_id);
            $isCompleted = $task->is_completed ?? false;
            $assignedUserName = $task->assignedUser->name ?? null;
            
            // Get task type tag name and color (matching TaskResource logic)
            $title = $task->title;
            $subtaskName = strpos($title, ' - ') !== false 
                ? trim(explode(' - ', $title)[0])
                : trim($title);
            
            $tagColor = match($subtaskName) {
                'Add Products' => 'warning', // orange/amber
                'Size Grade or Thread Colors Needed' => 'success', // green
                'Website Images' => 'purple', // purple
                default => 'success',
            };
            
            $tagBgColor = match($subtaskName) {
                'Add Products' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                'Size Grade or Thread Colors Needed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                'Website Images' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
                default => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            };
        @endphp
        <a 
            href="{{ $this->getViewTaskUrl($task) }}"
            class="block text-xs p-2 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow"
            title="{{ $task->title }}"
        >
            <div class="mb-1">
                <span class="inline-block px-2 py-0.5 rounded text-xs font-medium {{ $tagBgColor }}" 
                    @if($subtaskName === 'Add Products') style="background-color: #fed7aa !important; color: #9a3412 !important;" @endif>
                    {{ $subtaskName }}
                </span>
            </div>
            @if($assignedUserName)
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                    {{ $assignedUserName }}
                </div>
            @endif
        </a>
    @endforeach
    @if($tasks->isEmpty())
        <div class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">
            No tasks scheduled
        </div>
    @endif
</div>

