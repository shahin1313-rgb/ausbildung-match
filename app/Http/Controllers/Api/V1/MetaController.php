<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Opportunity;
use Illuminate\Http\JsonResponse;

class MetaController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'slug', 'name_fa', 'name_de']),
            'cities' => Opportunity::query()
                ->published()
                ->select('city')
                ->distinct()
                ->orderBy('city')
                ->pluck('city'),
            'german_levels' => ['A2', 'B1', 'B2', 'C1'],
            'legal' => [
                'terms_version' => config('legal.terms_version'),
                'privacy_version' => config('legal.privacy_version'),
                'resume_retention_days' => max(1, (int) config('legal.resume_retention_days')),
                'provider' => config('legal.provider'),
            ],
        ]);
    }
}
