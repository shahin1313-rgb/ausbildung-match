<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_super_admin')->default(false)->after('is_admin')->index();
        });

        // Preserve admin access after deployment: the oldest existing admin becomes Super Admin.
        $firstAdminId = DB::table('users')
            ->where('is_admin', true)
            ->orderBy('id')
            ->value('id');

        if ($firstAdminId !== null) {
            DB::table('users')->where('id', $firstAdminId)->update(['is_super_admin' => true]);
        }

        Schema::create('admin_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 100)->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['is_super_admin']);
            $table->dropColumn('is_super_admin');
        });
    }
};
