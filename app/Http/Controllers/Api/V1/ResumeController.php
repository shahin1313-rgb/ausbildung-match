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
        $extension = strtolower($file->extension() ?: 'bin');
        $path = $file->storeAs(
            'resumes/'.$request->user()->id,
            Str::uuid().'.'.$extension,
            'local'
        );

        try {
            $resume = DB::transaction(function () use ($request, $file, $path): Resume {
                $request->user()->resumes()->update(['is_primary' => false]);

                return $request->user()->resumes()->create([
                    'original_name' => Str::limit($file->getClientOriginalName(), 255, ''),
                    'disk' => 'local',
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
            Storage::disk('local')->delete($path);
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

        Storage::disk($resume->disk)->delete($resume->path);
        $resume->delete();

        return response()->json(['message' => 'رزومه حذف شد.']);
    }
}
