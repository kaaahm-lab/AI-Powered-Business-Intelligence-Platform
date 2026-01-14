<?php

namespace App\Http\Controllers;

use App\Models\CommerceRegister;
use Illuminate\Routing\Controller;

class CommerceRegisterController extends Controller
{
    /**
     * عينات عشوائية (افتراضي 100)
     * GET /api/commerce-registers/samples
     */
    public function samples()
    {
        $limit = 100;

        $maxId = CommerceRegister::max('id');

        if (!$maxId) {
            return response()->json([
                'count' => 0,
                'data' => []
            ]);
        }

        // توليد IDs عشوائية
        $ids = collect(range(1, $limit * 2))
            ->map(fn () => rand(1, $maxId))
            ->unique()
            ->take($limit);

        $data = CommerceRegister::whereIn('id', $ids)
            ->get([
                'id',
                'company_name_ar',
                'company_name_en',
                'commerce_number',
                'nationality_desc_ar',
                'legal_type_desc_ar',
                'issue_date',
            ]);

        return response()->json([
            'count' => $data->count(),
            'data' => $data
        ]);
    }
}
