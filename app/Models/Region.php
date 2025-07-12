<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    //
    protected $fillable = [
        'name',
        'description',
    ];



    public function predictions()
    {
        return $this->hasMany(Price_prediction::class);
    }

    /**
     * Get the crop prices for the region
     */
    public function prices()
    {
        return $this->hasMany(Crop_price::class);
    }

    /**
     * Scope for active regions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the latest price for a specific crop in this region
     */
    public function getLatestCropPrice($cropId)
    {
        return $this->prices()
            ->where('crop_id', $cropId)
            ->orderBy('date_recorded', 'desc')
            ->first();
    }

    /**
     * Get recent predictions for this region
     */
    public function getRecentPredictions($cropId = null, $days = 30)
    {
        $query = $this->predictions()
            ->where('created_at', '>=', now()->subDays($days));

        if ($cropId) {
            $query->where('crop_id', $cropId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get all crops that have prices in this region
     */
    public function availableCrops()
    {
        return Crop::whereHas('prices', function ($query) {
            $query->where('region_id', $this->id);
        })->get();
    }

     public function price_Predictions()
    {
        return $this->hasMany(Price_prediction::class);
    }
}
