@props(['icon', 'title', 'value', 'color'])

<div class="group bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 border border-white/20 hover:scale-105 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-r from-{{ $color }}-500/5 to-{{ $color }}-600/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
    <div class="relative">
        <div class="flex items-center justify-between mb-4">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-gradient-to-r from-{{ $color }}-500 to-{{ $color }}-600 rounded-xl group-hover:scale-110 transition-transform duration-300">
                <span class="text-white text-xl">{{ $icon }}</span>
            </div>
            <div class="text-xs bg-{{ $color }}-100 text-{{ $color }}-700 px-2 py-1 rounded-full">
                {{ $date ?? now()->format('Y-m-d') }}
            </div>
        </div>
        <h3 class="text-sm font-medium text-gray-500 mb-2">{{ $title }}</h3>
        <p class="text-2xl font-bold text-gray-800 group-hover:text-{{ $color }}-600 transition-colors duration-300">
            {{ $value }}
        </p>
        <p class="text-xs text-gray-400 mt-1">Price Prediction</p>
    </div>
</div>

