<div>
    {{-- Include SortableJS library. Ensure this is available in your project.
         If not already globally available, you might need to add it via npm/yarn and bundle it,
         or include a CDN link. For this task, assume SortableJS is available.
         A common way to ensure it's loaded before use if using a CDN:
    --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <div wire:ignore> {{-- wire:ignore to prevent Livewire from interfering with SortableJS-managed DOM --}}
        <ul id="vocabulary-tree-root" class="list-group" data-parent-id="null">
            @foreach ($entries as $entry)
                @include('livewire.partials.vocabulary-entry-item', ['entry' => $entry, 'isRoot' => true])
            @endforeach
        </ul>
    </div>

    {{-- Button to save. Consider making this visible only when $changedEntries is not empty. --}}
    {{-- This button will be moved to the Relation Manager later, but keep it here for testing the component in isolation if needed --}}
    @if (count($changedEntries) > 0)
        <button wire:click="saveOrder" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
            Save Order
        </button>
        <span class="ml-2 text-sm text-gray-600">({{ count($changedEntries) }} changes pending)</span>
    @endif

    <script>
        document.addEventListener('livewire:load', function () {
            // Initialize SortableJS for the root list and any nested lists
            // This needs to be robust enough to handle dynamically added nested lists if applicable,
            // though for this tree, it's rendered once.

            const allSortableLists = Array.from(document.querySelectorAll('.list-group'));

            allSortableLists.forEach(listEl => {
                new Sortable(listEl, {
                    group: 'nested', // Group name, allows dragging between lists with the same group name
                    animation: 150,
                    fallbackOnBody: true,
                    swapThreshold: 0.65,
                    handle: '.drag-handle', // Define a drag handle if you want specific drag points
                    onEnd: function (evt) {
                        const itemEl = evt.item; // dragged HTMLElement
                        const itemId = itemEl.dataset.itemId;
                        const newParentEl = evt.to; // List element where the item was dropped
                        let newParentId = newParentEl.dataset.parentId; // Get parent ID from list's data attribute
                        if (newParentId === 'null') newParentId = null;


                        let siblingIdsInOrder = Array.from(newParentEl.children).map(child => child.dataset.itemId);
                        let newRank = siblingIdsInOrder.indexOf(itemId);

                        // Ensure all elements are present (sometimes SortableJS might move a temporary clone)
                        if (newRank === -1) {
                            // If item is not found, it might be because SortableJS is still moving the original.
                            // Re-query children after a small delay.
                            setTimeout(() => {
                                siblingIdsInOrder = Array.from(newParentEl.children).map(child => child.dataset.itemId);
                                newRank = siblingIdsInOrder.indexOf(itemId);
                                if (itemId && newRank !== -1) {
                                    // Call Livewire component method
                                    @this.call('updateEntryOrder', itemId, newParentId, newRank, siblingIdsInOrder);
                                }
                            }, 50);
                        } else {
                             if (itemId && newRank !== -1) {
                                // Call Livewire component method
                                @this.call('updateEntryOrder', itemId, newParentId, newRank, siblingIdsInOrder);
                            }
                        }
                    }
                });
            });
        });
    </script>
</div>
