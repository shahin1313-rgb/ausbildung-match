<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'it', 'name_fa' => 'فناوری اطلاعات', 'name_de' => 'IT & Informatik'],
            ['slug' => 'healthcare', 'name_fa' => 'سلامت و پرستاری', 'name_de' => 'Gesundheit & Pflege'],
            ['slug' => 'technical', 'name_fa' => 'فنی و مهندسی', 'name_de' => 'Technik & Industrie'],
            ['slug' => 'business', 'name_fa' => 'بازرگانی و اداری', 'name_de' => 'Kaufmännisch'],
            ['slug' => 'hospitality', 'name_fa' => 'هتلداری و گردشگری', 'name_de' => 'Hotel & Gastronomie'],
            ['slug' => 'crafts', 'name_fa' => 'صنایع دستی و ساخت', 'name_de' => 'Handwerk'],
        ];

        foreach ($categories as $index => $category) {
            Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                [...$category, 'sort_order' => $index + 1, 'is_active' => true]
            );
        }
    }
}
