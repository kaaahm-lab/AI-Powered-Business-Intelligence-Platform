<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegionAnalysis extends Model
{
    protected $fillable = [
        'idea_id',
        'predicted_region',
        'confidence',
        'is_ambiguous',
        'top_k',
    ];

    protected $casts = [
        'top_k' => 'array',
        'is_ambiguous' => 'boolean',
        'confidence' => 'float',
    ];

    public function idea()
    {
        return $this->belongsTo(Idea::class);
    }
}
