<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleMentionNotificationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some users
        $users = User::where('is_active', true)->take(5)->get();
        
        if ($users->count() < 2) {
            $this->command->warn('Need at least 2 active users to create mention notifications.');
            return;
        }

        $this->command->info('Creating sample task comments with mentions...');

        // Get some existing tasks (or create one if none exist)
        $tasks = Task::with('parentTask')->take(5)->get();
        
        if ($tasks->isEmpty()) {
            $this->command->warn('No tasks found. Please create tasks first or run SampleTaskNotificationsSeeder.');
            return;
        }

        $commenter = $users->first();
        $mentionedUsers = $users->skip(1)->take(3);

        $sampleComments = [
            [
                'comment' => "Hey @{$mentionedUsers->first()->name}, can you review this task? I need your input on the design details.",
                'task' => $tasks->first(),
            ],
            [
                'comment' => "@{$mentionedUsers->skip(1)->first()->name} and @{$mentionedUsers->skip(2)->first()->name}, we need to discuss the timeline for this project. Can you both check this out?",
                'task' => $tasks->skip(1)->first() ?? $tasks->first(),
            ],
            [
                'comment' => "Quick update: @{$mentionedUsers->first()->name}, the mockups are ready for your approval. Please take a look when you have a chance!",
                'task' => $tasks->skip(2)->first() ?? $tasks->first(),
            ],
        ];

        $createdCount = 0;

        foreach ($sampleComments as $index => $commentData) {
            $task = $commentData['task'];
            
            // Determine which users to tag based on the comment
            $taggedUserIds = [];
            if ($index === 0) {
                // First comment: tag first mentioned user
                $taggedUserIds = [$mentionedUsers->first()->id];
            } elseif ($index === 1) {
                // Second comment: tag two users
                $taggedUserIds = [
                    $mentionedUsers->skip(1)->first()->id,
                    $mentionedUsers->skip(2)->first()->id,
                ];
            } else {
                // Third comment: tag first mentioned user
                $taggedUserIds = [$mentionedUsers->first()->id];
            }

            // Create the comment
            $comment = TaskComment::create([
                'task_id' => $task->id,
                'user_id' => $commenter->id,
                'comment' => $commentData['comment'],
                'tagged_users' => $taggedUserIds,
            ]);

            $createdCount++;
            
            $taggedUserNames = User::whereIn('id', $taggedUserIds)->pluck('name')->join(', ');
            $taskTitle = $task->parent_task_id ? "Subtask: {$task->title}" : $task->title;
            
            $this->command->info("Created comment on task: {$taskTitle}");
            $this->command->info("  → Commented by: {$commenter->name}");
            $this->command->info("  → Mentioned: {$taggedUserNames}");
        }

        $this->command->info("\nTotal created:");
        $this->command->info("- Comments with mentions: {$createdCount}");
        $this->command->info("\nMention notifications have been automatically sent to tagged users!");
        
        // Also show what other notifications were sent
        $this->command->info("\nNote: Regular comment notifications were also sent to:");
        $this->command->info("- Task assigned users (if different from commenter)");
        $this->command->info("- Task creators (if different from commenter)");
    }
}

