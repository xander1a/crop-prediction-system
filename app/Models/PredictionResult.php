<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PredictionResult extends Model
{
    //
    protected $fillable = [
        'predicted_price',
        'confidence_score',
        'model_used',
        'prediction_date',
        'target_date',
        'commodity_id',
        'admin1',
        'admin2',
        'market',
        'crop_name',

    ];
}
