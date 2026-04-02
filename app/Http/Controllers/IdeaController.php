<?php

namespace App\Http\Controllers;

use App\Events\IdeaCreated;
use App\Models\Idea;
use App\Models\Competitor;
use App\Models\Recommendation;
use App\Models\AnalysisReport;
use App\Models\FinancialEstimation;
use App\Models\PropertyPriceAnalysis;
use App\Models\RegionAnalysis;
use App\Models\SwotAnalysis;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class IdeaController extends Controller
{
    /**
     * إنشاء فكرة جديدة + إرسالها للـ AI
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'required|string',

        ]);

        // استخدام Transaction لضمان سلامة البيانات
        return DB::transaction(function () use ($request) {

            // 1) حفظ الفكرة
            $idea = Idea::create([
                'user_id'         => Auth::id(),
                'title'           => $request->title,
                'description'     => $request->description,
                'status'          => 'processing',
            ]);

            try {
                // 2) إرسال الفكرة للـ AI
                $aiResponse = Http::timeout(10)->post(
                    'http://127.0.0.1:8009/predict',
                    [
                        'idea_text' => $idea->description,
                        'top_k' => 3
                    ]
                );

                if (!$aiResponse->successful()) {
                    throw new \Exception("AI API error");
                }

                $ai = $aiResponse->json();

                // 3) حفظ التقرير
                $report = AnalysisReport::create([
                    'idea_id'            => $idea->id,
                    'predicted_category' => $ai['predicted_category'],
                    'confidence'         => $ai['confidence'],
                    'is_ambiguous'       => $ai['is_ambiguous'],
                    'top_k'              => $ai['top_k'],
                ]);

                $idea->update(['status' => 'done']);

                return response()->json([
                    'status'  => true,
                    'message' => 'Idea analyzed successfully',
                    'data'    => [
                        'idea' => $idea,
                        'report' => $report
                    ]
                ]);
            } catch (\Exception $e) {
                $idea->update(['status' => 'failed']);
                return response()->json([
                    'status' => false,
                    'message' => "Analysis failed: " . $e->getMessage(),
                ], 500);
            }
        });
    }


    public function runCompetitionAnalysis(Request $request)
    {
        return DB::transaction(function () use ($request) {

            /*
        |--------------------------------------------------------------------------
        | 1️⃣ جلب الفكرة والتأكد من الملكية
        |--------------------------------------------------------------------------
        */

            $idea = Idea::where('id', $request->idea_id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$idea) {
                return response()->json([
                    'status' => false,
                    'message' => 'Idea not found or unauthorized'
                ], 404);
            }

            /*
        |--------------------------------------------------------------------------
        | 2️⃣ جلب نتيجة Model 1 (Idea Classification)
        |--------------------------------------------------------------------------
        */



            $analysis = AnalysisReport::where('idea_id', $idea->id)->latest()->first();

            if (!$analysis) {
                throw new \Exception('Idea classification not found (no analysis record)');
            }

            if (empty($analysis->predicted_category)) {
                throw new \Exception('Idea classification found but predicted_category is empty');
            }


            /*
        |--------------------------------------------------------------------------
        | 3️⃣ إرسال Request إلى AI Model 2
        |--------------------------------------------------------------------------
        */

            $response = Http::timeout(10)->acceptJson()
                ->asJson()->post(
                    'http://127.0.0.1:8002/competition/analyze',
                    [
                        'idea_text'       => $idea->description,
                        'industry_hint'   => $analysis->predicted_category,
                        'target_country'  => $request->target_country,
                        'target_city'     => $request->target_city,
                        'max_competitors' => $request->max_competitors ?? 10,
                        'max_clusters'    => $request->max_clusters ?? 4,
                    ]
                );

            if (!$response->successful()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Competition AI API failed',
                    'debug' => [
                        'status_code' => $response->status(),
                        'response'    => $response->body(),
                    ]
                ], 500);
            }

            $data = $response->json();

            /*
        |--------------------------------------------------------------------------
        | 4️⃣ تخزين المنافسين
        |--------------------------------------------------------------------------
        */

            foreach ($data['competitors'] as $comp) {
                Competitor::create([
                    'idea_id'          => $idea->id,
                    'name'             => $comp['name'],
                    'industry'         => $comp['industry'],
                    'region'           => $comp['region'],
                    'country'          => $comp['country'],
                    'company_size'     => $comp['size'],
                    'website'          => $comp['website'] ?? null,
                    'similarity_score' => $comp['similarity_score'],
                ]);
            }

            /*
        |--------------------------------------------------------------------------
        | 5️⃣ تخزين SWOT Analysis
        |--------------------------------------------------------------------------
        */

            SwotAnalysis::create([
                'idea_id'       => $idea->id,
                'strengths'     => $data['swot']['strengths'],
                'weaknesses'    => $data['swot']['weaknesses'],
                'opportunities' => $data['swot']['opportunities'],
                'threats'       => $data['swot']['threats'],
            ]);

            /*
        |--------------------------------------------------------------------------
        | 6️⃣ تحديث حالة الفكرة
        |--------------------------------------------------------------------------
        */

            $idea->update(['status' => 'done']);

            /*
        |--------------------------------------------------------------------------
        | 7️⃣ إرسال إشعار
        |--------------------------------------------------------------------------
        */

            //event(new \App\Events\IdeaAnalysisCompleted($idea));

            /*
        |--------------------------------------------------------------------------
        | 🔚 Response
        |--------------------------------------------------------------------------
        */

            return response()->json([
                'status' => true,


                'idea' => $data['idea'],
                'competitors_summary' => $data['competitors_summary'],
                'competitors' => $data['competitors'],
                'clusters' => $data['clusters'],
                'swot' => $data['swot'],
                'metadata' => $data['metadata'] ?? null
            ]);
        });
    }

    public function runRegionAnalysis(Request $request)
    {
        return DB::transaction(function () use ($request) {

            /*
        |---------------------------------------------------------
        | 1️⃣ جلب الفكرة
        |---------------------------------------------------------
        */
            $idea = Idea::where('id', $request->idea_id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$idea) {
                return response()->json([
                    'status' => false,
                    'message' => 'Idea not found or unauthorized'
                ], 404);
            }

            /*
        |---------------------------------------------------------
        | 2️⃣ جلب industry من Model 1
        |---------------------------------------------------------
        */
            $classification = AnalysisReport::where('idea_id', $idea->id)
                ->latest()
                ->first();

            if (!$classification || !$classification->predicted_category) {
                throw new \Exception('Industry classification not found');
            }

            /*
        |---------------------------------------------------------
        | 3️⃣ إرسال Request إلى AI Model 3
        |---------------------------------------------------------
        */
            $response = Http::timeout(10)->post(
                'http://127.0.0.1:8005/predict',
                [
                    'industry' => strtolower($classification->predicted_category),
                    'k'        => $request->k ?? 3,
                ]
            );

            if (!$response->successful()) {
                throw new \Exception('Region AI API failed');
            }

            $data = $response->json();

            /*
        |---------------------------------------------------------
        | 4️⃣ تخزين النتيجة
        |---------------------------------------------------------
        */
            $region = RegionAnalysis::updateOrCreate(
                ['idea_id' => $idea->id],
                [
                    'predicted_region' => $data['predicted_region'],
                    'confidence'       => $data['confidence'],
                    'is_ambiguous'     => $data['is_ambiguous'],
                    'top_k'            => $data['top_k'],
                ]
            );

            /*
        |---------------------------------------------------------
        | 5️⃣ Response مطابق لرد AI
        |---------------------------------------------------------
        */
            return response()->json([
                'predicted_region' => $region->predicted_region,
                'confidence'       => $region->confidence,
                'is_ambiguous'     => $region->is_ambiguous,
                'top_k'            => $region->top_k,
            ]);
        });
    }






    public function predictPropertyPrice(Request $request)
    {
        $request->validate([
            'idea_id' => 'required|integer',
            'type' => 'required|in:Rent,Sale',
            'furnishing_status' => 'nullable|string',
            'sizeDescription' => 'required|in:small,medium,big',
            'k' => 'required|integer|min:1|max:10',
        ]);

        $idea = Idea::where('id', $request->idea_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $regionAnalysis = $idea->regionAnalysis;

        if (!$regionAnalysis) {
            return response()->json([
                'status' => false,
                'message' => 'Region prediction not found'
            ], 422);
        }

        $response = Http::timeout(10)->post(
            'http://127.0.0.1:8007/predict',
            [
                'type' => $request->type,
                'region' => $regionAnalysis->predicted_region,
                'furnishing_status' => $request->furnishing_status,
                'sizeDescription' => $request->sizeDescription,
                'k' => $request->k,
            ]
        );

        $data = $response->json();

        $analysis = PropertyPriceAnalysis::create([
            'idea_id' => $idea->id,
            'type' => $data['type'],
            'region' => $regionAnalysis->predicted_region,
            'furnishing_status' => $request->furnishing_status,
            'size_description' => $request->sizeDescription,

            'price_min' => $data['predicted_price_range']['min'],
            'price_max' => $data['predicted_price_range']['max'],
            'price_unit' => $data['predicted_price_range']['unit'],
            'price_text' => $data['predicted_price_range']['text'],
            'price_label' => $data['predicted_price_label'],
            'price_confidence' => $data['price_confidence'],
            'price_top_k' => $data['price_top_k'],

            'size_min' => $data['predicted_size_range']['min'],
            'size_max' => $data['predicted_size_range']['max'],
            'size_unit' => $data['predicted_size_range']['unit'],
            'size_text' => $data['predicted_size_range']['text'],
            'size_label' => $data['predicted_size_label'],
            'size_confidence' => $data['size_confidence'],
            'size_top_k' => $data['size_top_k'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $analysis
        ]);
    }













    /**
     * جميع أفكار المستخدم
     */
    public function myIdeas()
    {
        $ideas = Idea::where('user_id', Auth::id())
            ->with(['competitors', 'recommendations', 'financialEstimation', 'report', "propertyPriceAnalysis"])
            ->get();

        return response()->json([
            'status' => true,
            'ideas' => $ideas,
        ]);
    }

    /**
     * عرض فكرة واحدة
     */
    public function show($id)
    {
        $idea = Idea::where('id', $id)
            ->with(['competitors', 'recommendations', 'financialEstimation', 'report'])
            ->first();

        if (!$idea) {
            return response()->json([
                'status' => false,
                'message' => 'Idea not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'idea' => $idea,
        ]);
    }

    /**
     * تحديث حالة الفكرة (للمشرف)
     */

    public function update(Request $request)
    {
        $request->validate([
            'idea_id'          => 'required|integer',
            'title'            => 'required|string|max:255',
            'description'      => 'required|string',
            'industry'         => 'nullable|string',
            'target_audience'  => 'nullable|string',
        ]);

        // 1) اجلب الفكرة وتأكد أنها للمستخدم نفسه
        $idea = Idea::where('id', $request->idea_id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$idea) {
            return response()->json([
                'status' => false,
                'message' => 'Idea not found or unauthorized'
            ], 404);
        }

        // 2) تحديث بيانات الفكرة
        $idea->update([
            'title'            => $request->title,
            'description'      => $request->description,
            'industry'         => $request->industry,
            'target_audience'  => $request->target_audience,
            'status'           => 'pending',  // نرجعها pending
        ]);

        $aiResponse = Http::timeout(10)->post(
            'http://127.0.0.1:8009/predict',
            [
                'idea_text' => $idea->description,
                'top_k' => 3
            ]
        );

        if (!$aiResponse->successful()) {
            throw new \Exception("AI API error");
        }

        $ai = $aiResponse->json();

        // 3) حفظ التقرير
        $report = AnalysisReport::create([
            'idea_id'            => $idea->id,
            'predicted_category' => $ai['predicted_category'],
            'confidence'         => $ai['confidence'],
            'is_ambiguous'       => $ai['is_ambiguous'],
            'top_k'              => $ai['top_k'],
        ]);

        $idea->update(['status' => 'done']);

        return response()->json([
            'status'  => true,
            'message' => 'Idea analyzed successfully',
            'data'    => [
                'idea' => $idea,
                'report' => $report
            ]
        ]);
    }
        public function reanalyze(Request $request)
    {
        $request->validate([
            'idea_id' => 'required|integer'
        ]);

        // 1) تأكد أن الفكرة تخص المستخدم
        $idea = Idea::where('id', $request->idea_id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$idea) {
            return response()->json([
                'status' => false,
                'message' => 'Idea not found or unauthorized',
            ], 404);
        }

        // 2) غيّر الحالة إلى processing
        $idea->update(['status' => 'processing']);

        // 3) احذف البيانات القديمة
        $idea->competitors()->delete();
        $idea->recommendations()->delete();
        $idea->financialEstimation()->delete();
        $idea->report()->delete();

        // 4) أرسل الطلب للذكاء الاصطناعي (نفس الاستدعاء السابق)
        try {
            $aiResponse = Http::post('http://127.0.0.1:8001/api/mock-ai', [
                'title'       => $idea->title,
                'description' => $idea->description,
            ]);

            if (!$aiResponse->successful()) {
                $idea->update(['status' => 'failed']);
                return response()->json([
                    'status' => false,
                    'message' => 'AI server did not respond',
                ], 500);
            }

            $ai = $aiResponse->json()['data'];
        } catch (\Exception $e) {
            $idea->update(['status' => 'failed']);
            return response()->json([
                'status' => false,
                'message' => "AI error: " . $e->getMessage(),
            ], 500);
        }

        // 5) احفظ التوصيات الجديدة
        foreach ($ai['recommendations'] as $rec) {
            Recommendation::create([
                'idea_id' => $idea->id,
                'recommendation_text' => $rec,
            ]);
        }

        // 6) احفظ المنافسين الجدد
        foreach ($ai['competitors'] as $comp) {
            Competitor::create([
                'idea_id' => $idea->id,
                'name' => $comp['name'],
                'description' => $comp['description'],
            ]);
        }

        // 7) حفظ التحليل المالي الجديد
        FinancialEstimation::create([
            'idea_id'           => $idea->id,
            'estimated_cost'    => $ai['financial_estimation']['estimated_cost'],
            'estimated_revenue' => $ai['financial_estimation']['expected_revenue'],
            'roi'               => $ai['financial_estimation']['roi'],
        ]);

        // 8) تقرير التحليل الجديد
        AnalysisReport::create([
            'idea_id'    => $idea->id,
            'strengths'  => $ai['strengths'],
            'weaknesses' => $ai['weaknesses'],
            'pdf_path'   => 'none',
            'report_type'  => 'full',
            'storage_disk' => 'local',
        ]);

        // 9) تحديث حالة الفكرة
        $idea->update(['status' => 'done']);
    }
    public function delete(Request $request)
    {
        $request->validate([
            'idea_id' => 'required|integer',
        ]);

        // 1) جلب الفكرة والتأكد أنها لنفس المستخدم
        $idea = Idea::where('id', $request->idea_id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$idea) {
            return response()->json([
                'status' => false,
                'message' => 'Idea not found or unauthorized',
            ], 404);
        }

        // 2) حذف الفكرة (وسيتم حذف كل البيانات المرتبطة بسبب onDelete Cascade)
        $idea->delete();

        return response()->json([
            'status' => true,
            'message' => 'Idea deleted successfully',
        ]);
    }
}
