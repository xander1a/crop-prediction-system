<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CropPricePredictionService;
use App\Models\Price_prediction; // Make sure this matches your actual model name
use App\Models\Crop;
use App\Models\Region;
use App\Models\PredictionResult;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Models\Crop_price; // Make sure this matches your actual model name
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class PredictionController extends Controller
{
    protected $predictionService;

    public function __construct(CropPricePredictionService $predictionService)
    {
        $this->predictionService = $predictionService;
    }

   public function index(Request $request)
    {
        $query = PredictionResult::query();
        
        // Add search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('crop_name', 'like', "%{$search}%")
                  ->orWhere('market', 'like', "%{$search}%")
                  ->orWhere('admin1', 'like', "%{$search}%")
                  ->orWhere('admin2', 'like', "%{$search}%")
                  ->orWhere('model_used', 'like', "%{$search}%");
            });
        }
        
        // Add filtering by crop
        if ($request->filled('crop')) {
            $query->where('crop_name', $request->get('crop'));
        }
        
        // Add filtering by market
        if ($request->filled('market')) {
            $query->where('market', $request->get('market'));
        }
        
        // Add filtering by date range
        if ($request->filled('date_from')) {
            $query->whereDate('target_date', '>=', $request->get('date_from'));
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('target_date', '<=', $request->get('date_to'));
        }
        
        // Order by latest predictions first
        $predictions = $query->orderBy('prediction_date', 'desc')
                           ->paginate(15)
                           ->withQueryString();
        
        // Get unique values for filter dropdowns
        $crops = PredictionResult::distinct()->pluck('crop_name')->sort();
        $markets = PredictionResult::distinct()->pluck('market')->sort();
        
        return view('predictions.index', compact('predictions', 'crops', 'markets'));
    }



    public function downloadPDF()
    {
        $predictionResults = PredictionResult::orderByDesc('prediction_date')->get();
        
        $data = [
            'title' => 'Prediction Results Report',
            'date' => now()->format('Y-m-d H:i:s'),
            'predictionResults' => $predictionResults,
            'totalRecords' => $predictionResults->count()
        ];

        $pdf = PDF::loadView('reports.prediction-results-pdf', $data)
                  ->setPaper('a4', 'landscape');
        
        return $pdf->download('prediction-results-report-' . now()->format('Y-m-d') . '.pdf');
    }




    
}

