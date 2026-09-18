<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('website')->nullable();
            $table->string('contact_email');
            $table->string('phone')->nullable();
            $table->string('city');
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'under_review', 'verified', 'suspended'])
                ->default('pending')
                ->index();
            $table->timestamps();
        });

        Schema::table('opportunities', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('source_id')->constrained()->nullOnDelete();
            $table->text('application_url')->nullable()->change();
            $table->index(['company_id', 'status']);
        });

        Schema::table('applications', function (Blueprint $table): void {
            $table->text('candidate_message')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn('candidate_message');
        });

        Schema::table('opportunities', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'status']);
            $table->dropConstrainedForeignId('company_id');
            $table->text('application_url')->nullable(false)->change();
        });

        Schema::dropIfExists('companies');
    }
};
