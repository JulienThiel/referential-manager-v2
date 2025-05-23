<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

class ProductResource extends Resource
{
    // Le modèle Eloquent géré
    protected static ?string $model = Product::class;

    // Icône affichée dans la sidebar
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    // Regroupe ce menu sous "Référentiels"
    protected static ?string $navigationGroup = 'Référentiels';

    // Libellé personnalisé dans la navigation
    protected static ?string $navigationLabel = 'Produits';

    // Force l’ordre d’affichage : plus petit => plus haut
    protected static ?int $navigationSort = 1;

    /**
     * Formulaire de création / édition d’un produit
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('label')
                    ->label('Libellé du produit')  // texte convivial
                    ->required()                   // champ obligatoire
                    ->maxLength(255),              // longueur max en BDD
            ]);
    }

    /**
     * Table listant les produits
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),               // recherche sur l’UUID
                TextColumn::make('label')
                    ->label('Libellé')
                    ->sortable()                 // tri alphabétique
                    ->searchable(),              // recherche full‐text
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()                 // date & heure
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make(),             // action “éditer”
            ])
            ->bulkActions([
                BulkActionGroup::make([         // groupe d’actions en masse
                    DeleteBulkAction::make(),   // suppression en lot
                ]),
            ]);
    }

    /**
     * Si besoin d’onglets / RelationManagers
     */
    public static function getRelations(): array
    {
        return [
            // Ex. \App\Filament\Resources\ProductResource\RelationManagers\VocabulariesRelationManager::class,
        ];
    }

    /**
     * Les pages CRUD de la ressource
     */
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
