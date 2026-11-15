<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->string('preferred_locale', 5)->default('ar')->after('two_factor_confirmed_at');
            $table->boolean('is_active')->default(true)->index()->after('preferred_locale');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 32)->unique();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('granted_at');
            $table->timestamp('credential_verified_at')->nullable();
            $table->string('credential_reference')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64);
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('summary');
            $table->char('ip_hash', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['actor_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'preferred_locale',
                'is_active',
                'last_login_at',
            ]);
        });
    }
};
