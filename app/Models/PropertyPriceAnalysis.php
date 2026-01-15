<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyPriceAnalysis extends Model
{
    use HasFactory;
    protected $fillable = [
        'idea_id',
        'type',
        'region',
        'furnishing_status',
        'size_description',

        'price_min',
        'price_max',
        'price_unit',
        'price_text',
        'price_label',
        'price_confidence',
        'price_top_k',

        'size_min',
        'size_max',
        'size_unit',
        'size_text',
        'size_label',
        'size_confidence',
        'size_top_k',
    ];

    protected $casts = [
        'price_top_k' => 'array',
        'size_top_k'  => 'array',
    ];

    public function idea()
    {
        return $this->belongsTo(Idea::class);
    }
}
