<x-filament-panels::page>
    @php
        $savedViews = \App\Models\SharedTableView::where('resource_name', 'TaskResource')
            ->orderBy('created_at', 'desc')
            ->get();
    @endphp

    @if($savedViews->count() > 0)
        <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8" aria-label="Saved Views">
                @foreach($savedViews as $view)
                    <button
                        wire:click="loadView({{ $view->id }})"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ request()->get('view_id') == $view->id ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                        type="button"
                    >
                        {{ $view->view_name }}
                    </button>
                @endforeach
            </nav>
        </div>
    @endif

    {{ $this->table }}

    @script
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check for pending view state in session
            @if(session()->has('pending_table_view_' . request()->get('view_id')))
                const viewState = @json(session('pending_table_view_' . request()->get('view_id')));
                
                if (viewState) {
                    // Build URL with query parameters
                    const url = new URL(window.location.href);
                    
                    // Clear existing table parameters
                    url.searchParams.delete('tableFilters');
                    url.searchParams.delete('tableSortColumn');
                    url.searchParams.delete('tableSortDirection');
                    url.searchParams.delete('tableSearch');
                    
                    // Apply filters
                    if (viewState.filters && Object.keys(viewState.filters).length > 0) {
                        url.searchParams.set('tableFilters', JSON.stringify(viewState.filters));
                    }
                    
                    // Apply sorting
                    if (viewState.sort_column && viewState.sort_direction) {
                        url.searchParams.set('tableSortColumn', viewState.sort_column);
                        url.searchParams.set('tableSortDirection', viewState.sort_direction);
                    }
                    
                    // Apply search
                    if (viewState.search_query) {
                        url.searchParams.set('tableSearch', viewState.search_query);
                    }
                    
                    // Reload page with new parameters
                    window.location.href = url.toString();
                }
            @endif
        });
    </script>
    @endscript
</x-filament-panels::page>
