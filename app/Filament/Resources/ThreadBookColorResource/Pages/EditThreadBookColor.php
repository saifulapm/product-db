<?php

namespace App\Filament\Resources\ThreadBookColorResource\Pages;

use App\Filament\Resources\ThreadBookColorResource;
use App\Models\ThreadBookColor;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditThreadBookColor extends EditRecord
{
    protected static string $resource = ThreadBookColorResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        if ($previous = $this->getAdjacentRecord('previous')) {
            $actions[] = Actions\Action::make('previous_thread')
                ->label('Previous Thread')
                ->icon('heroicon-o-chevron-left')
                ->color('gray')
                ->url(ThreadBookColorResource::getUrl('edit', ['record' => $previous]));
        }

        if ($next = $this->getAdjacentRecord('next')) {
            $actions[] = Actions\Action::make('next_thread')
                ->label('Next Thread')
                ->icon('heroicon-o-chevron-right')
                ->color('gray')
                ->url(ThreadBookColorResource::getUrl('edit', ['record' => $next]));
        }

        $actions[] = Actions\DeleteAction::make();

        return $actions;
    }

    protected function getAdjacentRecord(string $direction): ?ThreadBookColor
    {
        $record = $this->record;

        if (!$record instanceof ThreadBookColor) {
            return null;
        }

        $query = ThreadBookColor::query();

        if ($direction === 'previous') {
            $query->where(function ($q) use ($record) {
                $q->where('name', '<', $record->name)
                    ->orWhere(function ($q) use ($record) {
                        $q->where('name', $record->name)
                            ->where('id', '<', $record->id);
                    });
            })
            ->orderBy('name', 'desc')
            ->orderBy('id', 'desc');
        } else {
            $query->where(function ($q) use ($record) {
                $q->where('name', '>', $record->name)
                    ->orWhere(function ($q) use ($record) {
                        $q->where('name', $record->name)
                            ->where('id', '>', $record->id);
                    });
            })
            ->orderBy('name')
            ->orderBy('id');
        }

        return $query->first();
    }
}
