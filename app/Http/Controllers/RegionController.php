<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\Crop_price;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    public function index()
    {
        $regions = Region::withCount('prices')
            ->orderBy('name')
            ->paginate(12);

        return view('regions.index', [
            'regions' => $regions
        ]);
    }

    public function show(Region $region)
    {
        // Get recent prices for this region
        $recentPrices = Crop_price::with('crop')
            ->where('region_id', $region->id)
            ->orderBy('date_recorded', 'desc')
            ->take(10)
            ->get();

        // Get top crops in this region
        $topCrops = Crop_price::selectRaw('crop_id, AVG(price) as avg_price')
            ->with('crop')
            ->where('region_id', $region->id)
            ->groupBy('crop_id')
            ->orderByDesc('avg_price')
            ->take(5)
            ->get();

        return view('regions.show', [
            'region' => $region,
            'recentPrices' => $recentPrices,
            'topCrops' => $topCrops
        ]);
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255|unique:regions',
        'description' => 'nullable|string',
    ]);

    $region = Region::create($validated);

    return redirect()->route('regions.index')
        ->with('success', 'Region added successfully!');
}
}