@include('layouts.app')

@section('title', 'My Price Alerts')


<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold flex items-center">
            <svg class="w-6 h-6 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            My Price Alerts
        </h1>
        <p class="text-gray-600">Get notified when crop prices reach your target levels</p>
    </div>
    
    <button onclick="document.getElementById('createAlertModal').classList.remove('hidden')" 
            class="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Create Alert
    </button>
</div>

<!-- Active Alerts -->
<div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-lg font-semibold flex items-center">
            <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
            Active Alerts
        </h2>
        <span class="text-sm text-gray-500">{{ $alerts->total() }} total alerts</span>
    </div>
    
    <div class="divide-y divide-gray-200">
        @forelse($alerts as $alert)
        <div class="px-6 py-4 hover:bg-gray-50 transition-colors {{ $alert->is_read ? 'bg-gray-50' : 'bg-red-50' }}">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center mb-1">
                        <h3 class="font-medium truncate mr-2">
                            {{ $alert->crop->name }} - {{ $priceDirections[$alert->direction] }} {{ number_format($alert->price_threshold, 2) }} RWF
                        </h3>
                        @if(!$alert->is_read)
                        <span class="px-2 py-0.5 text-xs bg-red-100 text-red-800 rounded-full">New</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500">
                        <span>Notification: {{ ucfirst($alert->notification_method) }}</span>
                        <span class="mx-2">•</span>
                        <span>Created: {{ $alert->created_at->diffForHumans() }}</span>
                    </p>
                </div>
                <div class="ml-4 flex items-center space-x-2">
                    <button onclick="markAsRead({{ $alert->id }})" 
                            class="p-1 text-gray-400 hover:text-green-600 rounded-full"
                            title="Mark as read">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </button>
                    <form action="{{ route('alerts.destroy', $alert) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="p-1 text-gray-400 hover:text-red-600 rounded-full"
                                title="Delete alert"
                                onclick="return confirm('Are you sure you want to delete this alert?')">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="px-6 py-8 text-center text-gray-500">
            <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p>You don't have any active alerts</p>
            <p class="text-sm mt-1">Create your first alert to get notified about price changes</p>
        </div>
        @endforelse
    </div>
    
    @if($alerts->hasPages())
    <div class="bg-gray-50 px-6 py-3 border-t border-gray-200">
        {{ $alerts->links() }}
    </div>
    @endif
</div>

<!-- Create Alert Modal -->
<div id="createAlertModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Create New Price Alert</h3>
            <button onclick="document.getElementById('createAlertModal').classList.add('hidden')" 
                    class="text-gray-400 hover:text-gray-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form action="{{ route('alerts.store') }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label for="crop_id" class="block text-sm font-medium text-gray-700 mb-1">Crop</label>
                <select id="crop_id" name="crop_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
                    <option value="">Select a crop</option>
                    @foreach($crops as $crop)
                    <option value="{{ $crop->id }}">{{ $crop->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="mb-4">
                <label for="direction" class="block text-sm font-medium text-gray-700 mb-1">Alert When Price</label>
                <select id="direction" name="direction" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
                    <option value="">Select direction</option>
                    @foreach($priceDirections as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="mb-4">
                <label for="price_threshold" class="block text-sm font-medium text-gray-700 mb-1">Price Threshold (RWF)</label>
                <input type="number" step="0.01" min="0" id="price_threshold" name="price_threshold" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notification Method</label>
                <div class="space-y-2">
                    <div class="flex items-center">
                        <input id="email" name="notification_method" type="radio" value="email" required
                               class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                        <label for="email" class="ml-2 block text-sm text-gray-700">Email</label>
                    </div>
                    <div class="flex items-center">
                        <input id="app" name="notification_method" type="radio" value="app"
                               class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                        <label for="app" class="ml-2 block text-sm text-gray-700">In-App Notification</label>
                    </div>
                    <div class="flex items-center">
                        <input id="both" name="notification_method" type="radio" value="both"
                               class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                        <label for="both" class="ml-2 block text-sm text-gray-700">Both</label>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="document.getElementById('createAlertModal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Create Alert
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function markAsRead(alertId) {
        fetch(`/alerts/${alertId}/read`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        });
    }
</script>
@endpush
