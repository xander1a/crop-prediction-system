<?php

namespace App\Http\Controllers;

use App\Models\User_alerts;
use App\Models\Crop;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index()
    {
        $alerts = auth()->user()->alerts()
            ->with('crop')
            ->latest()
            ->paginate(10);

        $crops = Crop::has('prices')
            ->orderBy('name')
            ->get();

        return view('alerts.index', [
            'alerts' => $alerts,
            'crops' => $crops,
            'priceDirections' => [
                'above' => 'Price Rises Above',
                'below' => 'Price Falls Below'
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'crop_id' => 'required|exists:crops,id',
            'price_threshold' => 'required|numeric|min:0',
            'direction' => 'required|in:above,below',
            'notification_method' => 'required|in:email,app,both'
        ]);

        $alert = auth()->user()->alerts()->create($validated);

        return redirect()
            ->route('alerts.index')
            ->with('success', 'Alert created successfully!');
    }

    public function destroy(User_alerts $alert)
    {
        $this->authorize('delete', $alert);
        
        $alert->delete();

        return redirect()
            ->route('alerts.index')
            ->with('success', 'Alert deleted successfully!');
    }

    public function markAsRead(User_alerts $alert)
    {
        $this->authorize('update', $alert);

        $alert->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }
}