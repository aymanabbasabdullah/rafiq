<?php

namespace Tests\Feature;

use App\Enums\PublishStatus;
use App\Enums\ReviewStatus;
use App\Models\DoctorQuestionTemplateTranslation;
use App\Models\GlossaryTerm;
use App\Models\GlossaryTermTranslation;
use App\Models\User;
use Database\Seeders\DoctorPrepDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Module-11-B step 5: doctor question bank and glossary review — edit, submit, four-eyes medical approval,
 * publish only when approved, and edits that send an approved text back to draft.
 */
class TextBankTest extends TestCase
{
    use RefreshDatabase;

    private function bankText(): DoctorQuestionTemplateTranslation
    {
        $this->seed(DoctorPrepDemoSeeder::class);

        return DoctorQuestionTemplateTranslation::query()->where('locale', 'en')->orderBy('id')->firstOrFail();
    }

    private function url(string $name, DoctorQuestionTemplateTranslation|GlossaryTermTranslation $text, string $bank = 'doctor-questions', array $extra = []): string
    {
        return route("admin.content.bank.{$name}", ['bank' => $bank, 'text' => $text->id, ...$extra]);
    }

    public function test_content_roles_see_the_banks_and_others_cannot(): void
    {
        $this->bankText();

        $this->actingAs(User::factory()->contentEditor()->create())->get('/admin/content/banks/doctor-questions?locale=ar')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Content/TextBank')
                ->where('bank', 'doctor-questions')
                ->has('rows', 36)
                ->where('rows.0.locale', 'ar')
                ->where('rows.0.reviewStatus', 'draft')
                ->where('admin.links.glossary', route('admin.content.bank.index', ['bank' => 'glossary'])));

        $this->actingAs(User::factory()->create())->get('/admin/content/banks/glossary')->assertForbidden();
        $this->get('/admin/content/banks/unknown')->assertNotFound();
    }

    public function test_the_full_review_cycle_enforces_four_eyes_and_approval_before_publishing(): void
    {
        $text = $this->bankText();
        $editor = User::factory()->contentEditor()->create();
        $reviewer = User::factory()->medicalReviewer()->create();

        $this->actingAs($editor)->put($this->url('update', $text), ['primary' => 'What could be causing this?', 'secondary' => null])->assertRedirect();
        $this->assertSame($editor->id, $text->fresh()->last_editor_id);

        // Publishing or approving a draft is refused.
        $this->actingAs($editor)->post($this->url('action', $text, extra: ['action' => 'publish']))->assertSessionHasErrors('workflow');
        $this->actingAs($editor)->post($this->url('action', $text, extra: ['action' => 'submit']))->assertSessionHasNoErrors();
        $this->assertSame(ReviewStatus::PendingMedicalReview, $text->fresh()->review_status);

        // Editors cannot approve; a reviewer who edited the text cannot approve it either.
        $this->actingAs($editor)->post($this->url('action', $text, extra: ['action' => 'approve']))->assertForbidden();
        $text->forceFill(['last_editor_id' => $reviewer->id])->save();
        $this->actingAs($reviewer)->post($this->url('action', $text, extra: ['action' => 'approve']))->assertForbidden();
        $text->forceFill(['last_editor_id' => $editor->id])->save();

        $this->actingAs($reviewer)->post($this->url('action', $text, extra: ['action' => 'reject']), ['comment' => ''])->assertSessionHasErrors('comment');
        $this->actingAs($reviewer)->post($this->url('action', $text, extra: ['action' => 'approve']))->assertSessionHasNoErrors();

        $approved = $text->fresh();
        $this->assertSame(ReviewStatus::Approved, $approved->review_status);
        $this->assertSame($reviewer->id, $approved->medically_approved_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'doctor_question.approved', 'actor_id' => $reviewer->id]);

        $this->actingAs($editor)->post($this->url('action', $text, extra: ['action' => 'publish']))->assertSessionHasNoErrors();
        $this->assertSame(PublishStatus::Published, $text->fresh()->publish_status);

        // Editing an approved text sends it back to draft and clears the approval.
        $this->actingAs($editor)->put($this->url('update', $text), ['primary' => 'A changed question', 'secondary' => ''])->assertRedirect();
        $this->assertSame(ReviewStatus::Draft, $text->fresh()->review_status);
        $this->assertNull($text->fresh()->medically_approved_by);
    }

    public function test_approved_glossary_terms_render_markdown_and_can_be_explained_by_the_ai(): void
    {
        config(['rafiq.ai.enabled' => true]);
        $term = GlossaryTerm::factory()->create(['key' => 'varicocele']);
        $translation = GlossaryTermTranslation::query()->create([
            'glossary_term_id' => $term->id,
            'locale' => 'en',
            'term' => 'Varicocele',
            'definition_markdown' => 'Old text',
            'definition_html' => '<p>Old text</p>',
            'review_status' => ReviewStatus::Draft,
            'publish_status' => PublishStatus::Draft,
        ]);
        $editor = User::factory()->contentEditor()->create();
        $reviewer = User::factory()->medicalReviewer()->create();

        $this->actingAs($editor)->put($this->url('update', $translation, 'glossary'), ['primary' => 'Varicocele', 'secondary' => 'Enlarged veins in the **scrotum**.']);
        $this->assertStringContainsString('<strong>scrotum</strong>', (string) $translation->fresh()->definition_html);

        $this->actingAs($editor)->post($this->url('action', $translation, 'glossary', ['action' => 'submit']));
        $this->actingAs($reviewer)->post($this->url('action', $translation, 'glossary', ['action' => 'approve']));
        $this->actingAs($editor)->post($this->url('action', $translation, 'glossary', ['action' => 'publish']));
        $this->assertDatabaseHas('audit_logs', ['action' => 'glossary.published']);

        $this->withSession(['age_confirmed' => true])->get('/en/glossary')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('aiEnabled', true)
            ->where('terms.0.term', 'Varicocele')
            ->where('terms.0.termId', $term->id));
    }

    public function test_doctor_prep_offers_term_help_without_sending_the_list(): void
    {
        config(['rafiq.ai.enabled' => true]);

        $this->withSession(['age_confirmed' => true])->get('/en/doctor-prep')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('aiEnabled', true)
            ->where('glossaryUrl', route('glossary.index', ['locale' => 'en'])));
    }
}
