@include('layouts.app')


<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Edit Crop Price</h1>
    
    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('crop-prices.update', $cropPrice->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label for="crop_id" class="block text-sm font-medium text-gray-700">Crop</label>
                <select 
                    id="crop_id" 
                    name="crop_id" 
                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                    required
                >
                    <option value="">Select Crop</option>
                    @foreach($crops as $crop)
                        <option value="{{ $crop->id }}" {{ $cropPrice->crop_id == $crop->id ? 'selected' : '' }}>{{ $crop->name }}</option>
                    @endforeach
                </select>
                @error('crop_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-4">
                <label for="region_id" class="block text-sm font-medium text-gray-700">Region</label>
                <select 
                    id="region_id" 
                    name="region_id" 
                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                    required
                >
                    <option value="">Select Region</option>
                    @foreach($regions as $region)
                        <option value="{{ $region->id }}" {{ $cropPrice->region_id == $region->id ? 'selected' : '' }}>{{ $region->name }}</option>
                    @endforeach
                </select>
                @error('region_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-4">
                <label for="price" class="block text-sm font-medium text-gray-700">Price</label>
                <input 
                    type="number" 
                    id="price" 
                    name="price" 
                    step="0.01" 
                    min="0" 
                    value="{{ old('price', $cropPrice->price) }}"
                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                    required
                >
                @error('price')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-4">
                <label for="date_recorded" class="block text-sm font-medium text-gray-700">Date</label>
                <input 
                    type="date" 
                    id="date_recorded" 
                    name="date_recorded" 
                    value="{{ old('date_recorded', $cropPrice->date_recorded->format('Y-m-d')) }}"
                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                    required
                >
                @error('date_recorded')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-4">
                <label for="source" class="block text-sm font-medium text-gray-700">Source (Optional)</label>
                <input 
                    type="text" 
                    id="source" 
                    name="source" 
                    value="{{ old('source', $cropPrice->source) }}"
                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                >
                @error('source')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="flex items-center justify-end">
                <a 
                    href="{{ route('crop-prices.index') }}" 
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-2"
                >
                    Cancel
                </a>
                <button 
                    type="submit" 
                    class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                >
                    Update Price
                </button>
            </div>
        </form>
    </div>
</div>
