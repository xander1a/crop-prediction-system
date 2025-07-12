@include('layouts.app')

@section('title', 'Market Overview')

<div class="max-w-4xl mx-auto mt-10 p-6">
    <!-- Header Section -->
    <div class="bg-gradient-to-r from-green-600 to-green-700 rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-3xl font-bold text-white flex items-center">
            <span class="mr-3">📊</span>
            Crop Price Prediction Result
        </h2>
        <p class="text-green-100 mt-2">Comprehensive market analysis and price forecasting</p>
    </div>

    @if(session('error'))
        <div class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 rounded-lg mb-6 shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    {{ session('error') }}
                </div>
            </div>
        </div>
    @endif

    <!-- Main Results Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Predicted Price Card -->
        <div class="bg-white rounded-lg shadow-md border-l-4 border-green-500 p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Predicted Price</p>
                    <p class="text-2xl font-bold text-green-600">FR{{ number_format($result['predicted_price'], 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <span class="text-2xl">💰</span>
                </div>
            </div>
        </div>

        <!-- Confidence Score Card -->
        <div class="bg-white rounded-lg shadow-md border-l-4 border-green-400 p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Accurancy Score</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($result['confidence_score'], 2) }}%</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <span class="text-2xl">📈</span>
                </div>
            </div>
            <!-- Confidence Bar -->
            <div class="mt-3">
                <div class="bg-gray-200 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ $result['confidence_score'] }}%"></div>
                </div>
            </div>
        </div>

        <!-- Model Used Card -->
        <div class="bg-white rounded-lg shadow-md border-l-4 border-green-300 p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Model Used</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $result['model_used'] }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <span class="text-2xl">🤖</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Information Section -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-6 flex items-center">
            <span class="w-2 h-6 bg-green-500 rounded mr-3"></span>
            Prediction Details
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Time Information -->
            <div class="space-y-4">
                <div class="flex items-center p-4 bg-green-50 rounded-lg">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <span class="text-lg">📅</span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Prediction Date</p>
                        <p class="text-lg font-semibold text-gray-800">
                            {{ \Carbon\Carbon::parse($result['prediction_date'])->format('M d, Y H:i') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center p-4 bg-green-50 rounded-lg">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <span class="text-lg">🎯</span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Target Date</p>
                        <p class="text-lg font-semibold text-gray-800">{{ $result['target_date'] }}</p>
                    </div>
                </div>

                <div class="flex items-center p-4 bg-green-50 rounded-lg">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <span class="text-lg">🌾</span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Commodity ID</p>
                        <p class="text-lg font-semibold text-gray-800">{{ $result['commodity_id'] }}</p>
                    </div>
                </div>
            </div>

            <!-- Location Information -->
            <div class="space-y-4">
                <div class="flex items-center p-4 bg-green-50 rounded-lg">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <span class="text-lg">🗺️</span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Admin 1</p>
                        <p class="text-lg font-semibold text-gray-800">{{ $result['admin1'] ?? 'N/A' }}</p>
                    </div>
                </div>

                <div class="flex items-center p-4 bg-green-50 rounded-lg">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <span class="text-lg">📍</span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Admin 2</p>
                        <p class="text-lg font-semibold text-gray-800">{{ $result['admin2'] ?? 'N/A' }}</p>
                    </div>
                </div>

                <div class="flex items-center p-4 bg-green-50 rounded-lg">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <span class="text-lg">🏪</span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Market</p>
                        <p class="text-lg font-semibold text-gray-800">{{ $result['market'] ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>