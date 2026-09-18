<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployerOpportunityRequest;
use App\Http\Resources\EmployerOpportunityResource;
use App\Models\Company;
use App\Models\Opportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmployerOpportunityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $this->company($request);
        $opportunities = $company->opportunities()
            ->withCount(['applications' => fn ($query) => $query->where('status', '!=', 'opened')])
            ->latest()
            ->get();

        return response()->json(['data' => EmployerOpportunityResource::collection($opportunities)]);
    }

    public function store(EmployerOpportunityRequest $request): JsonResponse
    {
        $company = $this->company($request);
        $data = $this->normalized($request->validated(), $company);
        $opportunity = $company->opportunities()->create($data);

        return response()->json([
            'message' => $opportunity->status === 'published' ? 'فرصت منتشر شد.' : 'پیش‌نویس فرصت ذخیره شد.',
            'opportunity' => new EmployerOpportunityResource($opportunity->loadCount('applications')),
        ], 201);
    }

    public function update(EmployerOpportunityRequest $request, Opportunity $opportunity): JsonResponse
    {
        $company = $this->company($request);
        $this->owns($company, $opportunity);
        $opportunity->update($this->normalized($request->validated(), $company, $opportunity));

        return response()->json([
            'message' => 'فرصت به‌روزرسانی شد.',
            'opportunity' => new EmployerOpportunityResource($opportunity->fresh()->loadCount('applications')),
        ]);
    }

    public function destroy(Request $request, Opportunity $opportunity): JsonResponse
    {
        $company = $this->company($request);
        $this->owns($company, $opportunity);
        abort_if($opportunity->applications()->exists(), 422, 'فرصتی که متقاضی دارد قابل حذف نیست؛ آن را منقضی کنید.');
        $opportunity->delete();

        return response()->json(status: 204);
    }

    private function company(Request $request): Company
    {
        $company = $request->user()->company;
        abort_unless($company && $company->isVerified(), 403, 'شرکت شما هنوز تأیید نشده است. پس از تأیید مدیر امکان مدیریت فرصت‌ها فعال می‌شود.');

        return $company;
    }

    private function owns(Company $company, Opportunity $opportunity): void
    {
        abort_unless($opportunity->company_id === $company->id, 404);
    }

    private function normalized(array $data, Company $company, ?Opportunity $opportunity = null): array
    {
        $status = $data['status'] ?? $opportunity?->status ?? 'draft';
        $deadline = array_key_exists('application_deadline', $data)
            ? $data['application_deadline']
            : $opportunity?->application_deadline;
        if ($status === 'published' && $deadline && now()->startOfDay()->gt($deadline)) {
            throw ValidationException::withMessages([
                'application_deadline' => ['برای انتشار، مهلت درخواست نباید گذشته باشد.'],
            ]);
        }

        return [
            ...$data,
            'source_id' => null,
            'employer_name' => $company->name,
            'application_url' => null,
            'contact_email' => $data['contact_email'] ?? $opportunity?->contact_email ?? $company->contact_email,
            'published_at' => $status === 'published' ? ($opportunity?->published_at ?? now()) : null,
        ];
    }
}
