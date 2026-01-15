<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MockAiController_pricePrediction extends Controller
{
    public function pricePrediction(Request $request)
{
    return response()->json([
        "type" => $request->type,
        "inputs" => [
            "region" => $request->region,
            "furnishing_status" => $request->furnishing_status,
            "sizeDescription" => $request->sizeDescription,
        ],
        "predicted_price_range" => [
            "min" => 3000,
            "max" => 5500,
            "unit" => "AED",
            "text" => "3,000 - 5,500 AED"
        ],
        "predicted_price_label" => "Mid-range",
        "price_confidence" => 0.83,
        "price_top_k" => [
            ["label" => "Low", "confidence" => 0.12],
            ["label" => "Mid-range", "confidence" => 0.83],
            ["label" => "High", "confidence" => 0.05]
        ],
        "predicted_size_range" => [
            "min" => 70,
            "max" => 120,
            "unit" => "sqm",
            "text" => "70 - 120 sqm"
        ],
        "predicted_size_label" => "Medium",
        "size_confidence" => 0.79,
        "size_top_k" => [
            ["label" => "Small", "confidence" => 0.18],
            ["label" => "Medium", "confidence" => 0.79],
            ["label" => "Large", "confidence" => 0.03]
        ]
    ]);
}

}
