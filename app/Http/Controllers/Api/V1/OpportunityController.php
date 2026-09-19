<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OpportunityResource;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\OpportunityMatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class OpportunityController extends Controller
{
    public function __construct(private readonly OpportunityMatcher $matcher)
    {
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'german_level' => ['nullable', 'in:a2,b1,b2,c1'],
            'international' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:latest,start,salary,match'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:6', 'max:24'],
        ]);

        $query = Opportunity::query()
            ->published()
            ->with(['category', 'source']);

        if ($term = trim($validated['q'] ?? '')) {
            $escaped = addcslashes($term, '%_\\');
            $query->where(function (Builder $query) use ($escaped): void {
                $query
                    ->where('title_fa', 'like', "%{$escaped}%")
                    ->orWhere('title_de', 'like', "%{$escaped}%")
                    ->orWhere('employer_name', 'like', "%{$escaped}%")
                    ->orWhere('city', 'like', "%{$escaped}%");
            });
        }

        $query
            ->when($validated['category'] ?? null, fn (Builder $query, string $slug) => $query->whereHas(
                'category',
                fn (Builder $query) => $query->where('slug', $slug)
            ))
            ->when($validated['city'] ?? null, fn (Builder $query, string $city) => $query->where('city', $city))
            ->when(
                array_key_exists('international', $validated) && (bool) $validated['international'],
                fn (Builder $query) => $query->where('accepts_international', true)
            );

        if ($level = $validated['german_level'] ?? null) {
            $levels = ['a2', 'b1', 'b2', 'c1'];
            $query->whereIn('required_german_level', array_slice($levels, 0, array_search($level, $levels, true) + 1));
        }

        $user = $request->user();
        $user?->loadMissing('profile');
        $profile = $user?->profile;
        $favoriteIds = $user
            ? $user->favoriteOpportunities()->pluck('opportunities.id')->all()
            : [];
        $perPage = (int) ($validated['per_page'] ?? 12);
        $sort = $validated['sort'] ?? 'latest';

        if ($sort === 'match' && $profile) {
            $collection = $query->limit(500)->get();
            $this->decorate($collection, $user, $favoriteIds);
            $collection = $collection->sortByDesc('match_score')->values();
            $page = LengthAwarePaginator::resolveCurrentPage();
            $paginator = new LengthAwarePaginator(
                $collection->forPage($page, $perPage)->values(),
                $collection->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return OpportunityResource::collection($paginator);
        }

        match ($sort) {
            'start' => $query->orderBy('start_date'),
            'salary' => $query->orderByDesc('monthly_salary_from'),
            default => $query->orderByDesc('published_at'),
        };

        $paginator = $query->paginate($perPage)->withQueryString();
        $this->decorate($paginator->getCollection(), $user, $favoriteIds);

        return OpportunityResource::collection($paginator);
    }

    public function show(Request $request, Opportunity $opportunity): OpportunityResource
    {
        abort_unless(
            $opportunity->status === 'published'
            && $opportunity->company_review_suspended_at === null
            && (! $opportunity->company_id || $opportunity->company?->isVerified())
            && (! $opportunity->application_deadline || $opportunity->application_deadline->isToday() || $opportunity->application_deadline->isFuture()),
            404
        );

        $opportunity->load(['category', 'source']);
        $user = $request->user();
        $user?->loadMissing('profile');
        $favorites = $user
            ? $user->favoriteOpportunities()->whereKey($opportunity->id)->pluck('opportunities.id')->all()
            : [];
        $this->decorate(collect([$opportunity]), $user, $favorites);

        return new OpportunityResource($opportunity);
    }

    private function decorate(Collection $opportunities, ?User $user, array $favoriteIds): void
    {
        $profile = $user?->profile;

        foreach ($opportunities as $opportunity) {
            $opportunity->setAttribute('is_favorite', in_array($opportunity->id, $favoriteIds, true));

            if ($profile) {
                $result = $this->matcher->score($opportunity, $profile);
                $opportunity->setAttribute('match_score', $result['score']);
                $opportunity->setAttribute('match_breakdown', $result['breakdown']);
            }
        }
    }
}
