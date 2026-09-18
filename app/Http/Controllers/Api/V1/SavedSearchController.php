<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SavedSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SavedSearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $searches = $request->user()->savedSearches()
            ->latest('updated_at')
            ->get(['id', 'name', 'filters', 'updated_at']);

        return response()->json(['data' => $searches]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('saved_searches')->where(fn ($query) => $query->where('user_id', $request->user()->id)),
            ],
            'filters' => ['required', 'array'],
            'filters.q' => ['nullable', 'string', 'max:120'],
            'filters.category' => ['nullable', 'string', 'max:100'],
            'filters.city' => ['nullable', 'string', 'max:100'],
            'filters.german_level' => ['nullable', Rule::in(['', 'a2', 'b1', 'b2', 'c1', 'A2', 'B1', 'B2', 'C1'])],
            'filters.international' => ['required', 'boolean'],
            'filters.sort' => ['required', Rule::in(['latest', 'start', 'salary', 'match'])],
        ]);

        abort_if($request->user()->savedSearches()->count() >= 25, 422, 'حداکثر ۲۵ جست‌وجو می‌توانید ذخیره کنید.');

        $filters = collect($validated['filters'])
            ->only(['q', 'category', 'city', 'german_level', 'international', 'sort'])
            ->map(fn (mixed $value) => is_string($value) ? trim($value) : $value)
            ->all();
        $filters['german_level'] = strtolower($filters['german_level'] ?? '');

        $savedSearch = $request->user()->savedSearches()->create([
            'name' => trim($validated['name']),
            'filters' => $filters,
        ]);

        return response()->json([
            'message' => 'جست‌وجو ذخیره شد.',
            'data' => $savedSearch->only(['id', 'name', 'filters', 'updated_at']),
        ], 201);
    }

    public function destroy(Request $request, SavedSearch $savedSearch): JsonResponse
    {
        abort_unless($savedSearch->user_id === $request->user()->id, 404);
        $savedSearch->delete();

        return response()->json(['message' => 'جست‌وجوی ذخیره‌شده حذف شد.']);
    }
}
