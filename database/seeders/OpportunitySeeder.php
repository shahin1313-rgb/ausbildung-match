<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Opportunity;
use App\Models\Source;
use Database\Seeders\Concerns\GuardsDemoData;
use Illuminate\Database\Seeder;

class OpportunitySeeder extends Seeder
{
    use GuardsDemoData;

    public function run(): void
    {
        $this->ensureDemoDataIsAllowed();

        $categoryIds = Category::query()->pluck('id', 'slug');
        $source = Source::query()->where('name', 'Ausbildung Match Demo')->firstOrFail();
        $locations = [
            ['Berlin', 'Berlin'], ['Hamburg', 'Hamburg'], ['München', 'Bayern'],
            ['Köln', 'Nordrhein-Westfalen'], ['Frankfurt am Main', 'Hessen'],
            ['Stuttgart', 'Baden-Württemberg'], ['Leipzig', 'Sachsen'],
            ['Hannover', 'Niedersachsen'], ['Dresden', 'Sachsen'],
            ['Düsseldorf', 'Nordrhein-Westfalen'], ['Bremen', 'Bremen'],
            ['Nürnberg', 'Bayern'], ['Dortmund', 'Nordrhein-Westfalen'],
            ['Freiburg im Breisgau', 'Baden-Württemberg'], ['Kiel', 'Schleswig-Holstein'],
        ];
        $sectors = $this->sectors();

        $number = 0;
        foreach ($sectors as $sectorIndex => $sector) {
            foreach ($sector['roles'] as $roleIndex => [$titleFa, $titleDe]) {
                $number++;
                [$city, $state] = $locations[($sectorIndex + ($roleIndex * 4)) % count($locations)];
                $salaryFrom = 980 + (($sectorIndex * 37 + $roleIndex * 55) % 360);
                $salaryTo = $salaryFrom + 180 + ($roleIndex * 35);
                $publishedAt = now()->subDays(($number * 3) % 45);

                Opportunity::query()->updateOrCreate(
                    ['source_id' => $source->id, 'external_id' => sprintf('demo-opportunity-%03d', $number)],
                    [
                        'category_id' => $categoryIds[$sector['slug']],
                        'slug' => sprintf('%s-%s-%02d', $sector['slug'], strtolower(str_replace(' ', '-', $city)), $number),
                        'title_fa' => $titleFa,
                        'title_de' => $titleDe,
                        'employer_name' => $sector['employer'],
                        'description_fa' => sprintf(
                            '%s برای مرکز ما در %s. در این موقعیت با راهنمایی مربی حرفه‌ای، دانش نظری را در پروژه‌های واقعی به کار می‌گیرید. برنامه شامل آموزش ساختاریافته، بازخورد منظم، آشنایی با استانداردهای ایمنی و امکان ادامه همکاری پس از پایان موفق دوره است. شرایط: علاقه جدی به حوزه، مسئولیت‌پذیری، توانایی کار تیمی و آمادگی برای یادگیری مستمر.',
                            $titleFa,
                            $city,
                        ),
                        'description_de' => sprintf(
                            '%s an unserem Standort %s. Sie verbinden fundierte Theorie mit abwechslungsreicher Praxis, arbeiten begleitet durch erfahrene Ausbilderinnen und Ausbilder an realen Aufgaben und erhalten regelmäßiges Feedback. Wir bieten einen strukturierten Ausbildungsplan, moderne Arbeitsmittel und gute Übernahmechancen. Erwartet werden Lernbereitschaft, Zuverlässigkeit und Freude an Teamarbeit.',
                            $titleDe,
                            $city,
                        ),
                        'city' => $city,
                        'state' => $state,
                        'training_type' => ($number % 5 === 0) ? 'school' : 'dual',
                        'start_date' => now()->addMonths(3 + ($number % 9))->startOfMonth()->toDateString(),
                        'application_deadline' => now()->addDays(45 + (($number * 7) % 150))->toDateString(),
                        'monthly_salary_from' => $salaryFrom,
                        'monthly_salary_to' => $salaryTo,
                        'required_german_level' => ['a2', 'b1', 'b1', 'b2', 'c1'][$number % 5],
                        'education_requirement' => ($roleIndex % 2 === 0)
                            ? 'حداقل دیپلم یا مدرک تحصیلی معادل و قابل ارزیابی'
                            : 'پایان موفق دوره متوسطه، انگیزه‌نامه و علاقه اثبات‌شده به حوزه',
                        'skills' => array_values(array_unique([...$sector['skills'], 'Teamarbeit', $roleIndex % 2 === 0 ? 'Zuverlässigkeit' : 'Kommunikation'])),
                        'accepts_international' => $number % 4 !== 0,
                        'visa_support' => ['unknown', 'possible', 'yes', 'no'][$number % 4],
                        'application_url' => sprintf('https://example.test/opportunities/demo-%03d/apply', $number),
                        'contact_email' => sprintf('ausbildung%02d@example.test', $sectorIndex + 1),
                        'status' => $number % 13 === 0 ? 'draft' : 'published',
                        'published_at' => $number % 13 === 0 ? null : $publishedAt,
                        'source_updated_at' => $publishedAt,
                    ],
                );
            }
        }
    }

