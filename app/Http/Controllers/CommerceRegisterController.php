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
            ->map(fn() => rand(1, $maxId))
            ->unique()
            ->take($limit);

        $data = CommerceRegister::whereIn('id', $ids)
            ->get([
                'id',
                'register_serial_number',
                'commerce_number',
                'company_name_ar',
                'company_name_en',
                'main_license_number',
                'commerce_register_type_code',
                'commerce_register_type_desc_ar',
                'commerce_register_type_desc_en',
                'legal_type_code',
                'legal_type_desc_ar',
                'legal_type_desc_en',
                'nationality_code',
                'nationality_desc_ar',
                'nationality_desc_en',
                'issue_date',
                'expiry_date',
                'cancel_date',
                'paid_up_capital',
                'nominated_capital',
            ]);

        return response()->json([
            'count' => $data->count(),
            'data' => $data
        ]);
    }
}
