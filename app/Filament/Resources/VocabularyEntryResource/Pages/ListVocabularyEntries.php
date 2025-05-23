<?php

namespace App\Filament\Resources\VocabularyEntryResource\Pages;

use App\Filament\Resources\VocabularyEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVocabularyEntries extends ListRecords
{
    protected static string $resource = VocabularyEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
