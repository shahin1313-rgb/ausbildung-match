<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\EligibilityAssessmentRequest;
use App\Services\EligibilityAssessmentService;
use Illuminate\Http\JsonResponse;

class EligibilityAssessmentController extends Controller
{
    public function __invoke(
        EligibilityAssessmentRequest $request,
        EligibilityAssessmentService $assessmentService,
    ): JsonResponse {
        return response()->json([
            'assessment' => $assessmentService->assess($request->validated()),
        ]);
    }
}
