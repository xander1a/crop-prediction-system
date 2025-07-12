@include('layouts.app')

@section('title', 'Market Overview')

<div class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 min-h-screen">
    <!-- Background decoration -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-r from-blue-300/20 to-indigo-300/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-gradient-to-r from-green-300/20 to-emerald-300/20 rounded-full blur-3xl"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
       

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="bg-gradient-to-r from-emerald-50 to-green-50 border-l-4 border-emerald-400 text-emerald-800 p-4 rounded-lg shadow-sm mb-8">
                <div class="flex items-center">
                    <span class="text-emerald-500 mr-2 text-xl">✓</span>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            </div>
        @elseif(session('error'))
            <div class="bg-gradient-to-r from-red-50 to-rose-50 border-l-4 border-red-400 text-red-800 p-4 rounded-lg shadow-sm mb-8">
                <div class="flex items-center">
                    <span class="text-red-500 mr-2 text-xl">⚠</span>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
            </div>
        @endif

   
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <!-- Total Predictions Card -->




            
            <div class="group bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 text-center border border-white/20 hover:scale-105">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl mb-4 group-hover:scale-110 transition-transform duration-300">
                    <span class="text-white text-xl">📈</span>
                </div>
                <p class="text-gray-500 text-sm font-medium mb-2">Total Predictions</p>
                <h3 class="text-3xl font-bold text-gray-800 group-hover:text-blue-600 transition-colors duration-300">
                    {{ $predictions->count() }}
                </h3>
                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-3">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-1.5 rounded-full w-3/4 transition-all duration-1000"></div>
                </div>
            </div>

            <!-- Average Price Card -->
            <div class="group bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 text-center border border-white/20 hover:scale-105">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl mb-4 group-hover:scale-110 transition-transform duration-300">
                    <span class="text-white text-xl">💰</span>
                </div>
                <p class="text-gray-500 text-sm font-medium mb-2">Average Price</p>
                <h3 class="text-3xl font-bold text-gray-800 group-hover:text-green-600 transition-colors duration-300">
                    {{ number_format($predictions->avg('predicted_price'), 2) }}
                </h3>
                <p class="text-xs text-gray-400 mt-1">RWF per kg</p>
                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-3">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 h-1.5 rounded-full w-4/5 transition-all duration-1000"></div>
                </div>
            </div>

            <!-- Confidence Score Card -->
            <div class="group bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 text-center border border-white/20 hover:scale-105">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-gradient-to-r from-purple-500 to-violet-600 rounded-xl mb-4 group-hover:scale-110 transition-transform duration-300">
                    <span class="text-white text-xl">🎯</span>
                </div>
                <p class="text-gray-500 text-sm font-medium mb-2">Avg Confidence Score</p>
                <h3 class="text-3xl font-bold text-gray-800 group-hover:text-purple-600 transition-colors duration-300">
                    {{ number_format($predictions->avg('confidence_score'), 2) }}%
                </h3>
                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-3">
                    <div class="bg-gradient-to-r from-purple-500 to-violet-600 h-1.5 rounded-full transition-all duration-1000" style="width: {{ min($predictions->avg('confidence_score'), 100) }}%"></div>
                </div>
            </div>

            <!-- Markets Predicted Card -->
            <div class="group bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 text-center border border-white/20 hover:scale-105">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-gradient-to-r from-orange-500 to-red-500 rounded-xl mb-4 group-hover:scale-110 transition-transform duration-300">
                    <span class="text-white text-xl">🏪</span>
                </div>
                <p class="text-gray-500 text-sm font-medium mb-2">Markets Predicted</p>
                <h3 class="text-3xl font-bold text-gray-800 group-hover:text-orange-600 transition-colors duration-300">
                    {{ $predictions->pluck('market')->unique()->count() }}
                </h3>
                <p class="text-xs text-gray-400 mt-1">Active markets</p>
                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-3">
                    <div class="bg-gradient-to-r from-orange-500 to-red-500 h-1.5 rounded-full w-5/6 transition-all duration-1000"></div>
                </div>
            </div>
        </div>

        {{-- Latest Predictions --}}
        <div class="bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg border border-white/20 p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                    <div class="inline-flex items-center justify-center w-10 h-10 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl">
                        <span class="text-white text-lg">🕒</span>
                    </div>
                    <h4 class="text-2xl font-bold text-gray-800">Latest Predictions</h4>
                </div>
                <div class="hidden sm:flex items-center space-x-2 text-sm text-gray-500">
                    <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                    <span>Live Updates</span>
                </div>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($predictions->sortByDesc('prediction_date')->take(6) as $prediction)
                    <div class="group relative bg-gradient-to-br from-white to-gray-50 border border-gray-200/50 rounded-xl p-6 shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                        <!-- Date Badge -->
                        <div class="absolute -top-2 -right-2 bg-gradient-to-r from-blue-500 to-indigo-600 text-white text-xs px-3 py-1 rounded-full shadow-lg">
                            {{ \Carbon\Carbon::parse($prediction->prediction_date)->format('M d') }}
                        </div>
                        
                        <!-- Time -->
                        <p class="text-gray-400 text-xs mb-3 font-medium">
                            {{ \Carbon\Carbon::parse($prediction->prediction_date)->format('H:i') }}
                        </p>
                        
                        <!-- Market Name -->
                        <h5 class="text-lg font-bold text-gray-800 mb-3 group-hover:text-indigo-600 transition-colors duration-300">
                            🏪 {{ $prediction->market }}
                        </h5>
                        
                        <!-- Price Display -->
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-3 mb-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 font-medium">Predicted Price</span>
                                <span class="text-xl font-bold text-green-600">
                                    {{ number_format($prediction->predicted_price, 2) }}
                                </span>
                            </div>
                            <span class="text-xs text-gray-500">RWF</span>
                        </div>
                        
                        <!-- Confidence & Model -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Confidence</span>
                                <span class="text-sm font-semibold text-purple-600">
                                    {{ number_format($prediction->confidence_score, 1) }}%
                                </span>
                            </div>
                            
                            <!-- Confidence Bar -->
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-purple-500 to-violet-600 h-2 rounded-full transition-all duration-1000" 
                                     style="width: {{ min($prediction->confidence_score, 100) }}%"></div>
                            </div>
                            
                            <div class="flex items-center justify-between mt-3">
                                <span class="text-sm text-gray-600">Crop</span>
                                <span class="text-xs bg-gray-100 px-2 py-1 rounded-full font-medium text-gray-700">
                                    {{ $prediction->crop_name }}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Hover Effect Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-500/5 to-indigo-500/5 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                @endforeach
            </div>
            
            @if($predictions->count() == 0)
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                        <span class="text-2xl">📊</span>
                    </div>
                    <h3 class="text-lg font-medium text-gray-600 mb-2">No predictions available</h3>
                    <p class="text-gray-500">Start making predictions to see them appear here.</p>
                </div>
            @endif
        </div>
    </div>
</div>