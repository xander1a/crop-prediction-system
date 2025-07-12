@include('layouts.app')

<div class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 min-h-screen">
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-blue-300/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-green-300/20 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-purple-200/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative max-w-6xl mx-auto p-6">
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Average Price Card -->
  @include('card', [
    'icon' => '🌾',
    'title' => 'Average Price of ' . ($crop_name ?? 'Crop'),
    'value' => number_format($avgPrice, 2) . ' RWF',
    'color' => 'blue',
    'date' => $latest_date ?? now()->format('Y-m-d')
])

  </div>

        {{-- Chart --}}
        <div class="bg-white/80 backdrop-blur-sm rounded-xl p-6 shadow-lg border">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">📈 Market Price Trends</h2>
            <canvas id="marketTrendChart" height="100"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const ctx = document.getElementById('marketTrendChart').getContext('2d');
    const marketTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! $dates !!},
            datasets: {!! $datasets !!}
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Average Predicted Price per Market'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                },
                legend: {
                    display: true,
                    position: 'bottom'
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Predicted Price (RWF)'
                    }
                }
            }
        }
    });
</script>
