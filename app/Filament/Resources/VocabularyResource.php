<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VocabularyResource\Pages;
use App\Filament\Resources\VocabularyResource\RelationManagers\VocabularyEntriesRelationManager;
use App\Models\Vocabulary;
use Filament\Forms\Components\MultiSelect;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteBulkAction;

class VocabularyResource extends Resource
{
    // Modèle associé
    protected static ?string $model = Vocabulary::class;

    // Icône utilisée dans la navigation
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // Groupe de navigation
    protected static ?string $navigationGroup = 'Dictionnaires';

    /**
     * Formulaire de création / édition de vocabulaire
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nom du dictionnaire')
                    ->required()
                    ->maxLength(255),

                MultiSelect::make('products')
                    ->label('Produits')
                    ->relationship('products', 'label')
                    ->helperText('Associez un ou plusieurs produits à ce vocabulaire'),
            ]);
    }

    /**
     * Tableau listant les vocabulaires
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('products_count')
                    ->label('# Produits')
                    ->counts('products')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    /**
     * Relation Managers (onglet Entrées)
     */
    public static function getRelations(): array
    {
        return [
            VocabularyEntriesRelationManager::class,
        ];
    }

    /**
     * Pages gérées par la ressource
     */
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVocabularies::route('/'),
            'create' => Pages\CreateVocabulary::route('/create'),
            'edit'   => Pages\EditVocabulary::route('/{record}/edit'),
        ];
    }
}
