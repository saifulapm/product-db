<?php

namespace Database\Seeders;

use App\Models\User;
use App\Notifications\FeatureAnnouncementNotification;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class SampleNotificationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all active users
        $users = User::where('is_active', true)->get();
        
        if ($users->isEmpty()) {
            $this->command->warn('No active users found. Please create a user first.');
            return;
        }

        $this->command->info('Creating sample notifications for ' . $users->count() . ' user(s)...');

        // Sample notifications with different types and statuses
        $sampleNotifications = [
            [
                'title' => 'New Feature: Enhanced Dashboard',
                'message' => 'We\'ve added new widgets and improved the dashboard layout for better productivity. Check it out!',
                'type' => 'feature',
                'read_at' => null, // Unread
            ],
            [
                'title' => 'Task Overdue: Product Images Needed',
                'message' => 'The task "Website Images - Lagree Versa Fit" is now overdue. Please complete it as soon as possible.',
                'type' => 'task',
                'read_at' => null, // Unread
            ],
            [
                'title' => 'Order Status Update',
                'message' => 'Order #12345 has been shipped and is on its way to the customer. Expected delivery: 3-5 business days.',
                'type' => 'order',
                'read_at' => now()->subHours(2), // Read 2 hours ago
            ],
            [
                'title' => 'Team Meeting Scheduled',
                'message' => 'Weekly team meeting scheduled for tomorrow at 2:00 PM. Please confirm your attendance.',
                'type' => 'meeting',
                'read_at' => null, // Unread
            ],
            [
                'title' => 'System Maintenance Complete',
                'message' => 'The scheduled system maintenance has been completed successfully. All services are now running normally.',
                'type' => 'system',
                'read_at' => now()->subDays(1), // Read yesterday
            ],
            [
                'title' => 'New Product Added',
                'message' => 'A new product "Lagree Versa Fit" has been added to the database. Review and add product details.',
                'type' => 'product',
                'read_at' => null, // Unread
            ],
            [
                'title' => 'Inventory Alert',
                'message' => 'Low stock alert: Sock Style "Athletic Crew" is below minimum threshold. Consider reordering.',
                'type' => 'inventory',
                'read_at' => now()->subHours(5), // Read 5 hours ago
            ],
            [
                'title' => 'Welcome to the Team!',
                'message' => 'Welcome to Ethos! We\'re excited to have you on board. Check out the dashboard to get started.',
                'type' => 'welcome',
                'read_at' => now()->subDays(3), // Read 3 days ago
            ],
        ];

        $totalCreated = 0;
        $unreadCount = count(array_filter($sampleNotifications, fn($n) => $n['read_at'] === null));

        foreach ($users as $user) {
            foreach ($sampleNotifications as $index => $notificationData) {
                // Create the notification directly in the database
                DatabaseNotification::create([
                    'id' => \Illuminate\Support\Str::uuid()->toString(),
                    'type' => FeatureAnnouncementNotification::class,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'data' => [
                        'title' => $notificationData['title'],
                        'message' => $notificationData['message'],
                        'body' => $notificationData['message'],
                        'type' => $notificationData['type'],
                        'format' => 'filament',
                        'status' => $this->getStatusForType($notificationData['type']),
                        'icon' => $this->getIconForType($notificationData['type']),
                        'iconColor' => $this->getIconColorForType($notificationData['type']),
                        'actions' => [],
                    ],
                    'read_at' => $notificationData['read_at'],
                    'created_at' => now()->subDays($index), // Stagger creation dates
                    'updated_at' => now()->subDays($index),
                ]);
                $totalCreated++;
            }
            $this->command->info("Created " . count($sampleNotifications) . " notifications for {$user->email}");
        }

        $this->command->info("Total: Created {$totalCreated} sample notifications ({$unreadCount} unread per user).");
    }

    private function getStatusForType(string $type): string
    {
        return match($type) {
            'task', 'inventory' => 'warning',
            'order' => 'success',
            'system' => 'info',
            'feature', 'welcome' => 'primary',
            default => 'info',
        };
    }

    private function getIconForType(string $type): string
    {
        return match($type) {
            'task' => 'heroicon-o-clipboard-document-check',
            'order' => 'heroicon-o-truck',
            'meeting' => 'heroicon-o-calendar',
            'system' => 'heroicon-o-cog-6-tooth',
            'product' => 'heroicon-o-cube',
            'inventory' => 'heroicon-o-exclamation-triangle',
            'welcome' => 'heroicon-o-hand-raised',
            default => 'heroicon-o-bell',
        };
    }

    private function getIconColorForType(string $type): string
    {
        return match($type) {
            'task', 'inventory' => 'warning',
            'order' => 'success',
            'system' => 'info',
            'feature', 'welcome' => 'primary',
            default => 'gray',
        };
    }
}

