<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApplicationClickController extends Controller
{
    public function __invoke(Request $request, Opportunity $opportunity): JsonResponse
    {
        $validated = $request->validate([
            'channel' => ['nullable', Rule::in(['application_url', 'email'])],
        ]);

        $request->user()->applicationClicks()->create([
            'opportunity_id' => $opportunity->id,
            'channel' => $validated['channel'] ?? 'application_url',
            'clicked_at' => now(),
        ]);

        return response()->json(['recorded' => true], 201);
    }
}
