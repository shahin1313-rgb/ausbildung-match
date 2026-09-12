<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Opportunity;
use App\Models\Source;
use Illuminate\Database\Seeder;

class OpportunitySeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = Category::query()->pluck('id', 'slug');
        $sourceIds = Source::query()->pluck('id', 'name');
        $defaultUrl = 'https://www.arbeitsagentur.de/jobsuche/suche?angebotsart=4';
        $rows = [
            [
                'category' => 'it',
                'title_fa' => 'متخصص توسعه نرم‌افزار',
                'title_de' => 'Fachinformatiker/in – Anwendungsentwicklung',
                'employer_name' => 'شرکت فناوری نمونه برلین',
                'city' => 'Berlin',
                'state' => 'Berlin',
                'german' => 'b1',
                'salary' => [1120, 1350],
                'skills' => ['JavaScript', 'PHP', 'Git'],
                'description' => 'یک دوره دوگانه برای یادگیری توسعه نرم‌افزار، کار تیمی و پیاده‌سازی پروژه‌های واقعی.',
            ],
            [
                'category' => 'healthcare',
                'title_fa' => 'کارآموز پرستاری',
                'title_de' => 'Pflegefachfrau / Pflegefachmann',
                'employer_name' => 'مرکز درمانی نمونه هامبورگ',
                'city' => 'Hamburg',
                'state' => 'Hamburg',
                'german' => 'b2',
                'salary' => [1340, 1500],
                'skills' => ['Teamarbeit', 'Empathie', 'Patientenpflege'],
                'description' => 'آموزش حرفه‌ای مراقبت از بیمار همراه با بخش نظری و کار عملی در محیط درمانی.',
            ],
            [
                'category' => 'technical',
                'title_fa' => 'مکاترونیک صنعتی',
                'title_de' => 'Mechatroniker/in',
                'employer_name' => 'صنایع نمونه مونیخ',
                'city' => 'München',
                'state' => 'Bayern',
                'german' => 'b1',
                'salary' => [1180, 1420],
                'skills' => ['Mechanik', 'Elektronik', 'Mathematik'],
                'description' => 'دوره عملی تعمیر، مونتاژ و نگهداری سامانه‌های مکانیکی و الکترونیکی صنعتی.',
            ],
            [
                'category' => 'business',
                'title_fa' => 'کارمند امور بازرگانی',
                'title_de' => 'Kaufmann/-frau für Büromanagement',
                'employer_name' => 'گروه خدمات نمونه کلن',
                'city' => 'Köln',
                'state' => 'Nordrhein-Westfalen',
                'german' => 'b2',
                'salary' => [1050, 1250],
                'skills' => ['Organisation', 'MS Office', 'Kommunikation'],
                'description' => 'آموزش برنامه‌ریزی اداری، ارتباط با مشتری، امور مالی اولیه و سازمان‌دهی جلسات.',
            ],
            [
                'category' => 'hospitality',
                'title_fa' => 'مدیریت هتل',
                'title_de' => 'Hotelfachmann/-frau',
                'employer_name' => 'هتل نمونه فرانکفورت',
                'city' => 'Frankfurt am Main',
                'state' => 'Hessen',
                'german' => 'b1',
                'salary' => [1000, 1200],
                'skills' => ['Gästebetreuung', 'Englisch', 'Service'],
                'description' => 'آموزش پذیرش مهمان، خدمات، رزرو و هماهنگی عملیات روزانه هتل.',
            ],
            [
                'category' => 'crafts',
                'title_fa' => 'تکنسین برق ساختمان',
                'title_de' => 'Elektroniker/in für Energie- und Gebäudetechnik',
                'employer_name' => 'تأسیسات نمونه اشتوتگارت',
                'city' => 'Stuttgart',
                'state' => 'Baden-Württemberg',
                'german' => 'b1',
                'salary' => [1020, 1320],
                'skills' => ['Elektrotechnik', 'Handwerk', 'Sicherheit'],
                'description' => 'آموزش نصب، آزمایش و تعمیر تجهیزات الکتریکی و سامانه‌های هوشمند ساختمان.',
            ],
            [
                'category' => 'it',
                'title_fa' => 'متخصص یکپارچه‌سازی سیستم',
                'title_de' => 'Fachinformatiker/in – Systemintegration',
                'employer_name' => 'شبکه نمونه لایپزیگ',
                'city' => 'Leipzig',
                'state' => 'Sachsen',
                'german' => 'b1',
                'salary' => [1080, 1300],
                'skills' => ['Linux', 'Netzwerke', 'Support'],
                'description' => 'آموزش شبکه، سرور، پشتیبانی کاربران و مستندسازی زیرساخت‌های فناوری اطلاعات.',
            ],
            [
                'category' => 'business',
                'title_fa' => 'فروشنده خرده‌فروشی',
                'title_de' => 'Kaufmann/-frau im Einzelhandel',
                'employer_name' => 'فروشگاه نمونه هانوفر',
                'city' => 'Hannover',
                'state' => 'Niedersachsen',
                'german' => 'b1',
                'salary' => [980, 1180],
                'skills' => ['Verkauf', 'Kundenservice', 'Zuverlässigkeit'],
                'description' => 'آموزش فروش، مشاوره مشتری، مدیریت کالا و اجرای فرایندهای روزانه فروشگاه.',
            ],
        ];

        foreach ($rows as $index => $row) {
            Opportunity::query()->updateOrCreate(
                ['source_id' => $sourceIds['Bundesagentur für Arbeit'], 'external_id' => 'demo-'.($index + 1)],
                [
                    'category_id' => $categoryIds[$row['category']],
                    'title_fa' => $row['title_fa'],
                    'title_de' => $row['title_de'],
                    'employer_name' => $row['employer_name'],
                    'description_fa' => $row['description'],
                    'description_de' => null,
                    'city' => $row['city'],
                    'state' => $row['state'],
                    'training_type' => 'dual',
                    'start_date' => now()->addMonths(5 + ($index % 4))->toDateString(),
                    'application_deadline' => now()->addMonths(2 + ($index % 3))->toDateString(),
                    'monthly_salary_from' => $row['salary'][0],
                    'monthly_salary_to' => $row['salary'][1],
                    'required_german_level' => $row['german'],
                    'education_requirement' => 'حداقل دیپلم یا مدرک قابل ارزیابی مشابه',
                    'skills' => $row['skills'],
                    'accepts_international' => true,
                    'visa_support' => $index % 3 === 0 ? 'possible' : 'unknown',
                    'application_url' => $defaultUrl,
                    'status' => 'published',
                    'published_at' => now()->subDays($index),
                ]
            );
        }
    }
}
