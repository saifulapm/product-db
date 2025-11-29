<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleTaskNotificationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some users to assign tasks to
        $users = User::where('is_active', true)->take(5)->get();
        
        if ($users->isEmpty()) {
            $this->command->warn('No active users found. Please create users first.');
            return;
        }

        // Get or create a project
        $project = Project::first();
        if (!$project) {
            $project = Project::create([
                'name' => 'Sample Project',
                'description' => 'Sample project for testing notifications',
            ]);
        }

        $this->command->info('Creating sample tasks with subtasks...');

        // Create some main tasks with subtasks
        $mainTasks = [
            [
                'title' => 'Website Images - Lagree Versa Fit',
                'description' => 'Collect and upload website images for Lagree Versa Fit product',
                'task_type' => 'Product Additions',
                'assigned_to' => $users->random()->id,
                'created_by' => $users->first()->id,
                'due_date' => now()->addDays(5),
                'subtasks' => [
                    [
                        'title' => 'Size Grade or Thread Colors Needed',
                        'description' => 'Determine size grade and thread colors for the product',
                        'assigned_to' => $users->random()->id,
                        'due_date' => now()->addDays(2),
                    ],
                    [
                        'title' => 'Add Products to Database',
                        'description' => 'Add all product variants to the database',
                        'assigned_to' => $users->random()->id,
                        'due_date' => now()->addDays(3),
                    ],
                ],
            ],
            [
                'title' => 'Mockups for New Collection',
                'description' => 'Create mockups for the new spring collection',
                'task_type' => 'Mockups',
                'assigned_to' => $users->random()->id,
                'created_by' => $users->first()->id,
                'due_date' => now()->addDays(7),
                'subtasks' => [
                    [
                        'title' => 'Design Graphics',
                        'description' => 'Design custom graphics for mockups',
                        'assigned_to' => $users->random()->id,
                        'due_date' => now()->addDays(4),
                    ],
                    [
                        'title' => 'Customer Approval',
                        'description' => 'Get customer approval on mockup designs',
                        'assigned_to' => $users->random()->id,
                        'due_date' => now()->addDays(6),
                    ],
                ],
            ],
            [
                'title' => 'Product Database Update',
                'description' => 'Update product database with new inventory',
                'task_type' => 'Data',
                'assigned_to' => $users->random()->id,
                'created_by' => $users->first()->id,
                'due_date' => now()->addDays(3),
                'subtasks' => [
                    [
                        'title' => 'Verify Product Information',
                        'description' => 'Verify all product details are correct',
                        'assigned_to' => $users->random()->id,
                        'due_date' => now()->addDays(1),
                    ],
                    [
                        'title' => 'Update Pricing',
                        'description' => 'Update product pricing information',
                        'assigned_to' => $users->random()->id,
                        'due_date' => now()->addDays(2),
                    ],
                ],
            ],
        ];

        $createdCount = 0;
        $subtaskCount = 0;

        foreach ($mainTasks as $taskData) {
            $subtasks = $taskData['subtasks'];
            unset($taskData['subtasks']);

            // Create main task
            $mainTask = Task::create([
                'title' => $taskData['title'],
                'description' => $taskData['description'],
                'task_type' => $taskData['task_type'],
                'project_id' => $project->id,
                'assigned_to' => $taskData['assigned_to'],
                'created_by' => $taskData['created_by'],
                'due_date' => $taskData['due_date'],
            ]);

            $createdCount++;
            $this->command->info("Created main task: {$mainTask->title} (assigned to user ID: {$mainTask->assigned_to})");

            // Create subtasks
            foreach ($subtasks as $subtaskData) {
                $subtask = Task::create([
                    'title' => $subtaskData['title'],
                    'description' => $subtaskData['description'],
                    'task_type' => $taskData['task_type'],
                    'project_id' => $project->id,
                    'parent_task_id' => $mainTask->id,
                    'assigned_to' => $subtaskData['assigned_to'],
                    'created_by' => $taskData['created_by'],
                    'due_date' => $subtaskData['due_date'],
                ]);

                $subtaskCount++;
                $assignedUser = User::find($subtask->assigned_to);
                $this->command->info("  → Created subtask: {$subtask->title} (assigned to: {$assignedUser->email})");
            }
        }

        $this->command->info("\nTotal created:");
        $this->command->info("- Main tasks: {$createdCount}");
        $this->command->info("- Subtasks: {$subtaskCount}");
        $this->command->info("\nNotifications have been automatically sent to assigned users!");
    }
}

