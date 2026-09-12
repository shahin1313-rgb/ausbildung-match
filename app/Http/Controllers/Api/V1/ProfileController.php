<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->profile()->firstOrCreate([], [
            'german_level' => 'none',
            'work_experience_years' => 0,
            'relocation_ready' => true,
        ]);

        return response()->json(['profile' => $profile]);
    }

    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $profile = $request->user()->profile()->updateOrCreate(
            [],
            $request->validated()
        );

        return response()->json([
            'message' => 'پروفایل ذخیره شد.',
            'profile' => $profile->fresh(),
        ]);
    }
}
