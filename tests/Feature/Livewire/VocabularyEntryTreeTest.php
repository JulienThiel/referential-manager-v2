<?php

namespace Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Vocabulary;
use App\Models\VocabularyEntry;
use App\Livewire\VocabularyEntryTree;
use Livewire\Livewire;

class VocabularyEntryTreeTest extends TestCase
{
    use RefreshDatabase;

    public function testLoadEntries()
    {
        $vocabulary = Vocabulary::create(['name' => 'Fauna', 'slug' => 'fauna']);

        // Root entries
        $entry1 = VocabularyEntry::create([
            'vocabulary_id' => $vocabulary->id, 
            'entry_labels' => ['en' => 'Mammals', 'fr-BE' => 'Mammifères'], 
            'entry_value' => 'FAUNA_MAMMALS', 
            'rank' => 0, 
            'parent_id' => null
        ]);
        $entry2 = VocabularyEntry::create([
            'vocabulary_id' => $vocabulary->id, 
            'entry_labels' => ['en' => 'Reptiles', 'fr-BE' => 'Reptiles'], 
            'entry_value' => 'FAUNA_REPTILES', 
            'rank' => 1, 
            'parent_id' => null
        ]);

        // Children of entry1 (Mammals)
        $entry1_1 = VocabularyEntry::create([
            'vocabulary_id' => $vocabulary->id, 
            'entry_labels' => ['en' => 'Dogs', 'fr-BE' => 'Chiens'], 
            'entry_value' => 'FAUNA_MAMMALS_DOGS', 
            'rank' => 0, 
            'parent_id' => $entry1->id
        ]);
        $entry1_2 = VocabularyEntry::create([
            'vocabulary_id' => $vocabulary->id, 
            'entry_labels' => ['en' => 'Cats', 'fr-BE' => 'Chats'], 
            'entry_value' => 'FAUNA_MAMMALS_CATS', 
            'rank' => 1, 
            'parent_id' => $entry1->id
        ]);
        
        // Child of entry1_1 (Dogs)
        $entry1_1_1 = VocabularyEntry::create([
            'vocabulary_id' => $vocabulary->id,
            'entry_labels' => ['en' => 'Labrador', 'fr-BE' => 'Labrador'],
            'entry_value' => 'FAUNA_MAMMALS_DOGS_LABRADOR',
            'rank' => 0,
            'parent_id' => $entry1_1->id
        ]);


        $livewireComponent = Livewire::test(VocabularyEntryTree::class, ['vocabularyId' => $vocabulary->id]);

        $loadedEntries = $livewireComponent->get('entries');
        
        $this->assertCount(2, $loadedEntries, "Should be 2 root entries."); // Mammals, Reptiles
        
        // Check root entry 1 (Mammals)
        $loadedEntry1 = $loadedEntries->firstWhere('entry_value', 'FAUNA_MAMMALS');
        $this->assertNotNull($loadedEntry1, "Mammals entry not found.");
        $this->assertEquals('FAUNA_MAMMALS', $loadedEntry1->entry_value);
        $this->assertEquals(0, $loadedEntry1->depth, "Mammals depth should be 0.");
        $this->assertEquals(0, $loadedEntry1->rank, "Mammals rank should be 0.");
        $this->assertCount(2, $loadedEntry1->children, "Mammals should have 2 children.");

        // Check order of root entries
        $this->assertEquals('FAUNA_MAMMALS', $loadedEntries[0]->entry_value, "First root entry should be Mammals by rank.");
        $this->assertEquals('FAUNA_REPTILES', $loadedEntries[1]->entry_value, "Second root entry should be Reptiles by rank.");

        // Check children of Mammals
        $mammalChildren = $loadedEntry1->children;
        $this->assertEquals('FAUNA_MAMMALS_DOGS', $mammalChildren[0]->entry_value, "First child of Mammals should be Dogs.");
        $this->assertEquals(1, $mammalChildren[0]->depth, "Dogs depth should be 1.");
        $this->assertEquals(0, $mammalChildren[0]->rank, "Dogs rank should be 0.");
        $this->assertEquals('FAUNA_MAMMALS_CATS', $mammalChildren[1]->entry_value, "Second child of Mammals should be Cats.");
        $this->assertEquals(1, $mammalChildren[1]->depth, "Cats depth should be 1.");
        $this->assertEquals(1, $mammalChildren[1]->rank, "Cats rank should be 1.");

        // Check grandchild (Labrador)
        $dogChildren = $mammalChildren[0]->children;
        $this->assertCount(1, $dogChildren, "Dogs should have 1 child.");
        $this->assertEquals('FAUNA_MAMMALS_DOGS_LABRADOR', $dogChildren[0]->entry_value, "Child of Dogs should be Labrador.");
        $this->assertEquals(2, $dogChildren[0]->depth, "Labrador depth should be 2.");
    }

