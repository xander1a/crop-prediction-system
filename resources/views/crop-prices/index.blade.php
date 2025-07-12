@include('layouts.app')

<div class="min-h-screen bg-gray-50 py-6">
    <div class="container mx-auto px-4 sm:px-6">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row justify-between items-center mb-8">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-3xl font-extrabold text-green-800">Crop Prices</h1>
                <p class="text-gray-600 mt-1">Manage and monitor agricultural commodity pricing</p>
            </div>
            <button 
                onclick="openModal()"
                class="flex items-center bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg transition duration-200 shadow-md"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add New Price
            </button>
        </div>


        <!-- Success Message -->
@if (session('success'))
    <div class="mb-4 p-4 rounded-md bg-green-100 border border-green-300 text-green-800">
        {{ session('success') }}
    </div>
@endif

<!-- Validation Error Messages -->
@if ($errors->any())
    <div class="mb-4 p-4 rounded-md bg-red-100 border border-red-300 text-red-800">
        <strong class="font-semibold">Whoops! Something went wrong.</strong>
        <ul class="mt-2 list-disc list-inside text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

      <!-- Crop Prices Table - Visible on md and up -->
<div class="hidden md:block bg-white rounded-xl shadow-md overflow-hidden">
    <div class="overflow-x-auto max-w-full">
        <table class="w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">No</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Crop</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Region</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Price</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Weather Condition%</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Source</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                
                @php
                    $count = 0;
                @endphp

                @foreach($cropPrices as $price)
                <tr class="hover:bg-gray-50 transition duration-150">
                    <td class="px-6 py-4 whitespace-nowrap">{{ ++$count }}</td>
                    <td class="px-6 py-4 whitespace-nowrap flex items-center space-x-3">
                        <div class="h-8 w-8 bg-green-100 rounded-full flex items-center justify-center text-green-700 font-semibold">
                            {{ substr($price->crop->name, 0, 1) }}
                        </div>
                        <div>{{ $price->crop->name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $price->region->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-green-800 font-semibold">${{ number_format($price->price, 2) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $price->date_recorded->format('M d, Y') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $price->wether_condition }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ $price->source ?? 'N/A' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex space-x-3">
                            <a href="{{ route('crop-prices.edit', $price->id) }}" class="text-blue-600 hover:text-blue-900">
                                ✏️
                            </a>
                            <form 
                                action="{{ route('crop-prices.destroy', $price->id) }}" 
                                method="POST" 
                                onsubmit="return confirm('Are you sure you want to delete this price record?')"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

        
        <!-- Mobile View for Small Screens -->
        <div class="md:hidden mt-4">
            <div class="space-y-4">
                @foreach($cropPrices as $price)
                <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-green-500">
                    <div class="flex justify-between items-center mb-2">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">{{ $price->crop->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $price->region->name }}</p>
                        </div>
                        <div class="text-lg font-bold text-green-700">${{ number_format($price->price, 2) }}</div>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <div class="text-gray-500">{{ $price->date_recorded->format('M d, Y') }}</div>
                        <div class="flex space-x-3">
                            <a 
                                href="{{ route('crop-prices.edit', $price->id) }}" 
                                class="text-blue-600 hover:text-blue-900"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>
                            <form 
                                action="{{ route('crop-prices.destroy', $price->id) }}" 
                                method="POST" 
                                class="inline"
                                onsubmit="return confirm('Are you sure you want to delete this price record?')"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="mt-1 text-xs text-gray-500">
                        <span class="font-medium">Source:</span> {{ $price->source ?? 'N/A' }}
                    </div>
                </div>
                @endforeach
            </div>
            <!-- Mobile Pagination -->
            <div class="mt-4">
                {{ $cropPrices->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Add Price Modal -->
<!-- Add Price Modal -->
<div id="addPriceModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay with blur effect -->
        <div class="fixed inset-0 bg-black bg-opacity-60 transition-opacity backdrop-blur-sm" aria-hidden="true" onclick="closeModal()"></div>
        
        <!-- Modal panel -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl w-full mx-4 border border-gray-100">
            
            <!-- Modal header with gradient -->
            <div class="bg-gradient-to-r from-emerald-50 to-green-50 px-8 py-6 border-b border-emerald-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800">Add New Crop Price</h3>
                            <p class="text-sm text-gray-500 mt-1">Enter crop pricing information for market tracking</p>
                        </div>
                    </div>
                    <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors duration-200 p-2 hover:bg-gray-100 rounded-xl">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Form content -->
            <div class="bg-white px-8 py-8">
                <form id="priceForm" action="{{ route('crop-prices.store') }}" method="POST">
                    @csrf
                    
                    <!-- Grid layout for form fields -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        
                        <!-- Column 1 -->
                        <div class="space-y-6">
                            <!-- Crop Selection -->
                            <div class="group">
                                <label for="crop_id" class="block text-sm font-semibold text-gray-700 mb-3">Crop Type</label>
                                <div class="relative">
                                    <select 
                                        id="crop_id" 
                                        name="crop_id" 
                                        class="block w-full px-4 py-4 text-base border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 hover:bg-white hover:border-gray-300 appearance-none"
                                        required
                                    >
                                        <option value="">Choose a crop...</option>
                                        @foreach($crops as $crop)
                                            <option value="{{ $crop->id }}">{{ $crop->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Price Input -->
                            <div class="group">
                                <label for="price" class="block text-sm font-semibold text-gray-700 mb-3">Price per Unit</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <span class="text-gray-500 text-lg font-medium">$</span>
                                    </div>
                                    <input 
                                        type="number" 
                                        id="price" 
                                        name="price" 
                                        step="0.01" 
                                        min="0" 
                                        placeholder="0.00"
                                        class="block w-full pl-10 pr-4 py-4 text-base border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 hover:bg-white hover:border-gray-300"
                                        required
                                    >
                                </div>
                            </div>
                        </div>
                        
                        <!-- Column 2 -->
                        <div class="space-y-6">
                            <!-- Region Selection -->
                            <div class="group">
                                <label for="region_id" class="block text-sm font-semibold text-gray-700 mb-3">Region</label>
                                <div class="relative">
                                    <select 
                                        id="region_id" 
                                        name="region_id" 
                                        class="block w-full px-4 py-4 text-base border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 hover:bg-white hover:border-gray-300 appearance-none"
                                        required
                                    >
                                        <option value="">Select region...</option>
                                        @foreach($regions as $region)
                                            <option value="{{ $region->id }}">{{ $region->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Date Input -->
                            <div class="group">
                                <label for="date_recorded" class="block text-sm font-semibold text-gray-700 mb-3">Date Recorded</label>
                                <input 
                                    type="date" 
                                    id="date_recorded" 
                                    name="date_recorded" 
                                    class="block w-full px-4 py-4 text-base border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 hover:bg-white hover:border-gray-300"
                                    required
                                >
                            </div>


                            <div class="group relative">
    <label for="wether_condition" class="block text-sm font-semibold text-gray-700 mb-3">Weather Condition</label>
    <select 
        id="wether_condition" 
        name="wether_condition" 
        class="block w-full px-4 py-4 pr-10 text-base border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 hover:bg-white hover:border-gray-300 appearance-none cursor-pointer"
        required
    >
        <option value="" disabled selected class="text-gray-500">Select weather condition</option>
        <option value="excellent" class="text-gray-900">Excellent</option>
        <option value="good" class="text-gray-900">Good</option>
        <option value="fair" class="text-gray-900">Fair</option>
        <option value="poor" class="text-gray-900">Poor</option>
        <option value="very_poor" class="text-gray-900">Very Poor</option>
        <option value="drought" class="text-gray-900">Drought</option>
        <option value="flood" class="text-gray-900">Flood</option>
    </select>
    
    <!-- Custom dropdown arrow -->
    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none mt-8">
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </div>
</div>
                        </div>
                    </div>
                    
                    <!-- Full width source field -->
                    <div class="mt-8">
                        <label for="source" class="block text-sm font-semibold text-gray-700 mb-3">
                            Data Source 
                            <span class="text-gray-400 font-normal">(Optional)</span>
                        </label>
                        <input 
                            type="text" 
                            id="source" 
                            name="source" 
                            placeholder="e.g., Local Market Survey, Agricultural Department Report, Farm Association Data"
                            class="block w-full px-4 py-4 text-base border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 hover:bg-white hover:border-gray-300"
                        >
                    </div>
                </form>
            </div>
            
            <!-- Modal footer with enhanced buttons -->
            <div class="bg-gray-50 px-8 py-6 border-t border-gray-100 flex flex-col sm:flex-row sm:justify-end sm:space-x-4 space-y-3 sm:space-y-0">
                <button 
                    type="button" 
                    onclick="closeModal()"
                    class="inline-flex items-center justify-center px-6 py-3 border-2 border-gray-300 text-base font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 sm:w-auto w-full"
                >
                    Cancel
                </button>
                <button 
                    type="submit" 
                    form="priceForm" 
                    class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-semibold rounded-xl text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 sm:w-auto w-full"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save Price Data
                </button>
            </div>
        </div>
    </div>
</div>

#
<script>
    function openModal() {
        document.getElementById('addPriceModal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal() {
        document.getElementById('addPriceModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    // Set today's date as default
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('date_recorded').value = today;
        
        // Close modal on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    });
</script>