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
            ->with(['user.profile', 'user.resumes'])
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
            'application' => new EmployerApplicantResource($application->fresh()->load(['user.profile', 'user.resumes'])),
        ]);
    }

    public function downloadResume(Request $request, Application $application): StreamedResponse
    {
        $this->authorizeApplication($request, $application);
        $resume = $application->user->resumes()->where('is_primary', true)->first();
        abort_unless($resume && Storage::disk($resume->disk)->exists($resume->path), 404);

        return Storage::disk($resume->disk)->download($resume->path, $resume->original_name);
    }

    private function authorizeOpportunity(Request $request, Opportunity $opportunity): void
    {
        abort_unless(
            $request->user()->company && $opportunity->company_id === $request->user()->company->id,
            404
        );
    }

    private function authorizeApplication(Request $request, Application $application): void
    {
        $application->loadMissing('opportunity');
        $this->authorizeOpportunity($request, $application->opportunity);
    }
}
