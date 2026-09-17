<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reporter_email')->nullable();
            $table->enum('reason', ['scam', 'broken_link', 'incorrect_info', 'expired', 'other']);
            $table->text('details')->nullable();
            $table->enum('status', ['pending', 'reviewing', 'resolved', 'dismissed'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->char('reporter_key', 64);
            $table->date('reported_on');
            $table->timestamps();

            $table->unique(
                ['opportunity_id', 'reporter_key', 'reported_on'],
                'opportunity_reports_daily_unique'
            );
            $table->index(['status', 'created_at'], 'opportunity_reports_moderation_index');
            $table->index(['opportunity_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_reports');
    }
};
