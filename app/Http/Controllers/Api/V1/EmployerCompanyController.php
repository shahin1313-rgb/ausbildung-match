<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyRequest;
use App\Http\Resources\CompanyResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployerCompanyController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        return response()->json([
            'company' => $company ? new CompanyResource($company) : null,
        ]);
    }

    public function store(CompanyRequest $request): JsonResponse
    {
        abort_if($request->user()->company()->exists(), 409, 'برای این حساب قبلاً یک شرکت ثبت شده است.');

        $company = $request->user()->company()->create([
            ...$request->validated(),
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'شرکت ثبت شد و پس از تأیید مدیر امکان انتشار فرصت فعال می‌شود.',
            'company' => new CompanyResource($company),
        ], 201);
    }

    public function update(CompanyRequest $request): JsonResponse
    {
        $company = $request->user()->company;
        abort_unless($company, 404);
        $company->fill($request->validated());
        $nameChanged = $company->isDirty('name');
        $company->save();
        if ($nameChanged) {
            $company->opportunities()->update(['employer_name' => $company->name]);
        }

        return response()->json([
            'message' => 'اطلاعات شرکت به‌روزرسانی شد.',
            'company' => new CompanyResource($company->fresh()),
        ]);
    }
}
