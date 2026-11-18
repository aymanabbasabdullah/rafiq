<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AdminRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds the RBAC roles and, in local/demo environments, synthetic admin accounts for testing (Module-11-A).
 */
class AdminSeeder extends Seeder
{
    private const DEMO_SECRET = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';

    /**
     * @var list<string>
     */
    private const DEMO_RECOVERY_CODES = [
        'DEMO-0001',
        'DEMO-0002',
        'DEMO-0003',
        'DEMO-0004',
        'DEMO-0005',
        'DEMO-0006',
        'DEMO-0007',
        'DEMO-0008',
    ];

    public function run(): void
    {
        Role::ensureAllExist();

        // Known demo credentials must never exist on a reachable server, including the demo/staging site.
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $password = (string) env('RAFIQ_DEMO_ADMIN_PASSWORD', 'Password1234!');

        $superAdmin = User::query()->updateOrCreate(
            ['email' => 'admin@rafiq.test'],
            [
                'name' => '[SYNTHETIC] مدير النظام التجريبي',
                'password' => $password,
                'preferred_locale' => 'ar',
                'is_active' => true,
                'two_factor_secret' => self::DEMO_SECRET,
                'two_factor_recovery_codes' => self::DEMO_RECOVERY_CODES,
                'two_factor_confirmed_at' => now(),
            ],
        );
        $superAdmin->grantRole(AdminRole::SuperAdmin, $superAdmin);

        $editor = User::query()->updateOrCreate(
            ['email' => 'editor@rafiq.test'],
            [
                'name' => '[SYNTHETIC] محرر المحتوى التجريبي',
                'password' => $password,
                'preferred_locale' => 'ar',
                'is_active' => true,
                'two_factor_secret' => self::DEMO_SECRET,
                'two_factor_recovery_codes' => self::DEMO_RECOVERY_CODES,
                'two_factor_confirmed_at' => now(),
            ],
        );
        $editor->grantRole(AdminRole::ContentEditor, $superAdmin);

        $reviewer = User::query()->updateOrCreate(
            ['email' => 'reviewer@rafiq.test'],
            [
                'name' => '[SYNTHETIC] د. مراجع طبي تجريبي',
                'password' => $password,
                'preferred_locale' => 'ar',
                'is_active' => true,
                'two_factor_secret' => self::DEMO_SECRET,
                'two_factor_recovery_codes' => self::DEMO_RECOVERY_CODES,
                'two_factor_confirmed_at' => now(),
            ],
        );
        $reviewer->grantRole(
            role: AdminRole::MedicalReviewer,
            grantedBy: $superAdmin,
            credentialReference: 'SCFHS-DEMO-001',
            credentialVerifiedAt: now(),
        );
    }
}
