<?php

namespace Tests\Feature;

use App\Enums\ReviewStatus;
use App\Enums\RulesetStatus;
use App\Models\ClinicalRuleVersion;
use App\Models\RulesetRelease;
use App\Models\User;
use App\Services\Clinical\ActiveRulesetResolver;
use App\Services\Clinical\RulesetImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Module-11-C: clinical rules review, release checklist, four-eyes approval and publishing.
 */
class ClinicalAdminTest extends TestCase
{
    use RefreshDatabase;

    private RulesetRelease $release;

    private User $superAdmin;

    private User $editor;

    private User $reviewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->release = app(RulesetImporter::class)->import(resource_path('clinical/rulesets/RS-2026.1'));
        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->editor = User::factory()->contentEditor()->create();
        $this->reviewer = User::factory()->medicalReviewer()->create();
    }

    private function version(string $code): ClinicalRuleVersion
    {
        return ClinicalRuleVersion::query()->whereHas('rule', fn ($query) => $query->where('code', $code))->firstOrFail();
    }

    private function releaseAction(string $action): string
    {
        return route('admin.clinical.releases.action', ['release' => $this->release, 'action' => $action]);
    }

    public function test_rules_and_releases_are_visible_to_clinical_roles_only(): void
    {
        $roleless = User::factory()->create();

        $this->actingAs($roleless)->get('/admin/clinical/rules')->assertForbidden();
        $this->actingAs($this->editor)->get('/admin/clinical/rules')->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Admin/Clinical/Rules')->has('rules', 20)->where('rules.0.code', 'CR-001'));
        $this->actingAs($this->reviewer)->get('/admin/clinical/releases')->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Admin/Clinical/Releases')
                ->where('selected.version', 'RS-2026.1')
                ->where('selected.status', 'draft')
                ->where('selected.checklist.rulesTotal', 20));
    }

    public function test_rule_page_shows_a_readable_condition_in_both_languages(): void
    {
        $this->actingAs($this->reviewer)->get(route('admin.clinical.rules.show', $this->version('CR-002')))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Clinical/RuleVersion')
                ->where('rule.priority', 'P0')
                ->where('rule.branches.0.readable.en', '«Did the pain start suddenly, and is it severe?» is "Yes"')
                ->where('rule.branches.0.readable.ar', '«هل بدأ الألم فجأة وهو شديد؟» = «نعم»')
                ->where('rule.branches.0.stop', true)
                ->where('can.submit', false));
    }

    public function test_rule_versions_go_through_submit_and_four_eyes_approval_with_a_comment(): void
    {
        $version = $this->version('CR-011');
        $url = fn (string $action): string => route('admin.clinical.rules.action', ['version' => $version, 'action' => $action]);

        $this->actingAs($this->reviewer)->post($url('submit'))->assertForbidden();
        $this->actingAs($this->editor)->post($url('submit'))->assertSessionHasNoErrors();
        $this->assertSame(ReviewStatus::PendingMedicalReview, $version->refresh()->status);

        $this->actingAs($this->editor)->post($url('approve'), ['comment' => 'Fine'])->assertForbidden();
        $this->actingAs(User::factory()->medicalReviewer(verified: false)->create())->post($url('approve'), ['comment' => 'Fine'])->assertForbidden();
        $this->actingAs($this->reviewer)->post($url('approve'))->assertSessionHasErrors('comment');
        $this->actingAs($this->reviewer)->post($url('approve'), ['comment' => 'Matches SRC-003.'])->assertSessionHasNoErrors();

        $version->refresh();
        $this->assertSame(ReviewStatus::Approved, $version->status);
        $this->assertSame($this->reviewer->id, $version->reviewed_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'clinical.rule.approved', 'actor_id' => $this->reviewer->id]);
    }

    public function test_a_release_cannot_be_submitted_before_its_test_cases_pass(): void
    {
        $this->actingAs($this->superAdmin)->post($this->releaseAction('submit'))->assertSessionHasErrors('workflow');

        $this->actingAs($this->editor)->post($this->releaseAction('run-tests'))->assertForbidden();
        $this->actingAs($this->reviewer)->post($this->releaseAction('run-tests'))->assertSessionHasNoErrors();

        $this->release->refresh();
        $this->assertTrue($this->release->testsPassed());
        $this->assertSame(45, $this->release->test_report['passed']);

        $this->actingAs($this->superAdmin)->post($this->releaseAction('submit'))->assertSessionHasNoErrors();
        $this->assertSame(RulesetStatus::PendingMedicalReview, $this->release->refresh()->status);
    }

    public function test_full_release_flow_ends_with_production_running_the_published_release(): void
    {
        ClinicalRuleVersion::query()->update(['status' => ReviewStatus::Approved, 'reviewed_by' => $this->reviewer->id, 'reviewed_at' => now()]);
        $this->actingAs($this->reviewer)->post($this->releaseAction('run-tests'));
        $this->actingAs($this->superAdmin)->post($this->releaseAction('submit'));

        // The release cannot be approved while its texts are unapproved, nor by its submitter.
        $this->actingAs($this->reviewer)->post($this->releaseAction('approve'), ['comment' => 'OK'])->assertSessionHasErrors('workflow');
        $this->actingAs($this->superAdmin)->post($this->releaseAction('approve-texts'))->assertForbidden();
        $this->actingAs($this->reviewer)->post($this->releaseAction('approve-texts'))->assertSessionHasNoErrors();
        $this->actingAs($this->reviewer)->post($this->releaseAction('approve'), ['comment' => 'Accepted for launch.'])->assertSessionHasNoErrors();
        $this->assertSame(RulesetStatus::Approved, $this->release->refresh()->status);

        // Publishing: Super Admin only, behind password confirmation.
        $publish = route('admin.clinical.releases.publish', $this->release);
        $this->actingAs($this->superAdmin)->post($publish)->assertRedirect(route('admin.password.confirm'));
        $this->actingAs($this->reviewer)->withSession(['auth.password_confirmed_at' => time()])->post($publish)->assertForbidden();
        $this->actingAs($this->superAdmin)->withSession(['auth.password_confirmed_at' => time()])->post($publish)->assertSessionHasNoErrors();

        $this->assertSame(RulesetStatus::Published, $this->release->refresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'clinical.release.published', 'actor_id' => $this->superAdmin->id]);

        $this->app['env'] = 'production';
        config(['rafiq.demo_mode' => false]);
        $resolver = new ActiveRulesetResolver;
        $this->assertSame('RS-2026.1', $resolver->resolve()->version);
        $this->assertFalse($resolver->resolve()->demo);
    }

    public function test_a_release_with_unapproved_rules_cannot_be_approved(): void
    {
        $this->actingAs($this->reviewer)->post($this->releaseAction('run-tests'));
        $this->actingAs($this->superAdmin)->post($this->releaseAction('submit'));
        $this->actingAs($this->reviewer)->post($this->releaseAction('approve-texts'));

        $this->actingAs($this->reviewer)->post($this->releaseAction('approve'), ['comment' => 'OK'])
            ->assertSessionHasErrors(['workflow' => __('admin.clinical.error.rules_not_approved')]);
        $this->assertSame(RulesetStatus::PendingMedicalReview, $this->release->refresh()->status);
    }
}
