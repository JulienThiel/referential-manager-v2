<div>
    {{--
        This view replaces the standard table in the VocabularyEntriesRelationManager.
        It directly embeds the VocabularyEntryTree Livewire component.
        $ownerRecord is available here and is the Vocabulary model instance.
    --}}
    @livewire('vocabulary-entry-tree', ['vocabularyId' => $ownerRecord->id], key($ownerRecord->id))
</div>
