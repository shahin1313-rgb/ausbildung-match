<?php

namespace Database\Seeders;

use App\Models\ApplicationClick;
use App\Models\Category;
use App\Models\Opportunity;
use App\Models\Resume;
use App\Models\User;
use Database\Seeders\Concerns\GuardsDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    use GuardsDemoData;

    public function run(): void
    {
        $this->ensureDemoDataIsAllowed();

        $password = Hash::make('password');

        for ($i = 1; $i <= 5; $i++) {
            User::query()->updateOrCreate(
                ['email' => "employer{$i}@test.com"],
                [
                    'name' => ['نماینده نووابایت', 'نماینده راین‌ولت', 'نماینده هانزه‌ورک', 'نماینده ماین‌فاینانس', 'نماینده زونن‌هوف'][$i - 1],
                    'password' => $password,
                    'is_admin' => false,
                    'email_verified_at' => now(),
                ],
            );
        }

        $names = [
            'سارا احمدی', 'علی رضایی', 'نیلوفر کریمی', 'امیرحسین محمدی', 'مریم حسینی',
            'آرمان جعفری', 'نگار مرادی', 'رضا اکبری', 'مهسا صادقی', 'کیان نوری',
            'ترانه موسوی', 'سامان یوسفی', 'الهام قاسمی', 'پویا زمانی', 'نازنین رحیمی',
            'محمد پارسا', 'هانیه کاظمی', 'بردیا حیدری', 'شبنم امینی', 'یاسمن رستگار',
        ];
        $educationTitles = [
            'مهندسی کامپیوتر', 'برق صنعتی', 'مکانیک', 'حسابداری', 'مدیریت بازرگانی',
            'پرستاری', 'مدیریت گردشگری', 'صنایع غذایی', 'معماری', 'حمل‌ونقل',
        ];
        $skillSets = [
            ['PHP', 'JavaScript', 'Git'], ['Elektrotechnik', 'AutoCAD'], ['Mechanik', 'CAD'],
            ['Excel', 'Buchhaltung'], ['Verkauf', 'Digital Marketing'], ['Patientenpflege', 'Erste Hilfe'],
            ['Gästebetreuung', 'Englisch'], ['Hygiene', 'Kochen'], ['Bauzeichnung', 'AutoCAD'],
            ['Logistik', 'Routenplanung'],
        ];
        $categoryIds = Category::query()->whereIn('slug', [
            'software-it', 'electrical-electronics', 'mechanical-industrial', 'accounting-finance',
            'sales-marketing', 'nursing-care', 'hospitality-tourism', 'cooking-food',
            'construction-architecture', 'driving-transport', 'warehouse-logistics',
            'graphic-design', 'human-resources', 'technical-repair', 'ausbildung',
        ])->orderBy('sort_order')->pluck('id')->values();
        $opportunityIds = Opportunity::query()->whereNotNull('published_at')->orderBy('id')->pluck('id');

        foreach ($names as $index => $name) {
            $number = $index + 1;
            $user = User::query()->updateOrCreate(
                ['email' => "user{$number}@test.com"],
                ['name' => $name, 'password' => $password, 'is_admin' => false, 'email_verified_at' => now()],
            );

            $skills = $skillSets[$index % count($skillSets)];
            $user->profile()->updateOrCreate([], [
                'phone' => sprintf('+49 15%08d', 10000000 + $number),
                'country' => $index % 4 === 0 ? 'Iran' : 'Germany',
                'birth_date' => now()->subYears(19 + ($index % 12))->subDays($index * 17)->toDateString(),
                'german_level' => ['a2', 'b1', 'b2', 'c1'][$index % 4],
                'education_level' => ['diploma', 'associate', 'bachelor', 'master'][$index % 4],
                'education_title' => $educationTitles[$index % count($educationTitles)],
                'skills' => $skills,
                'preferred_category_ids' => [
                    $categoryIds[$index % $categoryIds->count()],
                    $categoryIds[($index + 5) % $categoryIds->count()],
                ],
                'preferred_cities' => [['Berlin', 'Hamburg'], ['München', 'Nürnberg'], ['Köln', 'Düsseldorf']][$index % 3],
                'work_experience_years' => $index % 6,
                'relocation_ready' => $index % 3 !== 0,
                'available_from' => now()->addWeeks(2 + ($index % 12))->toDateString(),
            ]);

            if ($number <= 15) {
                $user->germanCv()->updateOrCreate([], [
                    'headline' => "Ausbildungssuchende/r – {$educationTitles[$index % count($educationTitles)]}",
                    'summary' => 'Motivierte Bewerberin bzw. motivierter Bewerber mit praktischer Erfahrung, hoher Lernbereitschaft und Interesse an einer langfristigen beruflichen Entwicklung in Deutschland.',
                    'contact' => ['email' => $user->email, 'phone' => sprintf('+49 15%08d', 10000000 + $number)],
                    'experiences' => [[
                        'title' => 'Praktikum / Junior-Mitarbeit',
                        'company' => "Praxisbetrieb {$number}",
                        'from' => (string) (2022 + ($index % 3)),
                        'to' => '2026',
                        'description' => 'Mitarbeit im Tagesgeschäft, Dokumentation und eigenständige Bearbeitung kleiner Aufgaben.',
                    ]],
                    'education' => [['title' => $educationTitles[$index % count($educationTitles)], 'institution' => "Bildungsinstitut {$number}", 'graduation_year' => 2023 + ($index % 3)]],
                    'skills' => $skills,
                    'languages' => [['name' => 'Deutsch', 'level' => strtoupper(['a2', 'b1', 'b2', 'c1'][$index % 4])], ['name' => 'Persisch', 'level' => 'Muttersprache']],
                    'certificates' => $index % 2 === 0 ? [['name' => 'Deutschzertifikat', 'year' => 2025]] : [],
                ]);
            }

            if ($number <= 12) {
                Resume::query()->updateOrCreate(
                    ['user_id' => $user->id, 'path' => "demo/resumes/user-{$number}.pdf"],
                    [
                        'original_name' => "lebenslauf-user-{$number}.pdf",
                        'disk' => 'local',
                        'mime_type' => 'application/pdf',
                        'size_bytes' => 120000 + ($number * 7311),
                        'status' => 'reviewed',
                        'is_primary' => true,
                        'extracted_data' => ['skills' => $skills, 'education' => $educationTitles[$index % count($educationTitles)], 'experience_years' => $index % 6],
                        'analyzed_at' => now()->subDays($number),
                    ],
                );
            }

            if ($opportunityIds->isNotEmpty()) {
                $favoriteIds = [
                    $opportunityIds[$index % $opportunityIds->count()],
                    $opportunityIds[($index + 11) % $opportunityIds->count()],
                ];
                $user->favoriteOpportunities()->syncWithoutDetaching($favoriteIds);

                foreach (array_slice($favoriteIds, 0, $number <= 10 ? 2 : 1) as $clickIndex => $opportunityId) {
                    ApplicationClick::query()->updateOrCreate(
                        ['user_id' => $user->id, 'opportunity_id' => $opportunityId],
                        ['channel' => $clickIndex % 2 === 0 ? 'application_url' : 'email', 'clicked_at' => now()->subDays(($index + 1) * 2)],
                    );
                }
            }
        }
    }
}
