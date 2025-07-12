@include('layouts.app')


<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Crop Info -->
    <div class="bg-white rounded-xl shadow-md p-6 lg:col-span-2">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold flex items-center">
            <svg class="w-6 h-6 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
            </svg>
            {{ $crop->name }}
        </h1>
        @if($crop->description)
        <p class="text-gray-600 mt-1">{{ $crop->description }}</p>
        @endif
    </div>

    <!-- Train Button -->
    <a href="{{ route('train.model', ['cropId' => $crop->id]) }}"
       class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
        Train Price Prediction Model
    </a>
</div>

<!-- Prediction Form -->
<form method="POST" action="{{ route('predict.price', $crop->id) }}" class="mt-4 space-y-4 bg-gray-100 p-4 rounded shadow">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Select Region</label>
            <select name="region_id" required class="w-full mt-1 px-3 py-2 border rounded">
                @foreach ($regions as $region)
                    <option value="{{ $region->id }}">{{ $region->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Target Year</label>
            <input type="number" name="target_year" min="{{ date('Y') }}" value="{{ date('Y') + 1 }}" class="w-full mt-1 px-3 py-2 border rounded" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Weather Condition</label>
            <select name="weather_condition" required class="w-full mt-1 px-3 py-2 border rounded">
                <option value="excellent">Excellent</option>
                <option value="good">Good</option>
                <option value="fair">Fair</option>
                <option value="poor">Poor</option>
                <option value="very_poor">Very Poor</option>
                <option value="drought">Drought</option>
                <option value="flood">Flood</option>
            </select>
        </div>
    </div>
    <button type="submit"
        class="mt-4 bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">
        Predict and Save Price
    </button>
</form>



@if (session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif



        <!-- Price History Chart -->
        <div class="mb-8">
            <h2 class="text-lg font-semibold mb-4 flex items-center">
                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18v4H3V4z"></path>
                </svg>
                Price History (Last 30 Records)
            </h2>
            <div class="h-64">
                <canvas id="priceHistoryChart"></canvas>
            </div>
        </div>

        <!-- Regional Price Distribution -->
        <div>
            <h2 class="text-lg font-semibold mb-4 flex items-center">
                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Regional Price Distribution
            </h2>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Region</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Price</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price Range</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($regions as $region)
                        @if($region->prices->isNotEmpty())
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $region->name }}</td>
                            <td class="px-4 py-3 whitespace-nowrap font-medium">{{ number_format($region->prices[0]->avg_price, 2) }} RWF</td>
                            <td class="px-4 py-3 whitespace-nowrap">
    <div class="flex items-center">
        <span class="text-sm text-gray-500 mr-2">{{ number_format($region->prices[0]->min_price, 2) }}</span>
        <div class="flex-1 bg-gray-200 rounded-full h-2">
            @php
                $min = $region->prices[0]->min_price;
                $max = $region->prices[0]->max_price;
                $avg = $region->prices[0]->avg_price;
                $range = $max - $min;
                $percentage = $range != 0 ? (($avg - $min) / $range) * 100 : 0;
            @endphp
            <div class="bg-green-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
        </div>
        <span class="text-sm text-gray-500 ml-2">{{ number_format($region->prices[0]->max_price, 2) }}</span>
    </div>
</td>

                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Crop Details -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-lg font-semibold mb-4 flex items-center">
                <svg class="w-5 h-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Crop Details
            </h2>
            
            <div class="space-y-3">
                @if($crop->growing_period)
                <div class="flex justify-between">
                    <span class="text-gray-600">Growing Period</span>
                    <span class="font-medium">{{ $crop->growing_period }} months</span>
                </div>
                @endif
                
                <div class="flex justify-between">
                    <span class="text-gray-600">Price Records</span>
                    <span class="font-medium">{{ $crop->prices_count }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-600">First Record</span>
                    <span class="font-medium">
                        @if($crop->prices_count > 0)
                            {{ $priceHistory->last()->date_recorded->format('M Y') }}
                        @else
                            N/A
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- Similar Crops -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-lg font-semibold mb-4 flex items-center">
                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                </svg>
                Similar Crops
            </h2>
            
            <div class="space-y-3">
                @foreach($similarCrops as $similar)
                <a href="{{ route('crops.show', $similar) }}" class="block p-3 hover:bg-gray-50 rounded-lg transition-colors">
                    <div class="flex items-center justify-between">
                        <span class="font-medium">{{ $similar->name }}</span>
                        <span class="text-sm text-gray-500">{{ $similar->prices_count }} records</span>
                    </div>
                </a>
                @endforeach
            </div>
        </div>

        <!-- Recent Price Updates -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-lg font-semibold mb-4 flex items-center">
                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Recent Prices
            </h2>
            
            <div class="space-y-3">
                @forelse($priceHistory->take(5) as $price)
                <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg">
                    <div>
                        <p class="font-medium">{{ number_format($price->price, 2) }} RWF</p>
                        <p class="text-xs text-gray-500">{{ $price->region->name }} • {{ $price->date_recorded->format('M d, Y') }}</p>
                    </div>
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
                @empty
                <p class="text-gray-500 text-center py-4">No price records available</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Price History Chart
    const priceHistoryCtx = document.getElementById('priceHistoryChart').getContext('2d');
    new Chart(priceHistoryCtx, {
        type: 'line',
        data: {
            labels: @json($priceHistory->pluck('date_recorded')->map(fn($date) => $date->format('M d'))->reverse()),
            datasets: [{
                label: 'Price (RWF)',
                data: @json($priceHistory->pluck('price')->reverse()),
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.05)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: false,
                    title: {
                        display: true,
                        text: 'Price (RWF)'
                    }
                }
            }
        }
    });
</script>
