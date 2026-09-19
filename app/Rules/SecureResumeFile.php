<?php

namespace App\Rules;

use App\Services\ResumeMalwareScanner;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class SecureResumeFile implements ValidationRule
{
    private const MIME_TYPES = [
        'pdf' => ['application/pdf', 'application/x-pdf'],
        'doc' => ['application/msword', 'application/vnd.ms-office', 'application/x-ole-storage'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/x-zip-compressed',
        ],
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('فایل رزومه معتبر نیست.');

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());
        $mime = strtolower((string) $value->getMimeType());

        if (! isset(self::MIME_TYPES[$extension]) || ! in_array($mime, self::MIME_TYPES[$extension], true)) {
            $fail('محتوای واقعی فایل با پسوند آن مطابقت ندارد. فقط PDF، DOC و DOCX پذیرفته می‌شود.');

            return;
        }

        $handle = fopen($value->getRealPath(), 'rb');
        $signature = $handle ? fread($handle, 8) : false;
        if (is_resource($handle)) {
            fclose($handle);
        }

        $validSignature = match ($extension) {
            'pdf' => is_string($signature) && str_starts_with($signature, '%PDF-'),
            'doc' => $signature === "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1",
            'docx' => is_string($signature) && str_starts_with($signature, "PK\x03\x04"),
            default => false,
        };

        if (! $validSignature) {
            $fail('ساختار فایل رزومه معتبر نیست یا فایل تغییر نام داده شده است.');

            return;
        }

        if ($extension === 'docx') {
            $contents = file_get_contents($value->getRealPath());
            if (! is_string($contents)
                || ! str_contains($contents, '[Content_Types].xml')
                || ! str_contains($contents, 'word/document.xml')) {
                $fail('ساختار فایل DOCX معتبر نیست.');

                return;
            }
        }

        app(ResumeMalwareScanner::class)->ensureClean($value->getRealPath());
    }
}
