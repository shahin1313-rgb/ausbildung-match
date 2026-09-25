<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BundesagenturJobsClient
{
    public function searchAusbildung(int $page = 1, ?string $location = null): array
    {
        $query = [
            'angebotsart' => 4,
            'page' => max(1, $page),
            'size' => (int) config('services.bundesagentur.page_size', 25),
            'veroeffentlichtseit' => (int) config('services.bundesagentur.published_within_days', 14),
            'pav' => 'false',
        ];

        if ($location) {
            $query['wo'] = $location;
            $query['umkreis'] = (int) config('services.bundesagentur.radius_km', 200);
        }

        return $this->request()
            ->get('/jobboerse/jobsuche-service/pc/v6/jobs', $query)
            ->throw()
            ->json();
    }

    public function details(string $referenceNumber): array
    {
        if ($referenceNumber === '') {
            throw new RuntimeException('A Bundesagentur reference number is required.');
        }

        $encoded = rtrim(base64_encode($referenceNumber), '=');

        return $this->request()
            ->get('/jobboerse/jobsuche-service/pc/v4/jobdetails/'.rawurlencode($encoded))
            ->throw()
            ->json();
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.bundesagentur.base_url'), '/'))
            ->acceptJson()
            ->withHeaders(['X-API-Key' => (string) config('services.bundesagentur.api_key')])
            ->connectTimeout(10)
            ->timeout(30)
            ->retry(3, 1000, throw: false);
    }
}
