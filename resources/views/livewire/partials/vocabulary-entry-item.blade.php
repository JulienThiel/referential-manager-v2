<li class="list-group-item border p-2 my-1 rounded" data-item-id="{{ $entry->id }}" style="margin-left: {{ $entry->depth * 20 }}px;">
    <span class="drag-handle cursor-move">&#x2630;</span> {{-- Simple drag handle (hamburger icon) --}}
    <span>{{ $entry->entry_labels[app()->getLocale()] ?? $entry->entry_labels['fr-BE'] ?? $entry->entry_labels['en'] ?? $entry->entry_value ?? 'Unnamed Entry' }}</span>
    {{-- Display other info as needed --}}

    {{-- Recursively include children if any --}}
    @if ($entry->children && $entry->children->isNotEmpty())
        <ul class="list-group mt-2" data-parent-id="{{ $entry->id }}">
            @foreach ($entry->children as $childEntry)
                @include('livewire.partials.vocabulary-entry-item', ['entry' => $childEntry, 'isRoot' => false])
            @endforeach
        </ul>
    @endif
</li>
