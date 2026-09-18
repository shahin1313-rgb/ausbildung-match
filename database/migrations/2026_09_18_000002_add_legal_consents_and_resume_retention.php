<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('terms_accepted_at')->nullable()->after('email_verified_at');
            $table->timestamp('privacy_accepted_at')->nullable()->after('terms_accepted_at');
            $table->string('terms_version', 30)->nullable()->after('privacy_accepted_at');
            $table->string('privacy_version', 30)->nullable()->after('terms_version');
        });
        Schema::table('resumes', function (Blueprint $table): void {
            $table->timestamp('processing_consent_at')->nullable()->after('is_primary');
            $table->timestamp('retention_until')->nullable()->index()->after('processing_consent_at');
        });
        Schema::table('applications', function (Blueprint $table): void {
            $table->timestamp('data_sharing_consent_at')->nullable()->after('candidate_message');
        });

        $retentionDays = max(1, (int) config('legal.resume_retention_days', 180));
        DB::table('resumes')->orderBy('id')->chunkById(200, function ($resumes) use ($retentionDays): void {
            foreach ($resumes as $resume) {
                DB::table('resumes')->where('id', $resume->id)->update([
                    'retention_until' => Carbon::parse($resume->created_at)->addDays($retentionDays),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', fn (Blueprint $table) => $table->dropColumn('data_sharing_consent_at'));
        Schema::table('resumes', function (Blueprint $table): void {
            $table->dropIndex(['retention_until']);
            $table->dropColumn(['processing_consent_at', 'retention_until']);
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn([
            'terms_accepted_at', 'privacy_accepted_at', 'terms_version', 'privacy_version',
        ]));
    }
};
