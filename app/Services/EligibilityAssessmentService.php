<?php

namespace App\Services;

class EligibilityAssessmentService
{
    private const LANGUAGE_RANK = [
        'none' => 0,
        'a1' => 1,
        'a2' => 2,
        'b1' => 3,
        'b2' => 4,
        'c1' => 5,
        'c2' => 6,
    ];

    private const EDUCATION_SCORE = [
        'below_diploma' => 5,
        'diploma' => 20,
        'associate' => 22,
        'bachelor' => 25,
        'master' => 25,
        'doctorate' => 25,
    ];

    /**
     * Calculate a transparent, non-binding readiness assessment.
     *
     * @param  array{age: int, education_level: string, german_level: string, work_experience_years: int}  $data
     */
    public function assess(array $data): array
    {
        $breakdown = [
            'age' => ['score' => $this->ageScore($data['age']), 'max' => 25],
            'education' => ['score' => self::EDUCATION_SCORE[$data['education_level']], 'max' => 25],
            'language' => ['score' => $this->languageScore($data['german_level']), 'max' => 30],
            'experience' => ['score' => $this->experienceScore($data['work_experience_years']), 'max' => 20],
        ];

        $score = (int) collect($breakdown)->sum('score');
        [$strengths, $missing, $improvements] = $this->feedback($data);

        return [
            'score' => $score,
            'level' => $this->level($score),
            'readiness' => $this->readiness($score, $data),
            'breakdown' => $breakdown,
            'strengths' => $strengths,
            'missing' => $missing,
            'improvements' => $improvements,
            'explanation' => 'امتیاز از چهار بخش سن (۲۵)، تحصیلات (۲۵)، زبان آلمانی (۳۰) و سابقه مرتبط (۲۰) محاسبه شده است.',
            'disclaimer' => 'این نتیجه فقط ارزیابی اولیه سامانه است و تضمین پذیرش، قرارداد آوسبیلدونگ یا صدور ویزا نیست. شرایط هر آگهی و نظر مراجع رسمی جداگانه بررسی می‌شود.',
        ];
    }

    private function ageScore(int $age): int
    {
        return match (true) {
            $age < 16 => 4,
            $age <= 24 => 25,
            $age <= 29 => 23,
            $age <= 34 => 20,
            $age <= 39 => 16,
            $age <= 45 => 12,
            default => 8,
        };
    }

    private function languageScore(string $level): int
    {
        return match ($level) {
            'none' => 0,
            'a1' => 6,
            'a2' => 14,
            'b1' => 24,
            'b2', 'c1', 'c2' => 30,
        };
    }

    private function experienceScore(int $years): int
    {
        return match (true) {
            $years === 0 => 8,
            $years === 1 => 13,
            $years <= 3 => 17,
            default => 20,
        };
    }

    private function level(int $score): array
    {
        return match (true) {
            $score >= 85 => ['code' => 'very_good', 'label' => 'خیلی خوب'],
            $score >= 70 => ['code' => 'good', 'label' => 'خوب'],
            $score >= 50 => ['code' => 'medium', 'label' => 'متوسط'],
            default => ['code' => 'weak', 'label' => 'نیازمند آماده‌سازی'],
        };
    }

    /**
     * @param  array{age: int, education_level: string, german_level: string, work_experience_years: int}  $data
     */
    private function readiness(int $score, array $data): array
    {
        $coreRequirementsMet = $data['age'] >= 16
            && $data['education_level'] !== 'below_diploma'
            && (self::LANGUAGE_RANK[$data['german_level']] ?? 0) >= self::LANGUAGE_RANK['b1'];

        if ($coreRequirementsMet && $score >= 75) {
            return [
                'code' => 'ready',
                'can_start' => true,
                'label' => 'آماده شروع اقدام',
                'summary' => 'می‌توانی جست‌وجوی هدفمند و ارسال درخواست برای آگهی‌های متناسب را شروع کنی.',
            ];
        }

        if ($score >= 50) {
            return [
                'code' => 'conditional',
                'can_start' => true,
                'label' => 'امکان اقدام با تقویت هم‌زمان',
                'summary' => 'امکان شروع مسیر وجود دارد؛ آگهی مناسب انتخاب کن و کمبودهای اعلام‌شده را هم‌زمان برطرف کن.',
            ];
        }

        return [
            'code' => 'preparation',
            'can_start' => false,
            'label' => 'ابتدا پیش‌نیازها را آماده کن',
            'summary' => 'قبل از ارسال گسترده درخواست، اقدام‌های پیشنهادی زیر شانس انتخاب فرصت مناسب را بیشتر می‌کند.',
        ];
    }

