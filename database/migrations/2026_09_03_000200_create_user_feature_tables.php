<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('german_level', ['none', 'a1', 'a2', 'b1', 'b2', 'c1', 'c2'])->default('none');
            $table->enum('education_level', ['below_diploma', 'diploma', 'associate', 'bachelor', 'master', 'doctorate'])->nullable();
            $table->string('education_title')->nullable();
            $table->json('skills')->nullable();
            $table->json('preferred_category_ids')->nullable();
            $table->json('preferred_cities')->nullable();
            $table->unsignedTinyInteger('work_experience_years')->default(0);
            $table->boolean('relocation_ready')->default(true);
            $table->date('available_from')->nullable();
            $table->timestamps();
        });

        Schema::create('resumes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->enum('status', ['uploaded', 'processing', 'extracted', 'reviewed', 'failed'])->default('uploaded');
            $table->boolean('is_primary')->default(true)->index();
            $table->json('extracted_data')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('german_cvs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('headline')->nullable();
            $table->text('summary')->nullable();
            $table->json('contact')->nullable();
            $table->json('experiences')->nullable();
            $table->json('education')->nullable();
            $table->json('skills')->nullable();
            $table->json('languages')->nullable();
            $table->json('certificates')->nullable();
            $table->timestamps();
        });

        Schema::create('favorites', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'opportunity_id']);
        });

        Schema::create('application_clicks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->enum('channel', ['application_url', 'email'])->default('application_url');
            $table->timestamp('clicked_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_clicks');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('german_cvs');
        Schema::dropIfExists('resumes');
        Schema::dropIfExists('user_profiles');
    }
};
