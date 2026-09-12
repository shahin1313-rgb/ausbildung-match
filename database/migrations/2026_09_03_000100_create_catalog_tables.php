<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name_fa');
            $table->string('name_de');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sources', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('base_url');
            $table->string('feed_url')->nullable();
            $table->enum('sync_method', ['manual', 'api', 'feed'])->default('manual');
            $table->json('field_map')->nullable();
            $table->boolean('is_enabled')->default(true)->index();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('opportunities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_id')->nullable();
            $table->string('slug')->unique();
            $table->string('title_fa');
            $table->string('title_de');
            $table->string('employer_name');
            $table->longText('description_fa');
            $table->longText('description_de')->nullable();
            $table->string('city')->index();
            $table->string('state')->nullable();
            $table->enum('training_type', ['dual', 'school'])->default('dual');
            $table->date('start_date')->nullable()->index();
            $table->date('application_deadline')->nullable()->index();
            $table->unsignedInteger('monthly_salary_from')->nullable();
            $table->unsignedInteger('monthly_salary_to')->nullable();
            $table->enum('required_german_level', ['a2', 'b1', 'b2', 'c1'])->default('b1');
            $table->string('education_requirement')->nullable();
            $table->json('skills')->nullable();
            $table->boolean('accepts_international')->default(false)->index();
            $table->enum('visa_support', ['unknown', 'no', 'possible', 'yes'])->default('unknown');
            $table->text('application_url');
            $table->string('contact_email')->nullable();
            $table->enum('status', ['draft', 'published', 'expired'])->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['source_id', 'external_id'], 'source_external_unique');
            $table->index(['status', 'accepts_international', 'application_deadline'], 'public_opportunity_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('sources');
        Schema::dropIfExists('categories');
    }
};
