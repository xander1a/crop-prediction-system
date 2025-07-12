@extends('layouts.app')

@section('title', 'Market Trends')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Price Trends Chart -->
    <div class="bg-white rounded-xl shadow-md p-6 lg:col-span-2">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold flex items-center">
                <svg class="w-6 h-6 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18v4H3V4z"></path>
                </svg>
                Price Trends ({{ $dateRange['start'] }} - {{ $dateRange['end'] }})
            </h2>
            <div class="flex space-x-2">
                <button class="px-3 py-1 text-xs bg-green-100 text-green-800 rounded-full">30 Days</button>
                <button class="px-3 py-1 text-xs bg-gray-100 text-gray-600 rounded-full">90 Days</button>
                <button class="px-3 py-1 text-xs bg-gray-100 text-gray-600 rounded-full">1 Year</button>
            </div>
        </div>
        
        <div class="h-80">
            <canvas id="priceTrendsChart"></canvas>
        </div>
    </div>
    
    <!-- Most Volatile Crops -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-semibold mb-6 flex items-center">
            <svg class="w-6 h-6 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
            </svg>
            Most Volatile Crops
        </h2>
        
        <div class="space-y-4">
            @foreach($volatileCrops as $crop)
            <div class="border border-gray-100 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-medium">{{ $crop->crop->name }}</h3>
                    <span class="text-sm font-bold {{ $crop->price_range > 1000 ? 'text-red-600' : 'text-yellow-600' }}">
                        {{ number_format($crop->price_range, 2) }} RWF
                    </span>
                </div>
                <div class="flex items-center justify-between text-sm text-gray-500">
                    <span>Low: {{ number_format($crop->avg_price - ($crop->price_range/2), 2) }}</span>
                    <span>Avg: {{ number_format($crop->avg_price, 2) }}</span>
                    <span>High: {{ number_format($crop->avg_price + ($crop->price_range/2), 2) }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const trendsCtx = document.getElementById('priceTrendsChart').getContext('2d');
    new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: @json($trends->pluck('date')->map(fn($date) => Carbon\Carbon::parse($date)->format('M d'))),
            datasets: [{
                label: 'Average Price (RWF)',
                data: @json($trends->pluck('avg_price')),
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.3
            }, {
                label: 'Crops Tracked',
                data: @json($trends->pluck('crop_count')),
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.3,
                yAxisID: 'y1'
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
                        text: 'Average Price (RWF)'
                    }
                },
                y1: {
                    position: 'right',
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Crops Tracked'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
</script>
@endpush
@endsection