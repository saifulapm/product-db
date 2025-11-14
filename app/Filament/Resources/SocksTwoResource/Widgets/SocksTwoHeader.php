<?php

namespace App\Filament\Resources\SocksTwoResource\Widgets;

use App\Models\TeamNote;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\RichEditor;
use Filament\Notifications\Notification;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class SocksTwoHeader extends Widget implements HasActions
{
    use InteractsWithActions;

    protected static string $view = 'filament.resources.socks-two-resource.widgets.socks-two-header';

    protected int | string | array $columnSpan = 'full';

    public $content = '';
    public $isEditable = false;
    public bool $hasFormsModalRendered = false;
    public bool $hasInfolistsModalRendered = false;
    public ?array $mountedFormComponentActions = [];

    public function mount(): void
    {
        $user = Auth::user();
        $this->isEditable = $user !== null;

        $teamNote = TeamNote::firstOrCreate(
            ['page' => 'socks-2'],
            ['content' => '']
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
                $teamNote = TeamNote::firstOrNew(['page' => 'socks-2']);
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

