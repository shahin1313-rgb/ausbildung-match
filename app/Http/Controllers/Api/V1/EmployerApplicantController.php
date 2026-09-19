<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployerApplicantResource;
use App\Models\Application;
use App\Models\Opportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployerApplicantController extends Controller
{
    public function index(Request $request, Opportunity $opportunity): JsonResponse
    {
        $this->authorizeOpportunity($request, $opportunity);
        $applications = $opportunity->applications()
            ->where('status', '!=', 'opened')
            ->whereNotNull('data_sharing_consent_at')
            ->with(['user.profile', 'resume'])
            ->latest('applied_at')
            ->get();

        return response()->json(['data' => EmployerApplicantResource::collection($applications)]);
    }

    public function update(Request $request, Application $application): JsonResponse
    {
        $this->authorizeApplication($request, $application);
        abort_if($application->status === 'withdrawn', 422, 'درخواست پس‌گرفته‌شده قابل تغییر نیست.');
        $validated = $request->validate([
            'status' => ['required', Rule::in(['reviewing', 'interview', 'offer', 'rejected'])],
            'interview_at' => ['nullable', 'date'],
        ]);
        if ($validated['status'] !== 'interview') {
            $validated['interview_at'] = null;
        }
        $application->update($validated);

        return response()->json([
            'message' => 'وضعیت متقاضی به‌روزرسانی شد.',
            'application' => new EmployerApplicantResource($application->fresh()->load(['user.profile', 'resume'])),
        ]);
    }

    public function downloadResume(Request $request, Application $application): StreamedResponse
    {
        $this->authorizeApplication($request, $application);
        $application->loadMissing('resume');
        $resume = $application->resume;
        abort_unless(
            $resume
                && $resume->user_id === $application->user_id
                && (! $resume->retention_until || $resume->retention_until->isFuture())
                && Storage::disk($resume->disk)->exists($resume->path),
            404
        );

        return Storage::disk($resume->disk)->download($resume->path, $resume->original_name, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function authorizeOpportunity(Request $request, Opportunity $opportunity): void
    {
        abort_unless(
            $request->user()->company?->isVerified()
                && $opportunity->company_id === $request->user()->company->id,
            404
        );
    }

    private function authorizeApplication(Request $request, Application $application): void
    {
        $application->loadMissing('opportunity');
        $this->authorizeOpportunity($request, $application->opportunity);
        abort_unless($application->data_sharing_consent_at, 404);
    }
}
