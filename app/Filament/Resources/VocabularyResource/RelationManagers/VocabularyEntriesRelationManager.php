<?php

namespace App\Filament\Resources\VocabularyResource\RelationManagers;

use App\Models\VocabularyEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms\Form;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Table; 
use Illuminate\Support\Str;

// New imports
use Filament\Actions\Action;
// Filament\Notifications\Notification is not used in this file based on the provided code.
// CreateAction is used via full namespace \Filament\Actions\CreateAction.

class VocabularyEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'entries';
    protected static ?string $recordTitleAttribute = 'entry_value';

    // Point to the custom view that will host our Livewire component
    protected static string $view = 'filament.resources.vocabulary-resource.relation-managers.vocabulary-entries-relation-manager-custom-view';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('entry_labels')
                    ->label('Labels multilingues')
                    ->schema([
                        Select::make('locale')
                            ->options([
                                'fr-BE' => 'Français (BE)',
                                'nl-BE' => 'Néerlandais (BE)', // Corrected from 'de-BE' as per consistent correction
                                'de'    => 'Allemand',
                            ])
                            ->required(),
                        TextInput::make('label')
                            ->required(),
                    ])
                    ->columns(2)
                    ->reactive(),
                Select::make('parent_id')
                    ->label('Parent')
                    ->options(fn ($livewire): array =>
                        VocabularyEntry::where('vocabulary_id', $livewire->ownerRecord->id)
                            ->when(isset($livewire->record?->id), fn($query) => $query->where('id', '!=', $livewire->record->id))
                            ->pluck('entry_value', 'id') 
                            ->toArray()
                    )
                    ->nullable(),
                TextInput::make('entry_value')
                    ->label('Slug')
                    ->disabled()
                    ->visible(fn ($livewire): bool => isset($livewire->record)),
                TextInput::make('rank')
                    ->label('Ordre')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        // Return a minimal table; its content won't be displayed due to the custom $view.
        return $table->columns([])->actions([])->bulkActions([]);
    }
    
    // Slug generation logic - keep as it's used by the form's Create/Edit actions
    protected function generateSlug(array $data, VocabularyEntry $record = null): array
    {
        $labels = [];
        if (isset($data['entry_labels'])) { // Ensure entry_labels exists
            foreach ($data['entry_labels'] as $item) {
                if (isset($item['locale']) && isset($item['label'])) { // Ensure keys exist
                    $labels[$item['locale']] = $item['label'];
                }
            }
        }
        ksort($labels); 
        $data['entry_labels'] = $labels;

        $vocabSlug = 'DEFAULT_VOCAB'; // Fallback
        if($this->ownerRecord && $this->ownerRecord->name) {
            $vocabSlug = Str::upper(Str::slug($this->ownerRecord->name, '_'));
        }
        
        $parentSlug = '';
        if (!empty($data['parent_id'])) {
            $p = VocabularyEntry::find($data['parent_id']);
            // Assuming $p->entry_value is a simple name part for the parent, not a full slug
            $parentSlug = $p ? Str::upper(Str::slug($p->entry_value, '_')) . '_' : '';
        }
        
        $firstLabelValue = '';
        if(!empty($labels)){
             $firstLabelValue = array_values($labels)[0] ?? ''; // Get first label value
        }
        $entrySlug = Str::upper(Str::slug($firstLabelValue, '_'));
        
        // Construct the slug
        $finalSlug = implode('_', array_filter([$vocabSlug, rtrim($parentSlug, '_'), $entrySlug]));
        // Clean up any potential double underscores and leading/trailing underscores
        $data['entry_value'] = preg_replace('/_+/', '_', $finalSlug);
        $data['entry_value'] = trim($data['entry_value'], '_');

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            // Standard CreateAction - this will use the form() method defined above.
            \Filament\Actions\CreateAction::make()
                ->mutateFormDataUsing(fn (array $data): array => $this->generateSlug($data)),

            // Custom action to save the order from the Livewire tree component
            Action::make('saveTreeOrder')
                ->label('Save Order')
                ->action(function () {
                    // Emit an event to the child Livewire component to trigger save
                    // The event name 'saveVocabularyOrder' should be listened to in VocabularyEntryTree
                    $this->dispatch('saveVocabularyOrder'); 
                }),
        ];
    }
}
