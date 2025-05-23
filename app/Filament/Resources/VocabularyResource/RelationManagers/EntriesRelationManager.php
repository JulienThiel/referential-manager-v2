<?php

namespace App\Filament\Resources\VocabularyResource\RelationManagers;

use App\Models\VocabularyEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms\Form;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class VocabularyEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'entries';
    protected static ?string $recordTitleAttribute = 'entry_value';

    /**
     * Formulaire de création / édition des entries
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Labels multilingues
                Repeater::make('entry_labels')
                    ->label('Labels multilingues')
                    ->schema([
                        Select::make('locale')
                            ->options([
                                'fr-BE' => 'Français (BE)',
                                'de-BE' => 'Néerlandais (BE)',
                                'de'    => 'Allemand',
                            ])
                            ->required(),
                        TextInput::make('label')
                            ->required(),
                    ])
                    ->columns(2)
                    ->reactive(),

                // Parent dans le même vocabulaire
                Select::make('parent_id')
                    ->label('Parent')
                    ->options(fn ($livewire): array =>
                        VocabularyEntry::where('vocabulary_id', $livewire->ownerRecord->id)
                            ->pluck('entry_value', 'id')
                            ->toArray()
                    )
                    ->nullable(),

                // Affichage du slug en lecture seule lors de l'édition
                TextInput::make('entry_value')
                    ->label('Slug')
                    ->disabled()
                    ->visible(fn ($livewire): bool => isset($livewire->record)),

                // Ordre
                TextInput::make('rank')
                    ->label('Ordre')
                    ->numeric()
                    ->default(0),
            ]);
    }

    /**
     * Génère le slug hiérarchique lors de la création
     * @param array $data
     * @param VocabularyEntry|null $record
     * @return array
     */
    protected function generateSlug(array $data, VocabularyEntry $record = null): array
    {
        // Transforme Repeater en tableau associatif locale => label
        $labels = [];
        foreach ($data['entry_labels'] as $item) {
            $labels[$item['locale']] = $item['label'];
        }
        ksort($labels);
        $data['entry_labels'] = $labels;

        // Niveau 1 : vocabulaire
        $vocabSlug = Str::upper(Str::slug($this->ownerRecord->name, '_'));
        // Niveau 2 : parent entry
        $parentSlug = '';
        if (!empty($data['parent_id'])) {
            $p = VocabularyEntry::find($data['parent_id']);
            $parentSlug = $p ? Str::upper(Str::slug($p->entry_value, '_')) . '_' : '';
        }
        // Niveau 3 : entry label
        $first = array_values($labels)[0] ?? '';
        $entrySlug = Str::upper(Str::slug($first, '_'));

        $data['entry_value'] = implode('_', array_filter([$vocabSlug, rtrim($parentSlug, '_'), $entrySlug]));

        return $data;
    }

    /**
     * Configuration de la table et actions
     */
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rank')
                    ->label('Ordre')
                    ->sortable(),
                TextColumn::make('entry_labels')
                    ->label('Libellé')
                    ->formatStateUsing(fn (VocabularyEntry $entry) =>
                        str_repeat('— ', $entry->depth)
                        . ($entry->entry_labels['fr-BE']
                            ?? ($entry->entry_labels['de-BE']
                                ?? ($entry->entry_labels['de']
                                    ?? '[sans label]')
                            )
                        )
                    )
                    ->sortable(),
                TextColumn::make('entry_value')->label('Slug'),
            ])
            ->defaultSort('rank')
            ->reorderable('rank')
            ->headerActions([
                // Création : génère le slug hiérarchique
                CreateAction::make()
                    ->mutateFormDataUsing(fn (array $data): array => $this->generateSlug($data)),
            ])
            ->actions([
                // Édition : pré-remplit et conserve le slug
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, VocabularyEntry $record): array {
                        // Pré-remplissage du Repeater pour l'affichage
                        $items = [];
                        foreach ($record->entry_labels as $locale => $label) {
                            $items[] = ['locale' => $locale, 'label' => $label];
                        }
                        $data['entry_labels'] = $items;
                        return $data;
                    })
                    ->mutateFormDataUsing(function (array $data, VocabularyEntry $record = null): array {
                        // Conserve le slug existant
                        if ($record !== null) {
                            $data['entry_value'] = $record->entry_value;
                        }
                        // Transforme le Repeater en tableau associatif
                        $labelsAssoc = [];
                        foreach ($data['entry_labels'] as $item) {
                            $labelsAssoc[$item['locale']] = $item['label'];
                        }
                        $data['entry_labels'] = $labelsAssoc;
                        return $data;
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}