<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GermanCvUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GermanCvController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $cv = $request->user()->germanCv()->firstOrCreate([], [
            'contact' => ['email' => $request->user()->email],
            'experiences' => [],
            'education' => [],
            'skills' => [],
            'languages' => [],
            'certificates' => [],
        ]);

        return response()->json(['cv' => $cv]);
    }

    public function update(GermanCvUpdateRequest $request): JsonResponse
    {
        $cv = $request->user()->germanCv()->updateOrCreate([], $request->validated());

        return response()->json([
            'message' => 'رزومه آلمانی ذخیره شد.',
            'cv' => $cv->fresh(),
        ]);
    }
}
