<?php

namespace App\Filament\Resources\FaqResource\Pages;

use App\Filament\Resources\FaqResource;
use App\Models\Faq;
use Filament\Resources\Pages\ManageRecords;

class ManageFaqs extends ManageRecords
{
    protected static string $resource = FaqResource::class;

    protected static string $view = 'filament.resources.faq-resource.pages.manage-faqs';
    
    public function getFaqs()
    {
        return Faq::ordered()->get();
    }
}