    public function testUpdateEntryOrder()
    {
        $vocabulary = Vocabulary::create(['name' => 'Test Vocab', 'slug' => 'test-vocab']);
        $rootA = VocabularyEntry::create(['vocabulary_id' => $vocabulary->id, 'entry_labels' => ['en' => 'Root A'], 'entry_value' => 'ROOT_A', 'rank' => 0]);
        $rootB = VocabularyEntry::create(['vocabulary_id' => $vocabulary->id, 'entry_labels' => ['en' => 'Root B'], 'entry_value' => 'ROOT_B', 'rank' => 1]);
        $childA1 = VocabularyEntry::create(['vocabulary_id' => $vocabulary->id, 'entry_labels' => ['en' => 'Child A1'], 'entry_value' => 'CHILD_A1', 'rank' => 0, 'parent_id' => $rootA->id]);

        $livewireComponent = Livewire::test(VocabularyEntryTree::class, ['vocabularyId' => $vocabulary->id]);

        // Case 1: Move ChildA1 to become a child of RootB
        $siblingIdsOfRootBAfterMove = [$childA1->id]; // ChildA1 will be the only child of RootB initially
        $livewireComponent->call('updateEntryOrder', $childA1->id, $rootB->id, 0, $siblingIdsOfRootBAfterMove);
        
        $changedEntries = $livewireComponent->get('changedEntries');
        $this->assertArrayHasKey($childA1->id, $changedEntries, "ChildA1 should be in changedEntries.");
        $this->assertEquals($rootB->id, $changedEntries[$childA1->id]['parent_id'], "ChildA1 parent_id should be RootB's id.");
        $this->assertEquals(0, $changedEntries[$childA1->id]['rank'], "ChildA1 rank should be 0 under RootB.");

        // Case 2: Reorder RootA and RootB. Move RootA to rank 1, RootB to rank 0.
        // Original: RootA (rank 0), RootB (rank 1)
        // New order: RootB (rank 0), RootA (rank 1)
        $newRootOrder = [$rootB->id, $rootA->id];
        // Simulate RootA being dragged to the second position (index 1) under the root (null parent)
        $livewireComponent->call('updateEntryOrder', $rootA->id, null, 1, $newRootOrder);
        
        $changedEntries = $livewireComponent->get('changedEntries');
        // RootA is moved
        $this->assertArrayHasKey($rootA->id, $changedEntries, "RootA should be in changedEntries.");
        $this->assertEquals(null, $changedEntries[$rootA->id]['parent_id'], "RootA parent_id should be null.");
        $this->assertEquals(1, $changedEntries[$rootA->id]['rank'], "RootA rank should be 1.");
        // RootB is also affected as its rank changes
        $this->assertArrayHasKey($rootB->id, $changedEntries, "RootB should be in changedEntries.");
        $this->assertEquals(null, $changedEntries[$rootB->id]['parent_id'], "RootB parent_id should be null.");
        $this->assertEquals(0, $changedEntries[$rootB->id]['rank'], "RootB rank should be 0.");
    }

    public function testSaveOrderPersistsChanges()
    {
        $vocabulary = Vocabulary::create(['name' => 'Save Test Vocab', 'slug' => 'save-test-vocab']);
        $entryA = VocabularyEntry::create(['vocabulary_id' => $vocabulary->id, 'entry_labels' => ['en' => 'Entry A'], 'entry_value' => 'ENTRY_A', 'rank' => 0]);
        $entryB = VocabularyEntry::create(['vocabulary_id' => $vocabulary->id, 'entry_labels' => ['en' => 'Entry B'], 'entry_value' => 'ENTRY_B', 'rank' => 1]);
        $entryC = VocabularyEntry::create(['vocabulary_id' => $vocabulary->id, 'entry_labels' => ['en' => 'Entry C'], 'entry_value' => 'ENTRY_C', 'rank' => 2]);

        $livewireComponent = Livewire::test(VocabularyEntryTree::class, ['vocabularyId' => $vocabulary->id]);

        // Step 1: Make EntryC a child of EntryA, rank 0
        // Siblings of EntryA's children after move: [EntryC]
        $livewireComponent->call('updateEntryOrder', $entryC->id, $entryA->id, 0, [$entryC->id]);
        
        // Step 2: Reorder EntryA and EntryB at root. Move EntryB to rank 0, EntryA to rank 1.
        // Original root order: A (0), B (1), C (2 - but C is now child of A)
        // Effective root order for this operation: A (0), B (1)
        // New root order: B (0), A (1)
        $livewireComponent->call('updateEntryOrder', $entryB->id, null, 0, [$entryB->id, $entryA->id]);
        // This will also update EntryA's rank to 1 implicitly by its position in siblingIdsInOrder for parent null.

        $livewireComponent->call('saveOrder');

        // Assert database changes
        $updatedEntryA = VocabularyEntry::find($entryA->id);
        $updatedEntryB = VocabularyEntry::find($entryB->id);
        $updatedEntryC = VocabularyEntry::find($entryC->id);

        // EntryC checks
        $this->assertEquals($entryA->id, $updatedEntryC->parent_id, "EntryC parent_id should be EntryA's id.");
        $this->assertEquals(0, $updatedEntryC->rank, "EntryC rank under EntryA should be 0.");

        // Root entries reorder checks
        $this->assertEquals(1, $updatedEntryA->rank, "EntryA rank at root should be 1.");
        $this->assertEquals(null, $updatedEntryA->parent_id, "EntryA parent_id should be null.");

        $this->assertEquals(0, $updatedEntryB->rank, "EntryB rank at root should be 0.");
        $this->assertEquals(null, $updatedEntryB->parent_id, "EntryB parent_id should be null.");

        // Assert $changedEntries is empty after saving
        $this->assertEmpty($livewireComponent->get('changedEntries'), "\$changedEntries should be empty after saveOrder.");
        
        // Check if a success notification was likely sent.
        // This is an indirect check. If saveOrder completed and had changes, it should have triggered a success notification flow.
        // The component's handleSaveOrder method is responsible for this.
        // We know there were changes, so a success notification should have been sent.
        // We can't directly assert Filament notifications here without a more complex setup.
        // However, if $changedEntries is empty, it implies saveOrder was called and ran through.
        // The actual notification test might be better in a browser test.
    }
}
