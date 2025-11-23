<x-filament-panels::page>
    <div class="mb-4">
        <a href="{{ url('/admin/email-drafts/create') }}" class="text-black underline hover:no-underline text-base font-medium">
            New Email Draft
        </a>
    </div>
    
    <div class="space-y-4" x-data>
        @forelse($this->getEmailDrafts() as $draft)
            <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden" x-data="{ open: false }">
                <!-- Header - Clickable -->
                <button 
                    @click="open = !open"
                    class="w-full flex justify-between items-start p-6 hover:bg-gray-50 transition-colors text-left"
                    type="button"
                >
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $draft->department }}</h3>
                        @if($draft->title)
                            <p class="text-sm text-gray-600 mt-1">{{ $draft->title }}</p>
                        @endif
                        @if($draft->phone)
                            <p class="text-sm font-medium text-blue-600 mt-1">{{ $draft->phone }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 ml-4">
                        <a 
                            href="{{ url('/admin/email-drafts/' . $draft->id . '/edit') }}" 
                            @click.stop
                            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
                        >
                            Edit
                        </a>
                        <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </button>
                
                <!-- Collapsible Content -->
                <div 
                    x-show="open"
                    x-collapse
                    class="border-t border-gray-200"
                >
                    <div class="p-6 pt-4">
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            @if($draft->description)
                                <p class="text-sm text-gray-600 mb-4">{{ $draft->description }}</p>
                            @endif
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @if($draft->phone)
                                    <div class="flex items-start">
                                        <span class="text-sm font-medium text-gray-700 mr-2">Phone:</span>
                                        <span class="text-sm text-gray-900">{{ $draft->phone }}</span>
                                    </div>
                                @endif
                                @if($draft->email)
                                    <div class="flex items-start">
                                        <span class="text-sm font-medium text-gray-700 mr-2">Email:</span>
                                        <span class="text-sm text-gray-900">{{ $draft->email }}</span>
                                    </div>
                                @endif
                                @if($draft->hours)
                                    <div class="flex items-start md:col-span-2">
                                        <span class="text-sm font-medium text-gray-700 mr-2">Hours:</span>
                                        <span class="text-sm text-gray-900">{{ $draft->hours }}</span>
                                    </div>
                                @endif
                            </div>
                            @if($draft->is_emergency)
                                <div class="mt-4 inline-flex items-center px-3 py-2 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    24/7 Emergency Contact
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-lg shadow p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No email drafts</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by adding an email draft.</p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>



