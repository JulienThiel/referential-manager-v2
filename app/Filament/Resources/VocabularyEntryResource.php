<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VocabularyEntryResource\Pages;
use App\Models\VocabularyEntry;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;

use Illuminate\Support\Str;

class VocabularyEntryResource extends Resource
{
    protected static ?string $model = VocabularyEntry::class;
    
    // Désactive l'affichage de cette Resource dans la navigation principale
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = null;
    protected static ?string $navigationGroup = null;

    /**
     * Formulaire de création/édition pour VocabularyEntry
     * Note : cette Resource est désormais gérée via RelationManager dans VocabularyResource
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('vocabulary_id')
                    ->relationship('vocabulary', 'name')
                    ->hidden() // géré automatiquement via le RelationManager
                    ->required(),

                Repeater::make('entry_labels')
                    ->label('Labels (locale → texte)')
                    ->schema([
                        Select::make('locale')
                            ->options(['fr' => 'Français', 'en' => 'English'])
                            ->required(),
                        TextInput::make('label')->required(),
                    ])
                    ->defaultItems(1)
                    ->columns(2)
                    ->reactive(),

                Select::make('parent_id')
                    ->label('Parent')
                    ->options(fn (): array => static::getParentOptions())
                    ->nullable(),

                Hidden::make('entry_value'), // slug généré automatiquement

                TextInput::make('rank')
                    ->label('Ordre')
                    ->numeric()
                    ->default(0),
            ]);
    }

    /**
     * Tri et génération du slug avant création
     */
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        return static::generateSlug($data);
    }

    /**
     * Tri et génération du slug avant sauvegarde
     */
    public static function mutateFormDataBeforeSave(array $data, VocabularyEntry $record): array
    {
        return static::generateSlug($data, $record);
    }

    /**
     * Trie les labels et génère entry_value en majuscules, underscores
     */
    protected static function generateSlug(array $data, VocabularyEntry $record = null): array
    {
        if (isset($data['entry_labels'])) {
            ksort($data['entry_labels']);
        }

        $parentSlug = '';
        if ($record?->parent?->entry_value) {
            $parentSlug = $record->parent->entry_value . '_';
        } elseif (!empty($data['parent_id'])) {
            $parent = VocabularyEntry::find($data['parent_id']);
            $parentSlug = $parent?->entry_value . '_';
        }

        $firstLabel = $data['entry_labels'][array_key_first($data['entry_labels'])] ?? '';
        $data['entry_value'] = Str::upper(Str::slug($parentSlug . $firstLabel, '_'));

        return $data;
    }

    /**
     * Configuration de la table
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rank')->label('Ordre')->sortable(),
                TextColumn::make('entry_labels.fr')
                    ->label('Libellé')
                    ->formatStateUsing(fn (VocabularyEntry $entry) =>
                        str_repeat('— ', $entry->depth)
                        . ($entry->entry_labels['fr'] ?? '[sans label]')
                    )
                    ->sortable(),
                TextColumn::make('locales')
                    ->label('Locale')
                    ->formatStateUsing(fn (VocabularyEntry $entry) =>
                        implode(', ', array_keys($entry->entry_labels))
                    ),
                //TextColumn::make('entry_value')->label('Slug'),
            ])
            ->defaultSort('rank')
            ->reorderable('rank')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVocabularyEntries::route('/'),
            'create' => Pages\CreateVocabularyEntry::route('/create'),
            'edit'   => Pages\EditVocabularyEntry::route('/{record}/edit'),
        ];
    }

    /**
     * Options de parent imbriquées (arbre)
     */
    protected static function getParentOptions(): array
    {
        $entries = VocabularyEntry::with('children')->get()->toTree();
        $options = [];
        $traverse = function ($nodes, $prefix = '') use (&$traverse, &$options) {
            foreach ($nodes as $node) {
                $options[$node->id] = $prefix . ($node->entry_labels['fr'] ?? $node->id);
                if ($node->children->isNotEmpty()) {
                    $traverse($node->children, $prefix . '— ');
                }
            }
        };
        $traverse($entries);
        return $options;
    }
}
