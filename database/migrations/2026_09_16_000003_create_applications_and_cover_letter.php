<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('opened')->index();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('interview_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'opportunity_id']);
        });

        Schema::table('german_cvs', function (Blueprint $table): void {
            $table->longText('cover_letter')->nullable()->after('certificates');
        });
    }

    public function down(): void
    {
        Schema::table('german_cvs', function (Blueprint $table): void {
            $table->dropColumn('cover_letter');
        });

        Schema::dropIfExists('applications');
    }
};