    /**
     * @param  array{age: int, education_level: string, german_level: string, work_experience_years: int}  $data
     * @return array{0: list<string>, 1: list<string>, 2: list<string>}
     */
    private function feedback(array $data): array
    {
        $strengths = [];
        $missing = [];
        $improvements = [];

        if ($data['age'] >= 16 && $data['age'] <= 34) {
            $strengths[] = 'از نظر بازه سنی، مانع آشکاری در ارزیابی اولیه دیده نمی‌شود.';
        } elseif ($data['age'] < 16) {
            $missing[] = 'برای شروع دوره در سن فعلی، شرایط اختصاصی آگهی و الزامات قانونی باید دقیق‌تر بررسی شود.';
            $improvements[] = 'فرصت‌های مناسب زمان پایان مدرسه را بررسی و برای زمان شروع دوره برنامه‌ریزی کن.';
        } else {
            $missing[] = 'ممکن است لازم باشد انگیزه تغییر مسیر شغلی و تناسب تجربه قبلی را روشن‌تر توضیح بدهی.';
            $improvements[] = 'در انگیزه‌نامه، دلیل انتخاب آوسبیلدونگ و ارتباط تجربه قبلی با رشته جدید را برجسته کن.';
        }

        if ($data['education_level'] === 'below_diploma') {
            $missing[] = 'مدرک تحصیلی باید با شرط تحصیلی هر آگهی جداگانه تطبیق داده شود.';
            $improvements[] = 'مدارک تحصیلی و ترجمه آن‌ها را آماده کن و فقط آگهی‌های سازگار با مدرکت را هدف بگیر.';
        } else {
            $strengths[] = 'حداقل یک مدرک پایان مدرسه یا مدرک بالاتر ثبت شده است.';
        }

        $languageRank = self::LANGUAGE_RANK[$data['german_level']] ?? 0;
        if ($languageRank >= self::LANGUAGE_RANK['b2']) {
            $strengths[] = 'سطح زبان ثبت‌شده برای دامنه گسترده‌تری از فرصت‌ها مناسب است.';
        } elseif ($languageRank >= self::LANGUAGE_RANK['b1']) {
            $strengths[] = 'سطح B1 امکان شروع جست‌وجوی هدفمند را فراهم می‌کند.';
            $improvements[] = 'برای فرصت‌هایی که B2 می‌خواهند، برنامه ارتقای زبان را ادامه بده.';
        } else {
            $missing[] = 'سطح زبان فعلی دامنه فرصت‌های قابل اقدام را محدود می‌کند.';
            $improvements[] = 'زبان آلمانی را دست‌کم تا B1 تقویت و مدرک معتبر آماده کن.';
        }

        if ($data['work_experience_years'] >= 1) {
            $strengths[] = 'سابقه مرتبط می‌تواند در رزومه و مصاحبه به‌عنوان مزیت ارائه شود.';
        } else {
            $missing[] = 'سابقه مرتبط یا تجربه عملی ثبت نشده است.';
            $improvements[] = 'پروژه شخصی، کارآموزی کوتاه، دوره عملی یا نمونه‌کار مرتبط اضافه کن.';
        }

        return [$strengths, $missing, array_values(array_unique($improvements))];
    }
}
