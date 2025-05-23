<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\VocabularyEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification; // Added for Filament notifications

class VocabularyEntryTree extends Component
{
    protected $listeners = ['saveVocabularyOrder' => 'handleSaveOrder'];

    public string $vocabularyId;
    public Collection $entries; // Type hint as Illuminate\Support\Collection
    public array $changedEntries = [];

    public function mount(string $vocabularyId): void
    {
        $this->vocabularyId = $vocabularyId;
        $this->loadEntries();
    }

    protected function loadEntries(): void
    {
        // Use the method created in the VocabularyEntry model
        $this->entries = VocabularyEntry::getTreeForVocabulary($this->vocabularyId);
    }

    /**
     * Called by JavaScript when an entry is dragged and dropped.
     *
     * @param string $itemId The ID of the item that was moved.
     * @param string|null $newParentId The ID of the new parent, or null if dropped at root.
     * @param int $newRank The new rank among its siblings (0-indexed).
     * @param array $siblingIdsInOrder An array of sibling IDs in their new order.
     */
    public function updateEntryOrder(string $itemId, ?string $newParentId, int $newRank, array $siblingIdsInOrder): void
    {
        // Find the moved entry in the local $this->entries collection (and its children)
        // This is a bit complex as the structure is nested.
        // For now, let's just record the raw change.
        // A more sophisticated approach would update the $this->entries collection directly
        // to reflect the change immediately in the UI without a full reload.

        $this->changedEntries[$itemId] = [
            'parent_id' => $newParentId,
            // Rank will be determined by position in siblingIdsInOrder during save
        ];

        // Update ranks for all siblings affected by the drop
        foreach ($siblingIdsInOrder as $rank => $siblingId) {
            $this->changedEntries[$siblingId] = array_merge(
                $this->changedEntries[$siblingId] ?? ['parent_id' => $newParentId], // Preserve parent_id if it was the moved item
                ['rank' => $rank]
            );
            // If the moved item is part of these siblings, its parent_id also needs to be set
            if ($siblingId === $itemId) {
                 $this->changedEntries[$siblingId]['parent_id'] = $newParentId;
            }
        }

        // Optional: For immediate UI feedback, you could try to rebuild $this->entries.
        // $this->loadEntries(); // This would show saved state, not intermediate
        // Or, more complex: manipulate $this->entries in PHP to reflect the new order.
        // For now, we rely on the JS to visually update, and saveOrder to persist.
    }

    public function saveOrder(): void
    {
        if (empty($this->changedEntries)) {
            // Send a notification that there's nothing to save
            Notification::make()
                ->title('No changes to save')
                ->info()
                ->send();
            return;
        }

        DB::transaction(function () {
            foreach ($this->changedEntries as $id => $data) {
                $entry = VocabularyEntry::find($id);
                if ($entry) {
                    $updateData = [];
                    if (array_key_exists('parent_id', $data)) {
                        $updateData['parent_id'] = $data['parent_id'];
                    }
                    if (array_key_exists('rank', $data)) {
                        $updateData['rank'] = $data['rank'];
                    }
                    if (!empty($updateData)) {
                        $entry->update($updateData);
                    }
                }
            }
        });

        $this->changedEntries = [];
        $this->loadEntries(); // Reload to reflect the persisted state
        // Notification is now handled by handleSaveOrder
    }

