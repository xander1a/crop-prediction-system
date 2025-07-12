<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\Region;
use App\Models\Crop_price;
use Illuminate\Http\Request;

class CropPriceController extends Controller
{
    public function index()
    {
        $cropPrices = Crop_price::with(['crop', 'region'])
            ->orderBy('date_recorded', 'desc')
            ->paginate(200);
            
            $crops = Crop::all();
            $regions = Region::all();
        
            return view('crop-prices.index', compact('cropPrices', 'crops', 'regions'));
    }

    public function create()
    {
        $crops = Crop::all();
        $regions = Region::all();
        return view('crop-prices.create', compact('crops', 'regions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'crop_id' => 'required|exists:crops,id',
            'region_id' => 'required|exists:regions,id',
            'price' => 'required|numeric|min:0',
            'date_recorded' => 'required|date',
            'source' => 'nullable|string|max:255',
            'wether_condition' => 'nullable|string|max:255',
        ]);

        Crop_price::create($validated);

        return redirect()->route('crop-prices.index')->with('success', 'Crop price added successfully.');
    }

    public function edit(Crop_price $cropPrice)
    {
        $crops = Crop::all();
        $regions = Region::all();
        return view('crop-prices.edit', compact('cropPrice', 'crops', 'regions'));
    }

    public function update(Request $request, Crop_price $cropPrice)
    {
        $validated = $request->validate([
            'crop_id' => 'required|exists:crops,id',
            'region_id' => 'required|exists:regions,id',
            'price' => 'required|numeric|min:0',
            'date_recorded' => 'required|date',
            'source' => 'nullable|string|max:255',
        ]);

        $cropPrice->update($validated);

        return redirect()->route('crop-prices.index')->with('success', 'Crop price updated successfully.');
    }

    public function destroy(Crop_price $cropPrice)
    {
        $cropPrice->delete();
        return redirect()->route('crop-prices.index')->with('success', 'Crop price deleted successfully.');
    }
}