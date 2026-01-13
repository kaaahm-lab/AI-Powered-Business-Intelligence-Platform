<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MockRegionAiController extends Controller     
{
    public function analyze(Request $request)
    {
        $request->validate([
            'industry' => 'required|string|min:3',
            'k'        => 'nullable|integer|min:1|max:10',
        ]);

        return response()->json([
            "predicted_region" => "dubai",
            "confidence" => 0.701,
            "is_ambiguous" => true,
            "top_k" => [
                [
                    "region" => "dubai",
                    "confidence" => 0.701
                ],
                [
                    "region" => "abu dhabi",
                    "confidence" => 0.173
                ],
                [
                    "region" => "sharjah",
                    "confidence" => 0.071
                ]
            ]
        ]);
    }
}
