<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Idea extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
    ];

    // العلاقة مع المستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // العلاقة مع المنافسين
    public function competitors()
    {
        return $this->hasMany(Competitor::class);
    }

    // العلاقة مع التوصيات
    public function recommendations()
    {
        return $this->hasMany(Recommendation::class);
    }

    // العلاقة مع التحليل المالي
    public function financialEstimation()
    {
        return $this->hasOne(FinancialEstimation::class);
    }

    // العلاقة مع تقرير التحليل
    public function report()
    {
        return $this->hasOne(AnalysisReport::class, 'idea_id');
    }

    public function swot()
{
    return $this->hasOne(SwotAnalysis::class);
}
public function regionAnalysis()
{
    return $this->hasOne(RegionAnalysis::class);
}

}
