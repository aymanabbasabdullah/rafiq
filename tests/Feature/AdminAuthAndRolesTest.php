<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\TotpService;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia;
use LogicException;
use Tests\TestCase;

/**
 * Module-11-A: Admin authentication, mandatory 2FA, RBAC roles, user management, four-eyes gate & audit logs.
 */
class AdminAuthAndRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_visitors_are_redirected_to_admin_login_and_admin_pages_are_noindex(): void
    {
        $this->get('/admin')
            ->assertRedirect(route('admin.login'));

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex">', false)
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Admin/Auth/Login'));
    }

    public function test_first_time_admin_must_complete_two_factor_setup_before_accessing_dashboard(): void
    {
        $admin = User::factory()->withoutTwoFactor()->superAdmin()->create([
            'password' => 'CorrectHorseBattery1!',
        ]);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'CorrectHorseBattery1!',
        ])->assertRedirect(route('admin.two-factor.setup'));

        // Attempting to bypass 2FA setup redirects right back to setup.
        $this->get('/admin')
            ->assertRedirect(route('admin.two-factor.setup'));

        $setupResponse = $this->get('/admin/two-factor-setup')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Auth/TwoFactorSetup')
                ->has('secret')
                ->has('provisioningUri'));

        /** @var string $secret */
        $secret = $setupResponse->viewData('page')['props']['secret'];
        $totp = app(TotpService::class);

        $this->post('/admin/two-factor-setup', [
            'code' => $totp->codeAt($secret),
        ])->assertRedirect(route('admin.dashboard'));

        $admin->refresh();
        $this->assertTrue($admin->hasTwoFactorEnabled());
        $this->assertCount(8, $admin->two_factor_recovery_codes ?? []);

        $this->get('/admin')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Dashboard')
                ->has('recoveryCodes', 8));

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'auth.two_factor.enabled',
        ]);
    }

    public function test_admin_with_two_factor_enabled_completes_totp_or_single_use_recovery_code_challenge(): void
    {
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();

        $admin = User::factory()->superAdmin()->create([
            'password' => 'CorrectHorseBattery1!',
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => ['ABCD-EFGH', 'JKLM-NPQR'],
            'two_factor_confirmed_at' => now(),
        ]);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'CorrectHorseBattery1!',
        ])->assertRedirect(route('admin.two-factor.challenge'));

        // Protected pages remain blocked while 2FA challenge is pending.
        $this->get('/admin')
            ->assertRedirect(route('admin.two-factor.challenge'));

        // Invalid TOTP code fails validation.
        $this->post('/admin/two-factor-challenge', [
            'code' => '000000',
        ])->assertSessionHasErrors('code');

        // Valid TOTP code completes login.
        $this->post('/admin/two-factor-challenge', [
            'code' => $totp->codeAt($secret),
        ])->assertRedirect(route('admin.dashboard'));

        $this->get('/admin')->assertOk();

        // Log out and test single-use recovery code.
        $this->post('/admin/logout')->assertRedirect(route('admin.login'));

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'CorrectHorseBattery1!',
        ])->assertRedirect(route('admin.two-factor.challenge'));

        $this->post('/admin/two-factor-challenge', [
            'recovery_code' => 'abcd-efgh',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'auth.two_factor.recovery_used',
        ]);

        // Reusing the same recovery code must fail.
        $this->post('/admin/logout');
        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'CorrectHorseBattery1!',
        ]);
        $this->post('/admin/two-factor-challenge', [
            'recovery_code' => 'ABCD-EFGH',
        ])->assertSessionHasErrors('code');
    }

    public function test_inactive_admin_cannot_log_in_and_deactivated_session_is_terminated_immediately(): void
    {
        $inactive = User::factory()->inactive()->superAdmin()->create([
            'password' => 'CorrectHorseBattery1!',
        ]);

        $this->post('/admin/login', [
            'email' => $inactive->email,
            'password' => 'CorrectHorseBattery1!',
        ])->assertSessionHasErrors('email');

        $active = User::factory()->superAdmin()->create();
        $this->actingAs($active)->get('/admin')->assertOk();

        $active->forceFill(['is_active' => false])->save();

        $this->actingAs($active)
            ->get('/admin')
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $admin = User::factory()->superAdmin()->create([
            'password' => 'CorrectHorseBattery1!',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', [
                'email' => $admin->email,
                'password' => 'WrongPassword123!',
            ])->assertSessionHasErrors('email');
        }

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'WrongPassword123!',
        ])->assertStatus(429);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login_failed',
        ]);
    }

    public function test_cli_command_creates_initial_super_admin_and_enforces_strong_password(): void
    {
        $this->artisan('rafiq:admin:create', [
            '--name' => '[SYNTHETIC] Initial Admin',
            '--email' => 'short@rafiq.test',
            '--password' => 'short',
        ])->assertExitCode(1);

        $this->artisan('rafiq:admin:create', [
            '--name' => '[SYNTHETIC] Initial Admin',
            '--email' => 'root@rafiq.test',
            '--password' => 'StrongPassphrase123!',
            '--locale' => 'ar',
        ])->assertExitCode(0);

        $user = User::query()->where('email', 'root@rafiq.test')->firstOrFail();
        $this->assertTrue($user->isSuperAdmin());
        $this->assertFalse($user->hasTwoFactorEnabled());

        $this->assertDatabaseHas('audit_logs', [
            'subject_id' => $user->id,
            'action' => 'user.created',
        ]);
    }

    public function test_only_super_admin_can_manage_users_and_password_confirmation_is_required(): void
    {
        $editor = User::factory()->contentEditor()->create();
        $reviewer = User::factory()->medicalReviewer()->create();
        $superAdmin = User::factory()->superAdmin()->create([
            'password' => 'CorrectHorseBattery1!',
        ]);

        // Without recent password confirmation, Super Admin is prompted to confirm password.
        $this->actingAs($superAdmin)
            ->get('/admin/users')
            ->assertRedirect(route('admin.password.confirm'));

        $this->actingAs($superAdmin)
            ->post('/admin/confirm-password', ['password' => 'CorrectHorseBattery1!'])
            ->assertRedirect(route('admin.users.index'));

        $this->actingAs($superAdmin)
            ->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Admin/Users/Index'));

        $this->actingAs($editor)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get('/admin/users')
            ->assertForbidden();

        $this->actingAs($reviewer)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_super_admin_user_lifecycle_enforces_credential_reference_and_protects_last_super_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin)->withSession(['auth.password_confirmed_at' => time()]);

        // Creating a Medical Reviewer without credential_reference fails (ADM-003).
        $this->post('/admin/users', [
            'name' => '[SYNTHETIC] Dr. Sara',
            'email' => 'sara@rafiq.test',
            'preferred_locale' => 'ar',
            'password' => 'ValidDoctorPass12!',
            'roles' => [AdminRole::MedicalReviewer->value],
            'credential_reference' => '',
        ])->assertSessionHasErrors('credential_reference');

        // Creating with credential_reference succeeds and verifies the role.
        $this->post('/admin/users', [
            'name' => '[SYNTHETIC] Dr. Sara',
            'email' => 'sara@rafiq.test',
            'preferred_locale' => 'ar',
            'password' => 'ValidDoctorPass12!',
            'roles' => [AdminRole::MedicalReviewer->value],
            'credential_reference' => 'SCFHS-998877',
        ])->assertRedirect(route('admin.users.index'));

        $doctor = User::query()->where('email', 'sara@rafiq.test')->firstOrFail();
        $this->assertTrue($doctor->hasVerifiedMedicalReviewerRole());

        // Super Admin cannot modify their own roles (BR-11-4).
        $this->put("/admin/users/{$superAdmin->id}/roles", [
            'roles' => [AdminRole::ContentEditor->value],
        ])->assertForbidden();

        // Super Admin cannot deactivate themselves (BR-11-4).
        $this->patch("/admin/users/{$superAdmin->id}/toggle-active")
            ->assertForbidden();

        // Super Admin can update another user's roles, toggle active, and reset 2FA.
        $this->put("/admin/users/{$doctor->id}/roles", [
            'roles' => [AdminRole::ContentEditor->value, AdminRole::MedicalReviewer->value],
            'credential_reference' => 'SCFHS-998877-UPDATED',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertTrue($doctor->fresh()->isContentEditor());

        $this->post("/admin/users/{$doctor->id}/reset-two-factor")
            ->assertRedirect(route('admin.users.index'));
        $this->assertFalse($doctor->fresh()->hasTwoFactorEnabled());

        $this->patch("/admin/users/{$doctor->id}/toggle-active")
            ->assertRedirect(route('admin.users.index'));
        $this->assertFalse($doctor->fresh()->is_active);
    }

    public function test_last_active_super_admin_cannot_be_deactivated_or_stripped_of_super_admin_role(): void
    {
        $firstSuper = User::factory()->superAdmin()->create();
        $secondSuper = User::factory()->superAdmin()->create();

        $this->actingAs($firstSuper)->withSession(['auth.password_confirmed_at' => time()]);

        // While two Super Admins are active, deactivating the second is allowed.
        $this->patch("/admin/users/{$secondSuper->id}/toggle-active")
            ->assertRedirect(route('admin.users.index'));
        $this->assertFalse($secondSuper->fresh()->is_active);

        // Reactivate second, then deactivate first, and verify the remaining active Super Admin cannot be deactivated.
        $this->patch("/admin/users/{$secondSuper->id}/toggle-active");
        $firstSuper->forceFill(['is_active' => false])->save();

        $this->assertFalse($secondSuper->fresh()->can('toggleActive', $secondSuper));
    }

    public function test_four_eyes_gate_requires_verified_medical_reviewer_and_forbids_self_review(): void
    {
        $author = User::factory()->contentEditor()->create();
        $unverifiedReviewer = User::factory()->medicalReviewer(false)->create();
        $verifiedReviewer = User::factory()->medicalReviewer(true)->create();
        $superAndReviewer = User::factory()->superAdmin()->medicalReviewer(true)->create();

        // Unverified Medical Reviewer cannot approve anyone's work (BR-11-5).
        $this->assertFalse(Gate::forUser($unverifiedReviewer)->allows('four-eyes', [$author->id, $author->id]));

        // Verified Medical Reviewer can approve another user's work.
        $this->assertTrue(Gate::forUser($verifiedReviewer)->allows('four-eyes', [$author->id, $author->id]));

        // Verified Medical Reviewer cannot approve their own authored or last-edited work (BR-11-1).
        $this->assertFalse(Gate::forUser($verifiedReviewer)->allows('four-eyes', [$verifiedReviewer->id, $author->id]));
        $this->assertFalse(Gate::forUser($verifiedReviewer)->allows('four-eyes', [$author->id, $verifiedReviewer->id]));

        // Even a user holding both Super Admin and Medical Reviewer cannot approve their own work (BR-11-1).
        $this->assertFalse(Gate::forUser($superAndReviewer)->allows('four-eyes', [$superAndReviewer->id, $author->id]));
        $this->assertTrue(Gate::forUser($superAndReviewer)->allows('four-eyes', [$author->id, $author->id]));
    }

    public function test_audit_logger_scrubs_sensitive_fields_hashes_ip_and_enforces_immutability_and_role_scoping(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $reviewer = User::factory()->medicalReviewer(true)->create();
        $editor = User::factory()->contentEditor()->create();

        $logger = app(AuditLogger::class);

        $authEntry = $logger->record('user.created', $editor, [
            'email' => $editor->email,
            'password' => 'SecretShouldNeverAppear',
            'two_factor_secret' => 'BASE32SECRET',
            'answers' => ['pain'],
        ], $superAdmin);

        $clinicalEntry = $logger->record('article.approved', null, [
            'slug' => 'pcos-overview',
        ], $reviewer);

        $this->assertSame('[REDACTED]', $authEntry->summary['password']);
        $this->assertSame('[REDACTED]', $authEntry->summary['two_factor_secret']);
        $this->assertSame('[REDACTED]', $authEntry->summary['answers']);
        $this->assertSame($editor->email, $authEntry->summary['email']);
        $this->assertNotNull($authEntry->ip_hash);
        $this->assertSame(64, strlen((string) $authEntry->ip_hash));

        // Content Editor cannot view audit log.
        $this->actingAs($editor)->get('/admin/audit-log')->assertForbidden();

        // Verified Medical Reviewer sees only review-related actions (article.approved), not user.created.
        $this->actingAs($reviewer)
            ->get('/admin/audit-log')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/AuditLog/Index')
                ->has('logs', 1)
                ->where('logs.0.id', $clinicalEntry->id));

        // Super Admin sees all audit logs and can filter by action.
        $this->actingAs($superAdmin)
            ->get('/admin/audit-log?action=user.created')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('logs', 1)
                ->where('logs.0.id', $authEntry->id));

        // Audit logs are append-only: updating throws LogicException.
        $this->expectException(LogicException::class);
        $authEntry->update(['action' => 'tampered']);
    }

    public function test_admin_can_reset_password_and_switch_preferred_locale(): void
    {
        $admin = User::factory()->superAdmin()->create([
            'preferred_locale' => 'ar',
        ]);

        $token = Password::broker()->createToken($admin);

        $this->post('/admin/reset-password', [
            'token' => $token,
            'email' => $admin->email,
            'password' => 'BrandNewPassphrase12!',
            'password_confirmation' => 'BrandNewPassphrase12!',
        ])->assertRedirect(route('admin.login'));

        $this->assertTrue(Hash::check('BrandNewPassphrase12!', $admin->fresh()->password));
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'auth.password_reset',
        ]);

        $this->actingAs($admin)
            ->patch('/admin/locale', ['locale' => 'en'])
            ->assertRedirect();

        $this->assertSame('en', $admin->fresh()->preferred_locale);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('locale.current', 'en')
                ->where('locale.direction', 'ltr'));
    }

    public function test_admin_seeder_creates_demo_accounts_with_ready_recovery_codes(): void
    {
        $this->seed(AdminSeeder::class);

        $this->post('/admin/login', [
            'email' => 'admin@rafiq.test',
            'password' => 'Password1234!',
        ])->assertRedirect(route('admin.two-factor.challenge'));

        $this->post('/admin/two-factor-challenge', [
            'code' => 'DEMO-0001',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get('/admin')->assertOk();
    }
}
