<?php

namespace App\Filament\Resources\VocabularyResource\Pages;

use App\Filament\Resources\VocabularyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVocabularies extends ListRecords
{
    protected static string $resource = VocabularyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
