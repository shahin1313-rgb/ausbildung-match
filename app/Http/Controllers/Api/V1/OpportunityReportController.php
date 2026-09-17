<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOpportunityReportRequest;
use App\Models\Opportunity;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class OpportunityReportController extends Controller
{
    public function store(StoreOpportunityReportRequest $request, Opportunity $opportunity): JsonResponse
    {
        abort_unless($opportunity->status === 'published', 404);

        $validated = $request->validated();
        $user = $request->user();
        $identity = $user
            ? 'user:'.$user->getAuthIdentifier()
            : 'ip:'.($request->ip() ?: 'unknown');
        $reporterKey = hash_hmac('sha256', $identity, (string) config('app.key'));

        try {
            $report = $opportunity->reports()->create([
                'user_id' => $user?->getAuthIdentifier(),
                'reporter_email' => $user?->email ?? strtolower($validated['reporter_email']),
                'reason' => $validated['reason'],
                'details' => isset($validated['details']) ? trim($validated['details']) : null,
                'status' => 'pending',
                'reporter_key' => $reporterKey,
                'reported_on' => today(),
            ]);
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                throw ValidationException::withMessages([
                    'report' => 'گزارش شما امروز ثبت شده است و در صف بررسی قرار دارد.',
                ]);
            }

            throw $exception;
        }

        return response()->json([
            'message' => 'گزارش شما ثبت شد و توسط تیم بررسی خواهد شد. سپاس از همکاری شما.',
            'report' => [
                'id' => $report->id,
                'status' => $report->status,
            ],
        ], 201);
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['19', '23000', '23505'], true);
    }
}
