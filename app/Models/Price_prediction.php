<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Price_prediction extends Model
{
    protected $fillable = [
        'crop_id',
        'region_id',
        'predicted_price',
        'confidence_score',
        'prediction_date',
        'target_date',
        'model_used',
        'accuracy',

    ];


  protected $casts = [
        'predicted_price' => 'decimal:2',
        'confidence_score' => 'decimal:2',
        'accuracy' => 'decimal:2',  // Cast accuracy as decimal
        'prediction_date' => 'datetime',
        'target_date' => 'date',
    ];



      public function crop()
    {
        return $this->belongsTo(Crop::class);
    }

    /**
     * Get the region that owns the prediction
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Scope for predictions with high confidence
     */
    public function scopeHighConfidence($query, $threshold = 70)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    /**
     * Scope for recent predictions
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for future predictions
     */
    public function scopeFuture($query)
    {
        return $query->where('target_date', '>', now());
    }

    /**
     * Get the price change percentage
     */
    public function getPriceChangePercentageAttribute()
    {
        if (!$this->current_price || $this->current_price == 0) {
            return 0;
        }

        return round((($this->predicted_price - $this->current_price) / $this->current_price) * 100, 2);
    }

    /**
     * Check if prediction shows price increase
     */
    public function getIsPriceIncreaseAttribute()
    {
        return $this->predicted_price > ($this->current_price ?? 0);
    }

    /**
     * Get confidence level as text
     */
    public function getConfidenceLevelAttribute()
    {
        if ($this->confidence_score >= 80) {
            return 'Very High';
        } elseif ($this->confidence_score >= 60) {
            return 'High';
        } elseif ($this->confidence_score >= 40) {
            return 'Medium';
        } else {
            return 'Low';
        }
    }

    /**
     * Get days until target date
     */
    public function getDaysUntilTargetAttribute()
    {
        return now()->diffInDays($this->target_date, false);
    }
}