    /** @return array<int, array{slug: string, employer: string, skills: array<int, string>, roles: array<int, array{string, string}>}> */
    private function sectors(): array
    {
        return [
            ['slug' => 'software-it', 'employer' => 'NovaByte Solutions GmbH', 'skills' => ['PHP', 'JavaScript', 'Git'], 'roles' => [
                ['کارآموز توسعه نرم‌افزار وب', 'Ausbildung Fachinformatiker/in Anwendungsentwicklung'],
                ['کارآموز یکپارچه‌سازی سیستم‌ها', 'Ausbildung Fachinformatiker/in Systemintegration'],
                ['کارآموز تحلیل داده و فرایند', 'Ausbildung Daten- und Prozessanalyse'],
                ['کارآموز تضمین کیفیت نرم‌افزار', 'Ausbildung Softwarequalität und Testing'],
            ]],
            ['slug' => 'electrical-electronics', 'employer' => 'RheinVolt Elektrotechnik GmbH', 'skills' => ['Elektrotechnik', 'Schaltpläne', 'Sicherheit'], 'roles' => [
                ['کارآموز الکترونیک انرژی ساختمان', 'Elektroniker/in Energie- und Gebäudetechnik'],
                ['کارآموز الکترونیک اتوماسیون', 'Elektroniker/in Automatisierungstechnik'],
                ['کارآموز مونتاژ بردهای الکترونیکی', 'Ausbildung Elektronikgeräte und Systeme'],
                ['کارآموز تکنسین شبکه برق', 'Ausbildung Elektroniker/in Betriebstechnik'],
            ]],
            ['slug' => 'mechanical-industrial', 'employer' => 'HanseWerk Maschinenbau KG', 'skills' => ['Mechanik', 'CAD', 'Messtechnik'], 'roles' => [
                ['کارآموز مکاترونیک صنعتی', 'Ausbildung Mechatroniker/in'],
                ['کارآموز مکانیک ماشین‌آلات', 'Ausbildung Industriemechaniker/in'],
                ['کارآموز اپراتور دستگاه CNC', 'Ausbildung Zerspanungsmechaniker/in'],
                ['کارآموز طراحی فنی محصول', 'Ausbildung Technische/r Produktdesigner/in'],
            ]],
            ['slug' => 'accounting-finance', 'employer' => 'MainFinanz Beratung AG', 'skills' => ['Buchhaltung', 'Excel', 'Zahlenverständnis'], 'roles' => [
                ['کارآموز حسابداری مالی', 'Ausbildung Finanzbuchhaltung'],
                ['کارآموز امور بانکی', 'Ausbildung Bankkaufmann/-frau'],
                ['کارآموز مالیات', 'Ausbildung Steuerfachangestellte/r'],
                ['کارآموز بیمه و خدمات مالی', 'Ausbildung Kaufmann/-frau Versicherungen und Finanzanlagen'],
            ]],
            ['slug' => 'sales-marketing', 'employer' => 'ElbeMarkt Handelsgesellschaft mbH', 'skills' => ['Verkauf', 'Kundenberatung', 'Marketing'], 'roles' => [
                ['کارآموز فروش خرده‌فروشی', 'Ausbildung Kaufmann/-frau im Einzelhandel'],
                ['کارآموز بازاریابی دیجیتال', 'Ausbildung Kaufmann/-frau für Marketingkommunikation'],
                ['کارآموز فروش عمده', 'Ausbildung Groß- und Außenhandelsmanagement'],
                ['کارآموز مدیریت تجارت الکترونیک', 'Ausbildung Kaufmann/-frau im E-Commerce'],
            ]],
            ['slug' => 'nursing-care', 'employer' => 'Sonnenhof Pflegezentrum gGmbH', 'skills' => ['Patientenpflege', 'Empathie', 'Dokumentation'], 'roles' => [
                ['کارآموز پرستاری عمومی', 'Ausbildung Pflegefachfrau/Pflegefachmann'],
                ['کارآموز مراقبت سالمندان', 'Ausbildung Altenpflegehilfe'],
                ['کارآموز دستیار درمان', 'Ausbildung Medizinische/r Fachangestellte/r'],
                ['کارآموز مراقبت کودکان', 'Ausbildung Gesundheits- und Kinderkrankenpflege'],
            ]],
            ['slug' => 'hospitality-tourism', 'employer' => 'Alpenblick Hotels & Reisen GmbH', 'skills' => ['Gästebetreuung', 'Englisch', 'Service'], 'roles' => [
                ['کارآموز مدیریت هتل', 'Ausbildung Hotelfachmann/-frau'],
                ['کارآموز گردشگری', 'Ausbildung Tourismuskaufmann/-frau'],
                ['کارآموز خدمات رستوران', 'Ausbildung Fachmann/-frau Restaurants und Veranstaltungsgastronomie'],
                ['کارآموز مدیریت رویداد', 'Ausbildung Veranstaltungskaufmann/-frau'],
            ]],
            ['slug' => 'cooking-food', 'employer' => 'NordKüche Lebensmittel GmbH', 'skills' => ['Hygiene', 'Lebensmittelkunde', 'Küchenorganisation'], 'roles' => [
                ['کارآموز آشپزی', 'Ausbildung Koch/Köchin'],
                ['کارآموز قنادی', 'Ausbildung Konditor/in'],
                ['کارآموز نانوایی', 'Ausbildung Bäcker/in'],
                ['کارآموز فناوری مواد غذایی', 'Ausbildung Fachkraft für Lebensmitteltechnik'],
            ]],
            ['slug' => 'construction-architecture', 'employer' => 'BauPlan Süd GmbH', 'skills' => ['Bauzeichnung', 'Mathematik', 'Arbeitssicherheit'], 'roles' => [
                ['کارآموز نقشه‌کشی ساختمان', 'Ausbildung Bauzeichner/in'],
                ['کارآموز بنایی', 'Ausbildung Maurer/in'],
                ['کارآموز نجاری ساختمان', 'Ausbildung Zimmerer/Zimmerin'],
                ['کارآموز نقشه‌برداری', 'Ausbildung Vermessungstechniker/in'],
            ]],
            ['slug' => 'driving-transport', 'employer' => 'CityMobil Transport GmbH', 'skills' => ['Fahrzeugkontrolle', 'Routenplanung', 'Verantwortung'], 'roles' => [
                ['کارآموز راننده حرفه‌ای', 'Ausbildung Berufskraftfahrer/in'],
                ['کارآموز حمل‌ونقل ریلی', 'Ausbildung Eisenbahner/in im Betriebsdienst'],
                ['کارآموز خدمات حمل‌ونقل', 'Ausbildung Fachkraft im Fahrbetrieb'],
                ['کارآموز برنامه‌ریزی ترافیک', 'Ausbildung Kaufmann/-frau für Verkehrsservice'],
            ]],
            ['slug' => 'warehouse-logistics', 'employer' => 'LogiCore Deutschland GmbH', 'skills' => ['Lagerverwaltung', 'Kommissionierung', 'Warenkontrolle'], 'roles' => [
                ['کارآموز لجستیک انبار', 'Ausbildung Fachkraft für Lagerlogistik'],
                ['کارآموز اپراتور انبار', 'Ausbildung Fachlagerist/in'],
                ['کارآموز حمل‌ونقل و لجستیک', 'Ausbildung Kaufmann/-frau Spedition und Logistikdienstleistung'],
                ['کارآموز کنترل موجودی', 'Ausbildung Bestandsmanagement und Warenfluss'],
            ]],
            ['slug' => 'graphic-design', 'employer' => 'PixelWelle Kreativstudio GmbH', 'skills' => ['Adobe Creative Cloud', 'Typografie', 'Gestaltung'], 'roles' => [
                ['کارآموز طراحی رسانه دیجیتال', 'Ausbildung Mediengestalter/in Digital'],
                ['کارآموز طراحی رسانه چاپی', 'Ausbildung Mediengestalter/in Print'],
                ['کارآموز طراحی تصویر و صدا', 'Ausbildung Mediengestalter/in Bild und Ton'],
                ['کارآموز عکاسی تبلیغاتی', 'Ausbildung Fotograf/in Produkt und Werbung'],
            ]],
            ['slug' => 'human-resources', 'employer' => 'PeopleBridge Personalservice GmbH', 'skills' => ['Personalverwaltung', 'MS Office', 'Diskretion'], 'roles' => [
                ['کارآموز امور کارکنان', 'Ausbildung Personaldienstleistungskaufmann/-frau'],
                ['کارآموز جذب و استخدام', 'Ausbildung Recruiting und Personalmarketing'],
                ['کارآموز حقوق و دستمزد', 'Ausbildung Entgeltabrechnung'],
                ['کارآموز توسعه منابع انسانی', 'Ausbildung Personalentwicklung'],
            ]],
            ['slug' => 'technical-repair', 'employer' => 'FixPro Handwerk GmbH', 'skills' => ['Fehlersuche', 'Werkzeugkunde', 'Kundenservice'], 'roles' => [
                ['کارآموز مکانیک خودرو', 'Ausbildung Kraftfahrzeugmechatroniker/in'],
                ['کارآموز تأسیسات گرمایش', 'Ausbildung Anlagenmechaniker/in SHK'],
                ['کارآموز تعمیر لوازم خانگی', 'Ausbildung Elektroniker/in Geräte und Systeme'],
                ['کارآموز تعمیر دوچرخه', 'Ausbildung Zweiradmechatroniker/in'],
            ]],
            ['slug' => 'ausbildung', 'employer' => 'ZukunftCampus Ausbildung GmbH', 'skills' => ['Lernbereitschaft', 'Deutsch', 'Selbstorganisation'], 'roles' => [
                ['آوسبیلدونگ مدیریت اداری', 'Ausbildung Kaufmann/-frau für Büromanagement'],
                ['آوسبیلدونگ دستیار دندان‌پزشکی', 'Ausbildung Zahnmedizinische/r Fachangestellte/r'],
                ['آوسبیلدونگ تکنسین آزمایشگاه شیمی', 'Ausbildung Chemielaborant/in'],
                ['آوسبیلدونگ باغبانی و فضای سبز', 'Ausbildung Gärtner/in Garten- und Landschaftsbau'],
            ]],
        ];
    }
}
