<?php

namespace App\Filament\Resources\FaqResource\Pages;

use App\Filament\Resources\FaqResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewFaq extends ViewRecord
{
    protected static string $resource = FaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Question')
                    ->schema([
                        Infolists\Components\TextEntry::make('question')
                            ->size('lg')
                            ->weight('bold'),
                    ]),
                Infolists\Components\Section::make('Solutions')
                    ->schema([
                        Infolists\Components\TextEntry::make('solutions')
                            ->label('')
                            ->formatStateUsing(function ($state) {
                                if (empty($state) || !is_array($state)) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-gray-500 italic">No solutions available.</p>');
                                }
                                
                                $html = '<div class="space-y-4">';
                                foreach ($state as $index => $solutionItem) {
                                    $html .= '<div class="bg-gray-50 rounded-lg p-4 border border-gray-200">';
                                    $html .= '<div class="pl-4 border-l-4 border-blue-600">';
                                    
                                    if (!empty($solutionItem['title'])) {
                                        $html .= '<div class="mb-2">';
                                        $html .= '<span class="text-sm font-semibold text-blue-600">' . htmlspecialchars($solutionItem['title']) . '</span>';
                                        $html .= '</div>';
                                    }
                                    
                                    $solution = $solutionItem['solution'] ?? '';
                                    $html .= '<div class="text-base text-gray-900 leading-relaxed whitespace-pre-wrap">' . nl2br(htmlspecialchars($solution)) . '</div>';
                                    $html .= '</div>';
                                    $html .= '</div>';
                                }
                                $html .= '</div>';
                                
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

