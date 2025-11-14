<?php

namespace App\Filament\Resources\PatchResource\Widgets;

use App\Models\TeamNote;
use Filament\Widgets\Widget;
use Filament\Forms\Components\RichEditor;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Support\Contracts\TranslatableContentDriver;
use Illuminate\Support\Facades\Auth;

class PatchesHeader extends Widget implements HasActions
{
    use InteractsWithActions;

    protected static string $view = 'filament.resources.patch-resource.widgets.patches-header';

    protected int | string | array $columnSpan = 'full';

    public $content = '';
    public $isEditable = false;
    public bool $hasFormsModalRendered = false;
    public bool $hasInfolistsModalRendered = false;
    public ?array $mountedFormComponentActions = [];

    public function mount(): void
    {
        // Check if user is super admin - check for 'super-admin' role
        $user = Auth::user();
        
        // TEMPORARY: Allow all logged-in users to edit for testing
        $this->isEditable = $user !== null;
        
        // Get or create the team note for this page
        $teamNote = TeamNote::firstOrCreate(
            ['page' => 'patches'],
            ['content' => '']
        );
        
        // Get content, use empty string if null or invalid
        $content = $teamNote->content ?? '';
        
        // Simple UTF-8 sanitization
        if ($content && !mb_check_encoding($content, 'UTF-8')) {
            $content = '';
        }
        
        $this->content = $content ?: '';
        
        foreach ($this->getActions() as $action) {
            if ($action instanceof Action) {
                $this->cacheAction($action);
            }
        }
    }

    public function getViewData(): array
    {
        return [];
    }

    public function editNotes(): Action
    {
        return Action::make('edit_notes')
            ->label('Edit Notes')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->form([
                RichEditor::make('content')
                    ->label('Team Notes')
                    ->placeholder('Enter your notes here. You can use HTML tags like <h3>Heading</h3> and <br> for line breaks.')
                    ->helperText('You can use HTML tags like <h3>, <h2>, <br>, <p>, <strong>, <em>, etc.')
                    ->toolbarButtons([
                        'attachFiles',
                        'blockquote',
                        'bold',
                        'bulletList',
                        'codeBlock',
                        'h2',
                        'h3',
                        'italic',
                        'link',
                        'orderedList',
                        'redo',
                        'strike',
                        'underline',
                        'undo',
                    ])
                    ->default(fn () => $this->content),
            ])
            ->action(function (array $data): void {
                $teamNote = TeamNote::firstOrNew(['page' => 'patches']);
                $teamNote->content = $data['content'];
                $teamNote->save();

                Notification::make()
                    ->title('Notes updated successfully!')
                    ->success()
                    ->send();
                
                // Refresh the content
                $this->content = $teamNote->content;
            })
            ->requiresConfirmation(false)
            ->modalHeading('Edit Team Notes')
            ->modalSubmitActionLabel('Save');
    }

    public function getActions(): array
    {
        if (!$this->isEditable) {
            return [];
        }

        return [
            $this->editNotes(),
        ];
    }

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    public function getMountedFormComponentAction() { return null; }
    public function mountedFormComponentActionShouldOpenModal(): bool { return false; }
    public function mountedFormComponentActionHasForm(): bool { return false; }
    public function getMountedFormComponentActionForm() { return null; }
    public function unmountFormComponentAction(bool $shouldCancelParentActions = true, bool $shouldCloseModal = true): void {}
}

