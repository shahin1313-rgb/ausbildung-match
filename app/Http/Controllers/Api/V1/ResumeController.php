<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResumeUploadRequest;
use App\Models\Resume;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ResumeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'resumes' => $request->user()->resumes()->latest()->get(),
        ]);
    }

    public function store(ResumeUploadRequest $request): JsonResponse
    {
        $file = $request->file('resume');
        $extension = strtolower($file->getClientOriginalExtension());
        $originalName = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $originalName = preg_replace('/[\x00-\x1F\x7F]/u', '', $originalName) ?: 'resume.'.$extension;
        $path = $file->storeAs(
            (string) $request->user()->id,
            Str::uuid().'.'.$extension,
            'resumes'
        );
        abort_if($path === false, 500, 'ذخیره امن رزومه انجام نشد.');

        try {
            $resume = DB::transaction(function () use ($request, $file, $path, $originalName): Resume {
                $request->user()->resumes()->update(['is_primary' => false]);

                return $request->user()->resumes()->create([
                    'original_name' => Str::limit($originalName, 255, ''),
                    'disk' => 'resumes',
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size_bytes' => $file->getSize(),
                    'status' => 'uploaded',
                    'is_primary' => true,
                    'processing_consent_at' => now(),
                    'retention_until' => now()->addDays(max(1, (int) config('legal.resume_retention_days', 180))),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('resumes')->delete($path);
            throw $exception;
        }

        return response()->json([
            'message' => 'رزومه با موفقیت و به‌صورت خصوصی ذخیره شد.',
            'resume' => $resume,
        ], 201);
    }

    public function destroy(Request $request, Resume $resume): JsonResponse
    {
        abort_unless($resume->user_id === $request->user()->id, 404);

        $disk = Storage::disk($resume->disk);
        abort_if($disk->exists($resume->path) && ! $disk->delete($resume->path), 500, 'حذف امن فایل انجام نشد.');
        $resume->delete();

        return response()->json(['message' => 'رزومه حذف شد.']);
    }
}
