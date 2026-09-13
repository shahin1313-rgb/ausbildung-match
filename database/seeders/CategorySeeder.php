<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'software-it', 'name_fa' => 'برنامه‌نویسی و فناوری اطلاعات', 'name_de' => 'IT & Softwareentwicklung'],
            ['slug' => 'electrical-electronics', 'name_fa' => 'برق و الکترونیک', 'name_de' => 'Elektrotechnik & Elektronik'],
            ['slug' => 'mechanical-industrial', 'name_fa' => 'مکانیک و صنایع', 'name_de' => 'Mechanik & Industrie'],
            ['slug' => 'accounting-finance', 'name_fa' => 'حسابداری و امور مالی', 'name_de' => 'Buchhaltung & Finanzen'],
            ['slug' => 'sales-marketing', 'name_fa' => 'فروش و بازاریابی', 'name_de' => 'Vertrieb & Marketing'],
            ['slug' => 'nursing-care', 'name_fa' => 'پرستاری و مراقبت', 'name_de' => 'Pflege & Betreuung'],
            ['slug' => 'hospitality-tourism', 'name_fa' => 'هتلداری و گردشگری', 'name_de' => 'Hotellerie & Tourismus'],
            ['slug' => 'cooking-food', 'name_fa' => 'آشپزی و صنایع غذایی', 'name_de' => 'Küche & Lebensmittel'],
            ['slug' => 'construction-architecture', 'name_fa' => 'ساختمان و معماری', 'name_de' => 'Bau & Architektur'],
            ['slug' => 'driving-transport', 'name_fa' => 'رانندگی و حمل‌ونقل', 'name_de' => 'Fahren & Transport'],
            ['slug' => 'warehouse-logistics', 'name_fa' => 'انبارداری و لجستیک', 'name_de' => 'Lager & Logistik'],
            ['slug' => 'graphic-design', 'name_fa' => 'طراحی گرافیک', 'name_de' => 'Grafikdesign & Medien'],
            ['slug' => 'human-resources', 'name_fa' => 'منابع انسانی', 'name_de' => 'Personalwesen'],
            ['slug' => 'technical-repair', 'name_fa' => 'مشاغل فنی و تعمیرات', 'name_de' => 'Handwerk & Reparatur'],
            ['slug' => 'ausbildung', 'name_fa' => 'دوره‌های آوسبیلدونگ', 'name_de' => 'Ausbildungsplätze'],
        ];

        foreach ($categories as $index => $category) {
            Category::query()->updateOrCreate(['slug' => $category['slug']], [...$category, 'sort_order' => $index + 1, 'is_active' => true]);
        }
    }
}
