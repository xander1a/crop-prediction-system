<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Crop extends Model
{
    //

    protected $fillable = [
        'name',
        'description',
        'image_url',
        'growing_period',

    ];

    public function prices()
    {
        return $this->hasMany(Crop_price::class);
    }


     public function predictions()
    {
        return $this->hasMany(Price_prediction::class);
    }

  

    /**
     * Scope for active crops
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the latest price for this crop in a specific region
     */
    public function getLatestPrice($regionId)
    {
        return $this->prices()
            ->where('region_id', $regionId)
            ->orderBy('date_recorded', 'desc')
            ->first();
    }

    /**
     * Get recent predictions for this crop
     */
    public function getRecentPredictions($regionId = null, $days = 30)
    {
        $query = $this->predictions()
            ->where('created_at', '>=', now()->subDays($days));

        if ($regionId) {
            $query->where('region_id', $regionId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

     public function pricePredictions()
    {
        return $this->hasMany(Price_prediction::class);
    }
}
