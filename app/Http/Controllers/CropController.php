<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\Region;
use App\Models\Crop_price;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;

class CropController extends Controller
{
    public function index(Request $request)
    {
        // Get search query
        $search = $request->input('search');

        // Get all crops with basic statistics
        $crops = Crop::when($search, function ($query, $search) {
                return $query->where('name', 'like', '%' . $search . '%');
            })
            ->withCount('prices')
            ->with(['prices' => function($query) {
                $query->selectRaw('crop_id, AVG(price) as avg_price, MAX(price) as max_price, MIN(price) as min_price')
                    ->groupBy('crop_id');
            }])
            ->orderBy('name')
            ->paginate(12);

        return view('crops.index', [
            'crops' => $crops,
            'search' => $search
        ]);
    }

    public function show(Crop $crop)
    {
        // Load price history for this crop
        $priceHistory = Crop_price::where('crop_id', $crop->id)
            ->with('region')
            ->orderBy('date_recorded', 'desc')
            ->take(30)
            ->get();

        // Get price statistics by region
        $regions = Region::with(['prices' => function($query) use ($crop) {
                $query->where('crop_id', $crop->id)
                    ->selectRaw('region_id, AVG(price) as avg_price, MAX(price) as max_price, MIN(price) as min_price')
                    ->groupBy('region_id');
            }])
            ->has('prices')
            ->get();

        // Get similar crops
        $similarCrops = Crop::where('id', '!=', $crop->id)
            ->inRandomOrder()
            ->take(4)
            ->get();

        return view('crops.show', [
            'crop' => $crop,
            'priceHistory' => $priceHistory,
            'regions' => $regions,
            'similarCrops' => $similarCrops
        ]);
    }






public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255|unique:crops',
        'description' => 'nullable|string',
        'growing_period' => 'required|integer|min:1',
        'image' => 'nullable|image|max:2048',
    ]);

    $data = $validated;

    if ($request->hasFile('image')) {
        // Store image in storage/app/public/crops
        $path = $request->file('image')->store('crops', 'public');
        $data['image_url'] = 'storage/' . $path;
    }

    Crop::create($data);

    return redirect()->route('crops.index')
        ->with('success', 'Crop added successfully!');
}

}