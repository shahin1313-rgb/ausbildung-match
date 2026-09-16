<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\Opportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $applications = $request->user()->applications()
            ->with(['opportunity.category', 'opportunity.source'])
            ->latest('updated_at')
            ->get();

        return response()->json([
            'data' => ApplicationResource::collection($applications),
            'summary' => [
                'total' => $applications->count(),
                'active' => $applications->whereIn('status', ['applied', 'reviewing'])->count(),
                'interviews' => $applications->where('status', 'interview')->count(),
            ],
        ]);
    }

    public function store(Request $request, Opportunity $opportunity): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(Application::STATUSES)],
        ]);
        $status = $validated['status'] ?? 'opened';

        $application = $request->user()->applications()->updateOrCreate(
            ['opportunity_id' => $opportunity->id],
            [
                'status' => $status,
                'applied_at' => in_array($status, ['applied', 'reviewing', 'interview', 'offer', 'rejected'], true) ? now() : null,
            ]
        );

        return response()->json([
            'message' => 'فرصت به رهگیر درخواست‌ها اضافه شد.',
            'application' => new ApplicationResource($application->load(['opportunity.category', 'opportunity.source'])),
        ], $application->wasRecentlyCreated ? 201 : 200);
    }

    public function update(Request $request, Application $application): JsonResponse
    {
        abort_unless($application->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(Application::STATUSES)],
            'interview_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        if (in_array($validated['status'] ?? null, ['applied', 'reviewing', 'interview', 'offer', 'rejected'], true) && ! $application->applied_at) {
            $validated['applied_at'] = now();
        }

        $application->update($validated);

        return response()->json([
            'message' => 'وضعیت درخواست به‌روزرسانی شد.',
            'application' => new ApplicationResource($application->fresh()->load(['opportunity.category', 'opportunity.source'])),
        ]);
    }

    public function destroy(Request $request, Application $application): JsonResponse
    {
        abort_unless($application->user_id === $request->user()->id, 404);
        $application->delete();

        return response()->json(status: 204);
    }
}