    public function handleSaveOrder(): void
    {
        // Call saveOrder. It will handle its own "no changes" notification.
        $this->saveOrder();

        // If saveOrder proceeded (i.e., there were changes), then changedEntries would have been cleared.
        // We assume that if saveOrder didn't return early due to no changes, it was successful.
        // To avoid sending a success message if "no changes" was already sent,
        // we can check if changedEntries is now empty (it should be if save was successful).
        // However, the original intent seems to be that handleSaveOrder *always* sends success
        // if saveOrder didn't send "no changes".

        // Let's check a flag or rely on the fact that saveOrder would have sent a notification
        // if there were no changes. If we reach this point and saveOrder didn't "return"
        // due to no changes, it means changes were processed.
        
        // A simple way: if saveOrder did not send "no changes", it implies it proceeded.
        // The current structure of saveOrder is:
        // 1. If no changes, send "no changes" and return.
        // 2. If changes, process them, clear changedEntries.
        // So, if changedEntries is empty *after* calling saveOrder, AND it wasn't empty *before*
        // (which is implied by the saveOrder logic), it means changes were processed.
        // This is still a bit indirect. The prompt implies this method sends success.

        // Re-evaluating: saveOrder handles the "no changes" case.
        // If saveOrder *doesn't* return early, it means changes were processed.
        // So, if we get past the call to $this->saveOrder() AND saveOrder didn't send
        // the "no changes" notification and return, then it's a success.
        // The most straightforward way is to let saveOrder return a boolean.
        // public function saveOrder(): bool { ... return false if no changes ... return true if saved }
        // if ($this->saveOrder()) { Notification ... }
        // Since I cannot change the signature now based on the tool, I will assume
        // if execution continues past $this->saveOrder(), and if changedEntries *were* present
        // (which saveOrder checks for), then it's a success.

        // The logic in saveOrder is: if empty, notify and return.
        // So if it *doesn't* return, it means there *were* changes.
        // We need to know if saveOrder actually processed anything.
        // A simple way is to check if changedEntries was initially non-empty
        // This info is lost after saveOrder clears it.

        // Let's stick to the prompt's intent: handleSaveOrder sends the success notification
        // and saveOrder sends the "no changes" notification.
        // This means saveOrder should not send success.
        // And handleSaveOrder should only send success if saveOrder didn't send "no changes".
        // This is tricky without a return value from saveOrder.

        // Simplest interpretation:
        // saveOrder: if no changes, sends INFO and returns. Otherwise, processes.
        // handleSaveOrder: calls saveOrder. Then *always* sends SUCCESS.
        // This might lead to two notifications if there are no changes (INFO then SUCCESS). This is bad.

        // Corrected approach:
        // saveOrder returns a status, or handleSaveOrder checks a condition set by saveOrder.
        // Given the constraints, let's refine the logic slightly:
        // `saveOrder` will only perform the save. `handleSaveOrder` will manage notifications.

        // No, the prompt is:
        // handleSaveOrder: calls saveOrder(), then sends SUCCESS.
        // saveOrder: if empty, sends INFO, returns. Else, saves.
        // This means if saveOrder sends INFO and returns, handleSaveOrder will *still* send SUCCESS. This is not good.

        // The most robust way given the current structure is:
        // saveOrder: if empty, return false. Else, save, return true.
        // handleSaveOrder: if (this->saveOrder()) { send SUCCESS } else { send INFO }
        // But I cannot change saveOrder's signature with the replace tool easily.

        // Let's assume the provided `saveOrder` from the prompt (which I have) is:
        // - if empty, send INFO, return.
        // - else, save, clear changedEntries, loadEntries. (no success notification here)
        // And `handleSaveOrder` from the prompt is:
        // - call saveOrder()
        // - send SUCCESS.
        // This has the double notification issue if no changes.

        // The `saveOrder` I have from Turn 19:
        // - if empty, send INFO, return.
        // - else, save, clear changedEntries, loadEntries. (no success notification)

        // The `handleSaveOrder` from Turn 19 had a faulty `if(!empty($this->changedEntries))`
        // Let's fix `handleSaveOrder` to only send SUCCESS if `saveOrder` did not return early.
        // This requires `saveOrder` to indicate if it returned early.
        // The best I can do without changing `saveOrder` signature is to check `changedEntries` *before* calling `saveOrder`.

        $hadChanges = !empty($this->changedEntries);
        $this->saveOrder(); // This will send "No changes to save" if $hadChanges was false.

        if ($hadChanges) {
            // If there were changes, saveOrder processed them (and didn't send "No changes").
            // So, now we send the success notification.
            Notification::make()
                ->title('Order saved successfully')
                ->success()
                ->send();
        }
        // If $hadChanges was false, saveOrder sent the "No changes" notification,
        // and this block is skipped, avoiding double notification.
    }

    public function render()
    {
        // The view file will be created in the next step:
        // resources/views/livewire/vocabulary-entry-tree.blade.php
        return view('livewire.vocabulary-entry-tree');
    }
}
