@extends('layouts.app')

@section('title', 'Market Analysis')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold flex items-center">
        <svg class="w-6 h-6 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
        </svg>
        Market Analysis ({{ $timePeriod }})
    </h1>
    <p class="text-gray-600">Detailed insights into crop prices and regional variations</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Regional Price Analysis -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-semibold mb-6 flex items-center">
            <svg class="w-6 h-6 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Regional Price Analysis
        </h2>
        
        <div class="space-y-4">
            @foreach($regionalAnalysis as $region)
            @if($region->prices->isNotEmpty())
            <div>
                <h3 class="font-medium">{{ $region->name }}</h3>
                <div class="flex items-center mt-1">
                    <span class="text-sm text-gray-500 mr-2">Avg:</span>
                    <span class="font-bold">{{ number_format($region->prices->first()->avg_price, 2) }} RWF</span>
                    <div class="ml-auto text-sm text-gray-500">
                        {{ round(($region->prices->first()->avg_price / $regionalAnalysis->first()->prices->first()->avg_price) * 100) }}% of highest
                    </div>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                    <div class="bg-blue-600 h-2 rounded-full" 
                         style="width: {{ ($region->prices->first()->avg_price / $regionalAnalysis->first()->prices->first()->avg_price) * 100 }}%"></div>
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>
    
    <!-- Crop Stability Analysis -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-semibold mb-6 flex items-center">
            <svg class="w-6 h-6 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            Crop Stability Index
        </h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Crop</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Price</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stability</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($cropPerformance as $crop)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $crop['crop']->name }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ number_format($crop['avg_price'], 2) }} RWF</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-24 bg-gray-200 rounded-full h-2.5 mr-2">
                                    <div class="h-2.5 rounded-full {{ $crop['stability'] > 30 ? 'bg-red-600' : ($crop['stability'] > 15 ? 'bg-yellow-500' : 'bg-green-600') }}" 
                                         style="width: {{ min($crop['stability'], 100) }}%"></div>
                                </div>
                                <span class="text-sm {{ $crop['stability'] > 30 ? 'text-red-600' : ($crop['stability'] > 15 ? 'text-yellow-600' : 'text-green-600') }}">
                                    {{ round($crop['stability']) }}%
                                </span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection