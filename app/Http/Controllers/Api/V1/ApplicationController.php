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
        abort_unless(
            $opportunity->status === 'published'
            && (! $opportunity->application_deadline || $opportunity->application_deadline->isToday() || $opportunity->application_deadline->isFuture()),
            404
        );

        if ($opportunity->company_id) {
            abort_if($request->user()->company?->id === $opportunity->company_id, 422, 'نمی‌توانید برای فرصت شرکت خودتان درخواست ارسال کنید.');
            $validated = $request->validate([
                'candidate_message' => ['nullable', 'string', 'max:3000'],
                'consent_data_sharing' => ['required', 'accepted'],
            ]);
            $application = $request->user()->applications()->updateOrCreate(
                ['opportunity_id' => $opportunity->id],
                [
                    'status' => 'applied',
                    'applied_at' => now(),
                    'candidate_message' => $validated['candidate_message'] ?? null,
                    'data_sharing_consent_at' => now(),
                ]
            );

            return response()->json([
                'message' => 'درخواست شما برای کارفرما ارسال شد.',
                'application' => new ApplicationResource($application->load(['opportunity.category', 'opportunity.source'])),
            ], $application->wasRecentlyCreated ? 201 : 200);
        }

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

        $application->loadMissing('opportunity');
        $validated = $application->opportunity->company_id
            ? $request->validate([
                'status' => ['sometimes', Rule::in(['withdrawn'])],
                'notes' => ['nullable', 'string', 'max:3000'],
            ])
            : $request->validate([
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
        $application->loadMissing('opportunity');
        abort_if($application->opportunity->company_id, 422, 'درخواست ارسال‌شده قابل حذف نیست؛ می‌توانید آن را پس بگیرید.');
        $application->delete();

        return response()->json(status: 204);
    }
}
