<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Crop_price extends Model
{
    protected $fillable = [
        'crop_id',
        'region_id',
        'price',
        'date_recorded',
        'source',
        'wether_condition',
    ];

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    protected $casts = [
        'date_recorded' => 'datetime',
    ];


    /**
     * Scope for recent prices
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('date_recorded', '>=', now()->subDays($days));
    }

    /**
     * Scope for verified prices
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope for specific date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date_recorded', [$startDate, $endDate]);
    }

    /**
     * Get price trend compared to previous record
     */
    public function getPriceTrendAttribute()
    {
        $previousPrice = static::where('crop_id', $this->crop_id)
            ->where('region_id', $this->region_id)
            ->where('date_recorded', '<', $this->date_recorded)
            ->orderBy('date_recorded', 'desc')
            ->first();

        if (!$previousPrice) {
            return 'stable';
        }

        if ($this->price > $previousPrice->price) {
            return 'increasing';
        } elseif ($this->price < $previousPrice->price) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }

    /**
     * Get percentage change from previous price
     */
    public function getPriceChangePercentageAttribute()
    {
        $previousPrice = static::where('crop_id', $this->crop_id)
            ->where('region_id', $this->region_id)
            ->where('date_recorded', '<', $this->date_recorded)
            ->orderBy('date_recorded', 'desc')
            ->first();

        if (!$previousPrice || $previousPrice->price == 0) {
            return 0;
        }

        return round((($this->price - $previousPrice->price) / $previousPrice->price) * 100, 2);
    }

}
