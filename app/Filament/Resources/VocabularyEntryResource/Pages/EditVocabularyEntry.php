<?php

namespace App\Filament\Resources\VocabularyEntryResource\Pages;

use App\Filament\Resources\VocabularyEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVocabularyEntry extends EditRecord
{
    protected static string $resource = VocabularyEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
