<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OpportunityResource;
use App\Models\Opportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $opportunities = $request->user()
            ->favoriteOpportunities()
            ->published()
            ->with(['category', 'source'])
            ->latest('favorites.created_at')
            ->paginate(12);

        $opportunities->getCollection()->each->setAttribute('is_favorite', true);

        return OpportunityResource::collection($opportunities);
    }

    public function store(Request $request, Opportunity $opportunity): JsonResponse
    {
        abort_unless($opportunity->status === 'published', 404);
        $request->user()->favoriteOpportunities()->syncWithoutDetaching([$opportunity->id]);

        return response()->json(['message' => 'فرصت ذخیره شد.']);
    }

    public function destroy(Request $request, Opportunity $opportunity): JsonResponse
    {
        $request->user()->favoriteOpportunities()->detach($opportunity->id);

        return response()->json(['message' => 'از ذخیره‌شده‌ها حذف شد.']);
    }
}
