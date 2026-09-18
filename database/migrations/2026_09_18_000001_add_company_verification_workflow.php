<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('verification_method', 30)->nullable()->after('status');
            $table->text('verification_notes')->nullable()->after('verification_method');
            $table->timestamp('verified_at')->nullable()->after('verification_notes');
            $table->foreignId('verified_by')->nullable()->after('verified_at')
                ->constrained('users')->nullOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE companies MODIFY status ENUM('active', 'pending', 'under_review', 'verified', 'suspended') NOT NULL DEFAULT 'pending'");
            DB::table('companies')->where('status', 'active')->update([
                'status' => 'verified',
                'verification_method' => 'admin_review',
                'verified_at' => now(),
            ]);
            DB::statement("ALTER TABLE companies MODIFY status ENUM('pending', 'under_review', 'verified', 'suspended') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE companies MODIFY status ENUM('active', 'pending', 'under_review', 'verified', 'suspended') NOT NULL DEFAULT 'active'");
            DB::table('companies')->where('status', 'verified')->update(['status' => 'active']);
            DB::table('companies')->whereIn('status', ['pending', 'under_review'])->update(['status' => 'suspended']);
            DB::statement("ALTER TABLE companies MODIFY status ENUM('active', 'suspended') NOT NULL DEFAULT 'active'");
        }

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['verification_method', 'verification_notes', 'verified_at']);
        });
    }
};
