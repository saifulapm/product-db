<?php

namespace App\Filament\Resources\HeadwearResource\Widgets;

use App\Models\TeamNote;
use Filament\Widgets\Widget;
use Filament\Forms\Components\RichEditor;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Facades\Filament;

class HeadwearHeader extends Widget implements HasActions
{
    use InteractsWithActions;

    protected static string $view = 'filament.resources.headwear-resource.widgets.headwear-header';

    protected int | string | array $columnSpan = 'full';

    public $content = '';
    public $isEditable = false;
    public bool $hasFormsModalRendered = false;
    public bool $hasInfolistsModalRendered = false;
    public ?array $mountedFormComponentActions = [];

    public function mount(): void
    {
        $user = Filament::auth()->user();
        $this->isEditable = $user !== null;

        $teamNote = TeamNote::firstOrCreate(
            ['page' => 'headwear'],
            ['content' => '<h3>Headwear Library</h3><p>Use this space to capture sourcing notes, decoration tips, and any approved partners for hats, beanies, and visors.</p>']
        );

        $content = $teamNote->content ?? '';
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
                    ->placeholder('Enter brand guidelines, pricing reminders, or production notes for headwear here.')
                    ->helperText('Supports HTML tags like <h3>, <h2>, <br>, <p>, <strong>, etc.')
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
                $teamNote = TeamNote::firstOrNew(['page' => 'headwear']);
                $teamNote->content = $data['content'];
                $teamNote->save();

                Notification::make()
                    ->title('Notes updated successfully!')
                    ->success()
                    ->send();

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

