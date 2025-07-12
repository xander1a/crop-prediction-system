@include('layouts.app')

@section('title', 'Crop Directory')

<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mt-4">
    <div>
        <h1 class="text-2xl font-bold flex items-center">
            <svg class="w-6 h-6 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
            </svg>
            Crop Directory
        </h1>
       
    </div>
    
    <div class="flex items-center gap-4 w-full md:w-auto">
   
        <!-- Add Crop Button -->
        <button onclick="document.getElementById('addCropModal').classList.remove('hidden')" 
                class="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add Crop
        </button>
    </div>
</div>

@if($crops->count() > 0)
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    @foreach($crops as $crop)
    <a href="{{ route('crops.show', $crop) }}" class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
        @if($crop->image_url)
        <div class="h-40 overflow-hidden">
            <img src="{{ asset($crop->image_url) }}" alt="{{ $crop->name }}" class="w-full h-full object-cover">
        </div>
        @endif
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">{{ $crop->name }}</h3>
                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">
                    {{ $crop->prices_count }} records
                </span>
            </div>
            
            @if($crop->growing_period)
            <div class="mb-3">
                <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                    {{ $crop->growing_period }} month growing period
                </span>
            </div>
            @endif
            
            @if($crop->prices->isNotEmpty())
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Avg Price</span>
                    <span class="font-medium">{{ number_format($crop->prices[0]->avg_price, 2) }} RWF</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Price Range</span>
                    <span class="font-medium">
                        {{ number_format($crop->prices[0]->min_price, 2) }} - {{ number_format($crop->prices[0]->max_price, 2) }} RWF
                    </span>
                </div>
            </div>
            @else
            <div class="text-center py-4 text-gray-500">
                No price data available
            </div>
            @endif
        </div>
    </a>
    @endforeach
</div>


@else
<div class="bg-white rounded-xl shadow-md overflow-hidden text-center py-12">
    <svg class="h-16 w-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
    <h3 class="mt-2 text-lg font-medium text-gray-900">No crops found</h3>
    <p class="mt-1 text-gray-500">
        @if(request('search'))
            Try adjusting your search query
        @else
            Our crop database appears to be empty
        @endif
    </p>
</div>
@endif

<!-- Enhanced Add Crop Modal -->
<div id="addCropModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay with blur effect -->
        <div class="fixed inset-0 bg-black bg-opacity-60 transition-opacity backdrop-blur-sm" aria-hidden="true" onclick="document.getElementById('addCropModal').classList.add('hidden')"></div>
        
        <!-- Modal panel -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full mx-4 border border-gray-100">
            
            <!-- Modal header -->
            <div class="bg-green-50 px-8 py-6 border-b border-green-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800">Add New Crop</h3>
                            <p class="text-sm text-gray-500 mt-1">Register a new crop in the database</p>
                        </div>
                    </div>
                    <button onclick="document.getElementById('addCropModal').classList.add('hidden')" 
                            class="text-gray-400 hover:text-gray-600 transition-colors duration-200 p-2 hover:bg-gray-100 rounded-xl">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Form content -->
            <div class="bg-white px-8 py-8">
                <form action="{{ route('crops.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <!-- Crop Name -->
                    <div class="mb-6">
                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-3">
                            Crop Name 
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" required
                               placeholder="e.g., Maize, Coffee, Banana..."
                               class="block w-full px-4 py-4 text-base border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 bg-gray-50 hover:bg-white hover:border-gray-300">
                        @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Description -->
                    <div class="mb-6">
                        <label for="description" class="block text-sm font-semibold text-gray-700 mb-3">
                            Description
                            <span class="text-gray-400 font-normal">(Optional)</span>
                        </label>
                        <textarea id="description" name="description" rows="4"
                                  placeholder="Describe the crop, its characteristics, and growing conditions..."
                                  class="block w-full px-4 py-4 text-base border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 bg-gray-50 hover:bg-white hover:border-gray-300 resize-none"></textarea>
                        @error('description')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Growing Period and Image in grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Growing Period -->
                        <div>
                            <label for="growing_period" class="block text-sm font-semibold text-gray-700 mb-3">
                                Growing Period 
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="number" id="growing_period" name="growing_period" min="1" required
                                       placeholder="6"
                                       class="block w-full px-4 py-4 pr-16 text-base border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 bg-gray-50 hover:bg-white hover:border-gray-300">
                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                    <span class="text-gray-500 text-sm font-medium">months</span>
                                </div>
                            </div>
                            @error('growing_period')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <!-- Image Upload -->
                        <div>
                            <label for="image" class="block text-sm font-semibold text-gray-700 mb-3">
                                Crop Image
                                <span class="text-gray-400 font-normal">(Optional)</span>
                            </label>
                            <div class="relative">
                                <input type="file" id="image" name="image" accept="image/*"
                                       class="block w-full px-4 py-4 text-base border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 bg-gray-50 hover:bg-white hover:border-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                            </div>
                            @error('image')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
             
            </div>
            
            <!-- Modal footer -->
            <div class="bg-gray-50 px-8 py-6 border-t border-gray-100 flex flex-col sm:flex-row sm:justify-end sm:space-x-4 space-y-3 sm:space-y-0">
                <button type="button" onclick="document.getElementById('addCropModal').classList.add('hidden')"
                        class="inline-flex items-center justify-center px-6 py-3 border-2 border-gray-300 text-base font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 sm:w-auto w-full">
                    Cancel
                </button>
                <button type="submit" 
                
                        class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-semibold rounded-xl text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 sm:w-auto w-full">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Add Crop
                </button>
            </div>
               </form>
        </div>
    </div>
</div>