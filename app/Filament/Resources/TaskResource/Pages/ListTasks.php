<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use App\Models\SharedTableView;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;
use Filament\Forms;
use Filament\Notifications\Notification;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected static string $view = 'filament.resources.task-resource.pages.list-tasks';

    public function mount(): void
    {
        parent::mount();
    }

    public function loadView(int $viewId)
    {
        $view = SharedTableView::find($viewId);
        if (!$view) {
            Notification::make()
                ->title('View not found')
                ->danger()
                ->send();
            return;
        }

        // Store the entire view state in session to be applied by JavaScript
        session([
            'pending_table_view_' . $view->id => [
                'filters' => $view->filters ?? [],
                'sort_column' => $view->sort_column,
                'sort_direction' => $view->sort_direction,
                'search_query' => $view->search_query,
                'column_visibility' => $view->column_visibility ?? [],
            ]
        ]);
        
        // Build query string from saved view (for URL)
        $queryParams = [];
        
        // Apply filters - handle nested arrays properly
        if (!empty($view->filters) && is_array($view->filters)) {
            foreach ($view->filters as $key => $value) {
                if (is_array($value)) {
                    // Handle nested filter arrays like ['value' => 6]
                    foreach ($value as $subKey => $subValue) {
                        $queryParams["tableFilters[{$key}][{$subKey}]"] = $subValue;
                    }
                } else {
                    $queryParams["tableFilters[{$key}]"] = $value;
                }
            }
        }
        
        // Apply sorting
        if ($view->sort_column && $view->sort_direction) {
            $queryParams['tableSortColumn'] = $view->sort_column;
            $queryParams['tableSortDirection'] = $view->sort_direction;
        }
        
        // Apply search
        if ($view->search_query) {
            $queryParams['tableSearch'] = $view->search_query;
        }
        
        // Build URL with query string
        $baseUrl = TaskResource::getUrl('index');
        $url = $baseUrl . (count($queryParams) > 0 ? '?' . http_build_query($queryParams) : '');
        
        Notification::make()
            ->title('View "' . $view->view_name . '" loaded')
            ->success()
            ->send();
        
        return redirect($url);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save_view')
                ->label('Save Current View')
                ->icon('heroicon-o-bookmark')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('view_name')
                        ->label('View Name')
                        ->placeholder('e.g., Active Tasks, My Filters')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Toggle::make('set_as_default')
                        ->label('Set as Default View')
                        ->helperText('This view will be applied to all users by default')
                        ->default(false),
                    Forms\Components\Hidden::make('table_state'),
                ])
                ->action(function (array $data) {
                    // Get ALL request parameters - Filament includes table state here
                    $allParams = request()->all();
                    
                    $filters = [];
                    $sortColumn = $allParams['tableSortColumn'] ?? null;
                    $sortDirection = $allParams['tableSortDirection'] ?? null;
                    $searchQuery = $allParams['tableSearch'] ?? null;
                    
                    // Extract filters from request parameters
                    // Filament sends filters as tableFilters[key][value] or tableFilters[key]
                    foreach ($allParams as $key => $value) {
                        if (str_starts_with($key, 'tableFilters[')) {
                            if (preg_match('/tableFilters\[([^\]]+)\](?:\[([^\]]+)\])?/', $key, $matches)) {
                                $filterName = $matches[1];
                                $subKey = $matches[2] ?? null;
                                
                                if ($subKey) {
                                    if (!isset($filters[$filterName])) {
                                        $filters[$filterName] = [];
                                    }
                                    $filters[$filterName][$subKey] = $value;
                                } else {
                                    $filters[$filterName] = $value;
                                }
                            }
                        }
                    }
                    
                    // Also check for table state in form data (from JavaScript - more reliable)
                    $tableStateJson = $data['table_state'] ?? null;
                    if ($tableStateJson) {
                        $tableState = json_decode($tableStateJson, true);
                        if ($tableState && is_array($tableState)) {
                            // Merge JavaScript captured state (takes priority as it's more current)
                            if (!empty($tableState['filters'])) {
                                $filters = array_merge($filters, $tableState['filters']);
                            }
                            if (!empty($tableState['sortColumn'])) {
                                $sortColumn = $tableState['sortColumn'];
                            }
                            if (!empty($tableState['sortDirection'])) {
                                $sortDirection = $tableState['sortDirection'];
                            }
                            if (!empty($tableState['search'])) {
                                $searchQuery = $tableState['search'];
                            }
                        }
                    }
                    
                    // Get column visibility from session (set by JavaScript if available)
                    $columnVisibility = session('current_column_visibility', []);
                    
                    // Debug: Log what we're saving
                    \Log::info('Saving view', [
                        'view_name' => $data['view_name'],
                        'filters' => $filters,
                        'sortColumn' => $sortColumn,
                        'sortDirection' => $sortDirection,
                        'searchQuery' => $searchQuery,
                        'all_params_keys' => array_keys($allParams),
                    ]);
                    
                    // Save the view
                    $this->saveSharedView(
                        $data['view_name'],
                        $data['set_as_default'] ?? false,
                        [
                            'filters' => $filters,
                            'sortColumn' => $sortColumn,
                            'sortDirection' => $sortDirection,
                            'columnVisibility' => $columnVisibility,
                            'search' => $searchQuery,
                        ]
                    );
                    
                    // Redirect to refresh page and show new tab
                    redirect(TaskResource::getUrl('index'));
                })
                ->modalSubmitActionLabel('Save View')
                ->extraModalFooterActions([
                    Actions\Action::make('cancel')
                        ->label('Cancel')
                        ->color('gray')
                        ->action(fn () => null),
                ]),
            Actions\CreateAction::make(),
        ];
    }

    public function saveSharedView(string $viewName, bool $setAsDefault = false, array $tableState = []): void
    {
        try {
            // If setting as default, unset other default views for this resource
            if ($setAsDefault) {
                SharedTableView::where('resource_name', 'TaskResource')
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            // Extract data from table state
            $filters = $tableState['filters'] ?? [];
            $sortColumn = $tableState['sortColumn'] ?? null;
            $sortDirection = $tableState['sortDirection'] ?? null;
            $columnVisibility = $tableState['columnVisibility'] ?? [];
            $searchQuery = $tableState['search'] ?? null;

            // Save the shared view
            SharedTableView::create([
                'resource_name' => 'TaskResource',
                'view_name' => $viewName,
                'filters' => $filters,
                'sort_column' => $sortColumn,
                'sort_direction' => $sortDirection,
                'column_visibility' => $columnVisibility,
                'search_query' => $searchQuery,
                'is_default' => $setAsDefault,
                'created_by' => auth()->id(),
            ]);

            Notification::make()
                ->title('View "' . $viewName . '" saved successfully')
                ->success()
                ->send();
            
            // Dispatch event to refresh page and show new tab
            $this->dispatch('view-saved');
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error saving view: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function applySavedView(int $viewId): void
    {
        $view = SharedTableView::find($viewId);
        if (!$view) {
            Notification::make()
                ->title('View not found')
                ->danger()
                ->send();
            return;
        }

        // Build query string from saved view
        $queryParams = [];
        
        // Apply filters - handle nested arrays properly
        if (!empty($view->filters) && is_array($view->filters)) {
            foreach ($view->filters as $key => $value) {
                if (is_array($value)) {
                    // Handle nested filter arrays like ['value' => 6]
                    foreach ($value as $subKey => $subValue) {
                        $queryParams["tableFilters[{$key}][{$subKey}]"] = $subValue;
                    }
                } else {
                    $queryParams["tableFilters[{$key}]"] = $value;
                }
            }
        }
        
        // Apply sorting
        if ($view->sort_column && $view->sort_direction) {
            $queryParams['tableSortColumn'] = $view->sort_column;
            $queryParams['tableSortDirection'] = $view->sort_direction;
        }
        
        // Apply search
        if ($view->search_query) {
            $queryParams['tableSearch'] = $view->search_query;
        }
        
        // Store column visibility in session to be applied by JavaScript
        if (!empty($view->column_visibility) && is_array($view->column_visibility)) {
            session(['pending_column_visibility_' . $viewId => $view->column_visibility]);
        }
        
        // Build URL with query string
        $baseUrl = TaskResource::getUrl('index');
        $url = $baseUrl . (count($queryParams) > 0 ? '?' . http_build_query($queryParams) : '');
        
        Notification::make()
            ->title('View loaded: ' . $view->view_name)
            ->success()
            ->send();
        
        // Redirect using Filament's redirect method
        redirect($url);
    }

    public function getTitle(): string
    {
        return 'Tasks';
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }
    
    public function getFooter(): ?View
    {
        return view('filament.resources.task-resource.table-footer');
    }
}
