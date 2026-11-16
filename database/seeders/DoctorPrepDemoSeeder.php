<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PublishStatus;
use App\Enums\ReviewStatus;
use App\Models\DoctorQuestionTemplate;
use Illuminate\Database\Seeder;

/**
 * Synthetic doctor-visit question bank and checklist (Module-10 §17 step 1).
 *
 * DRAFT – PENDING QUALIFIED MEDICAL REVIEW: items are published but NOT approved, so they appear only in demo
 * mode (with a draft notice) and never in production until a medical reviewer approves them.
 * Runs only in local/testing environments or when demo mode is enabled.
 */
class DoctorPrepDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) && ! config('rafiq.demo_mode')) {
            return;
        }

        $order = 0;

        foreach (self::QUESTIONS as $topic => $questions) {
            foreach ($questions as $index => [$en, $ar]) {
                $this->item("question.{$topic}.".($index + 1), DoctorQuestionTemplate::KIND_QUESTION, $topic, $order++, $en, $ar);
            }
        }

        foreach (self::BRING as $index => [$en, $ar]) {
            $this->item('bring.'.($index + 1), DoctorQuestionTemplate::KIND_BRING, null, $index, $en, $ar);
        }
    }

    private function item(string $key, string $kind, ?string $topic, int $order, string $en, string $ar): void
    {
        $template = DoctorQuestionTemplate::query()->updateOrCreate(['key' => $key], ['kind' => $kind, 'topic_key' => $topic, 'sort_order' => $order]);

        foreach (['en' => $en, 'ar' => $ar] as $locale => $text) {
            $template->translations()->updateOrCreate(['locale' => $locale], [
                'text' => $text,
                'review_status' => ReviewStatus::Draft,
                'publish_status' => PublishStatus::Published,
            ]);
        }
    }

    /**
     * @var array<string, list<array{0: string, 1: string}>>
     */
    private const QUESTIONS = [
        'pain_swelling' => [
            ['What could be causing the pain or swelling I have noticed?', 'ما الذي قد يسبب الألم أو التورم الذي لاحظته؟'],
            ['Which examinations or tests do you think I need?', 'ما الفحوصات أو التحاليل التي ترى أنني أحتاجها؟'],
            ['Which signs should make me go to the emergency department?', 'ما العلامات التي يجب أن تدفعني للذهاب إلى الطوارئ؟'],
            ['Is there anything I should avoid until we know the cause?', 'هل هناك ما يجب أن أتجنبه حتى نعرف السبب؟'],
            ['When should I come back if it does not get better?', 'متى يجب أن أعود إذا لم يتحسن الأمر؟'],
        ],
        'lump_change' => [
            ['What examination will you do to look at the lump or change?', 'ما الفحص الذي ستجريه لتقييم الكتلة أو التغيّر؟'],
            ['Do I need an ultrasound or other tests?', 'هل أحتاج إلى أشعة صوتية أو فحوص أخرى؟'],
            ['How should I check myself at home, and how often?', 'كيف أفحص نفسي في المنزل، وكم مرة؟'],
            ['What changes should make me contact you sooner?', 'ما التغيّرات التي تستدعي أن أتواصل معك أبكر؟'],
            ['When will I know the results, and how?', 'متى وكيف سأعرف النتائج؟'],
        ],
        'fertility' => [
            ['Should both my partner and I be assessed, and in what order?', 'هل يجب أن نُقيَّم أنا وشريكتي معاً، وبأي ترتيب؟'],
            ['Which tests are usually done first for men?', 'ما الفحوص التي تُجرى عادةً أولاً للرجل؟'],
            ['How should I prepare for a semen analysis?', 'كيف أستعد لتحليل السائل المنوي؟'],
            ['Could my health conditions or medicines affect fertility?', 'هل يمكن أن تؤثر حالتي الصحية أو أدويتي على الخصوبة؟'],
            ['Which lifestyle changes are worth making now?', 'ما التغييرات في نمط الحياة التي تستحق البدء بها الآن؟'],
        ],
        'sexual_urinary' => [
            ['What could be behind the symptoms I have noticed?', 'ما الذي قد يكون وراء الأعراض التي لاحظتها؟'],
            ['Do I need any urine or blood tests?', 'هل أحتاج إلى تحاليل بول أو دم؟'],
            ['Could any of my medicines be playing a part?', 'هل يمكن أن يكون لأي من أدويتي دور في ذلك؟'],
            ['What treatment options are usually available?', 'ما خيارات العلاج المتاحة عادةً؟'],
            ['Should my partner also be checked?', 'هل يجب أن تُفحص شريكتي أيضاً؟'],
        ],
        'medications_lifestyle' => [
            ['Can any of my current medicines affect my reproductive health?', 'هل يمكن لأي من أدويتي الحالية أن يؤثر على صحتي الإنجابية؟'],
            ['Is it safe to keep taking my medicines while we look into this?', 'هل من الآمن الاستمرار في أدويتي خلال متابعة هذا الأمر؟'],
            ['What support is available to help me stop smoking?', 'ما الدعم المتاح لمساعدتي على الإقلاع عن التدخين؟'],
            ['Is my work environment something we should discuss?', 'هل بيئة عملي أمر يجب أن نناقشه؟'],
            ['Which changes in sleep, weight or exercise would help most?', 'ما التغييرات في النوم أو الوزن أو الرياضة التي ستفيد أكثر؟'],
        ],
        'tests_results' => [
            ['Can you explain what my previous results mean for me?', 'هل يمكنك شرح ما تعنيه نتائجي السابقة بالنسبة لي؟'],
            ['Do any of the tests need to be repeated, and when?', 'هل يجب إعادة أي من الفحوص، ومتى؟'],
            ['What would the next step be after these results?', 'ما الخطوة التالية بعد هذه النتائج؟'],
            ['Should I see a specialist, and which one?', 'هل يجب أن أراجع اختصاصياً، وأي اختصاص؟'],
            ['Can I have a copy of my results?', 'هل يمكنني الحصول على نسخة من نتائجي؟'],
        ],
    ];

    /**
     * @var list<array{0: string, 1: string}>
     */
    private const BRING = [
        ['A list of all medicines and supplements you take, with doses', 'قائمة بكل الأدوية والمكملات التي تتناولها مع جرعاتها'],
        ['Any previous test results or reports', 'أي نتائج فحوص أو تقارير سابقة'],
        ['Notes on when your symptoms started and how they changed', 'ملاحظات عن بداية الأعراض وكيف تغيرت'],
        ['Your medical and surgical history, including childhood conditions', 'تاريخك الطبي والجراحي، بما في ذلك حالات الطفولة'],
        ['Your family health history, if you know it', 'التاريخ الصحي لعائلتك إن كنت تعرفه'],
        ['This list of questions', 'قائمة الأسئلة هذه'],
    ];
}
