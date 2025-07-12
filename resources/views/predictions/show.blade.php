@include('layouts.app')

@section('title', 'Prediction Details')

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-xl font-semibold text-gray-800">Prediction Details</h2>
    </div>

    <div class="px-6 py-4 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h3 class="text-lg font-medium mb-4">Basic Information</h3>
            <div class="space-y-4">
                <div>
                    <p class="text-sm text-gray-500">Crop</p>
                    <p class="font-medium">{{ $prediction->crop->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Region</p>
                    <p class="font-medium">{{ $prediction->region->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Prediction Date</p>
                    <p class="font-medium">{{ $prediction->prediction_date->format('M d, Y') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Target Date</p>
                    <p class="font-medium">{{ $prediction->target_date->format('M d, Y') }}</p>
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-lg font-medium mb-4">Price Information</h3>
            <div class="space-y-4">
                <div>
                    <p class="text-sm text-gray-500">Current Price</p>
                    <p class="font-medium">{{ number_format($prediction->current_price, 2) }} RWF</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Predicted Price</p>
                    <p class="font-medium text-{{ $prediction->predicted_price > $prediction->current_price ? 'green' : 'red' }}-600">
                        {{ number_format($prediction->predicted_price, 2) }} RWF
                        @if($prediction->current_price > 0)
                            @if($prediction->predicted_price > $prediction->current_price)
                                (↑ {{ number_format((($prediction->predicted_price - $prediction->current_price)/$prediction->current_price)*100, 2) }}%)
                            @else
                                (↓ {{ number_format((($prediction->current_price - $prediction->predicted_price)/$prediction->current_price)*100, 2) }}%)
                            @endif
                        @else
                            <span class="text-gray-500">(N/A)</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
        <div class="flex justify-between items-center">
            <a href="{{ route('predictions.index') }}" class="text-gray-600 hover:text-gray-900">← Back to predictions</a>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Historical price chart
    const histCtx = document.getElementById('historicalChart')?.getContext('2d');

    @if(isset($recentPredictions) && $recentPredictions->count() > 0)
        const chartLabels = @json($recentPredictions->pluck('created_at')->map(fn($date) => \Carbon\Carbon::parse($date)->format('M d')));
        const chartData = @json($recentPredictions->pluck('predicted_price'));
    @else
        const chartLabels = ['Jan 1', 'Jan 5', 'Jan 10', 'Jan 15', 'Jan 20'];
        const chartData = [100, 105, 98, 110, {{ $prediction->current_price ?? 100 }}];
    @endif

    if (histCtx) {
        new Chart(histCtx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'Historical Prices',
                        data: chartData,
                        borderColor: '#065f46',
                        backgroundColor: 'rgba(6, 95, 70, 0.1)',
                        fill: true,
                        tension: 0.1
                    },
                    {
                        label: 'Predicted Price',
                        data: Array(chartLabels.length - 1).fill(null).concat([{{ $prediction->predicted_price }}]),
                        borderColor: '#b91c1c',
                        borderDash: [5, 5],
                        backgroundColor: 'transparent',
                        pointBackgroundColor: '#b91c1c',
                        pointRadius: 5
                    }
                ]
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
    }
</script>
@endpush
