<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ArticleKind;
use App\Enums\EvidenceType;
use App\Enums\PublishStatus;
use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\ContentCategory;
use App\Models\GlossaryTerm;
use App\Models\Source;
use App\Models\User;
use App\Services\Content\ChunkBuilder;
use App\Services\Content\MarkdownRenderer;
use Illuminate\Database\Seeder;

/**
 * Demo Knowledge Center content (Module-08, PRIV-010).
 *
 * The text is general education limited to what the verified sources in
 * docs/04-clinical/Medical-Sources-Register.md state. It is NOT medically approved:
 * it is marked approved only so the demo works, and the public review stamp says
 * "demo review — not yet medically approved" whenever demo mode is on.
 * Runs only in local/testing environments or when demo mode is enabled.
 */
class ContentDemoSeeder extends Seeder
{
    public function run(MarkdownRenderer $renderer, ChunkBuilder $chunks): void
    {
        if (! app()->environment(['local', 'testing']) && ! config('rafiq.demo_mode')) {
            return;
        }

        $reviewer = User::query()->where('email', 'reviewer@rafiq.test')->first();
        $editor = User::query()->where('email', 'editor@rafiq.test')->first();
        $sources = $this->seedSources();
        $categories = $this->seedCategories();

        foreach ($this->articles() as $definition) {
            $article = Article::query()->updateOrCreate(['key' => $definition['key']], [
                'content_category_id' => $categories[$definition['category']]->id,
                'kind' => $definition['kind'],
                'ai_enabled' => true,
                'sort_order' => $definition['sort'],
                'created_by' => $editor?->id,
            ]);

            $article->sources()->sync(collect($definition['sources'])->mapWithKeys(fn (string $support, string $sourceKey): array => [
                $sources[$sourceKey]->id => ['support_level' => $support, 'claim_note' => null],
            ])->all());

            foreach ($definition['translations'] as $locale => $text) {
                $translation = $article->translations()->updateOrCreate(['locale' => $locale], [
                    'title' => $text['title'],
                    'slug' => $text['slug'],
                    'summary' => $text['summary'],
                    'body_markdown' => $text['body'],
                    'review_status' => ReviewStatus::Approved,
                    'publish_status' => PublishStatus::Published,
                    'version' => 1,
                    'author_id' => $editor?->id,
                    'last_editor_id' => $editor?->id,
                    'medically_approved_by' => $reviewer?->id,
                    'medically_approved_at' => now(),
                    'review_due_at' => now()->addYear()->toDateString(),
                    'review_comment' => null,
                    'published_at' => now(),
                ]);

                $translation->revisions()->getQuery()->delete();

                $revision = ArticleRevision::query()->create([
                    'article_translation_id' => $translation->id,
                    'version' => 1,
                    'title' => $text['title'],
                    'slug' => $text['slug'],
                    'summary' => $text['summary'],
                    'body_markdown' => $text['body'],
                    'body_html' => $renderer->toHtml($text['body']),
                    'approved_by' => $reviewer?->id,
                    'approved_at' => now(),
                    'change_note' => '[SYNTHETIC] demo content — pending qualified medical review',
                ]);

                $translation->forceFill(['published_revision_id' => $revision->id])->save();
                $chunks->rebuild($translation, $revision);
            }
        }

        $this->seedGlossary($renderer, $reviewer);
    }

    /**
     * @return array<string, Source>
     */
    private function seedSources(): array
    {
        $types = [
            'SRC-001' => [EvidenceType::PublicHealthBody, false, null],
            'SRC-002' => [EvidenceType::PublicHealthBody, false, 'Next review was due 2026-07-18 at access — re-check.'],
            'SRC-003' => [EvidenceType::ClinicalGuideline, true, 'Reuse terms not verified; not fed to AI as a precaution.'],
            'SRC-004' => [EvidenceType::ClinicalGuideline, true, 'Recommendations behind login; publisher restricts use in AI tools.'],
            'SRC-005' => [EvidenceType::ClinicalGuideline, false, 'Full text not yet reviewed.'],
        ];

        $sources = [];

        foreach ((array) config('rafiq.sources') as $entry) {
            [$type, $aiRestricted, $note] = $types[$entry['id']] ?? [EvidenceType::Other, false, null];

            $sources[$entry['id']] = Source::query()->updateOrCreate(['url' => $entry['url']], [
                'title' => $entry['title'],
                'publisher' => $entry['publisher'],
                'publication_date' => $entry['date'],
                'accessed_at' => '2026-11-15',
                'evidence_type' => $type,
                'access_note' => $note,
                'ai_use_restricted' => $aiRestricted,
            ]);
        }

        return $sources;
    }

    /**
     * @return array<string, ContentCategory>
     */
    private function seedCategories(): array
    {
        $definitions = [
            'warning-signs' => ['triangle-alert', 1, ['ar' => ['الألم وعلامات الخطر', 'alam-wa-alamat-alkhatar', 'متى يكون الألم أو التورم سبباً لطلب رعاية عاجلة.'], 'en' => ['Pain and warning signs', 'pain-and-warning-signs', 'When pain or swelling means you should seek care quickly.']]],
            'fertility' => ['sprout', 2, ['ar' => ['الخصوبة والاستعداد للإنجاب', 'alkhusuba', 'متى قد يكون التقييم الطبي مناسباً وكيف تستعد له.'], 'en' => ['Fertility and conception', 'fertility', 'When a medical evaluation may help and how to prepare.']]],
            'lifestyle' => ['leaf', 3, ['ar' => ['نمط الحياة والأدوية', 'namat-alhayat', 'عادات ومواد قد تؤثر على الصحة الإنجابية.'], 'en' => ['Lifestyle and medicines', 'lifestyle', 'Habits and substances that can affect reproductive health.']]],
            'about-rafiq' => ['info', 4, ['ar' => ['عن رفيق', 'an-rafiq', 'كيف تعمل المنصة وما الذي تقدمه.'], 'en' => ['About Rafiq', 'about-rafiq', 'How the platform works and what it offers.']]],
        ];

        $categories = [];

        foreach ($definitions as $key => [$icon, $sort, $translations]) {
            $category = ContentCategory::query()->updateOrCreate(['key' => $key], [
                'icon' => $icon, 'sort_order' => $sort, 'is_visible' => true, 'ai_enabled' => true,
            ]);

            foreach ($translations as $locale => [$name, $slug, $description]) {
                $category->translations()->updateOrCreate(['locale' => $locale], [
                    'name' => $name, 'slug' => $slug, 'description' => $description,
                ]);
            }

            $categories[$key] = $category;
        }

        return $categories;
    }

    private function seedGlossary(MarkdownRenderer $renderer, ?User $reviewer): void
    {
        $terms = [
            'semen-analysis' => [
                'ar' => ['تحليل السائل المنوي', 'فحص مخبري لعينة من السائل المنوي، وهو من الفحوص الأساسية عند تقييم الخصوبة لدى الرجل. يفسّر الطبيب نتيجته حسب الحالة.'],
                'en' => ['Semen analysis', 'A laboratory test of a semen sample and one of the basic tests when assessing male fertility. A doctor interprets the result for each person.'],
            ],
            'varicocele' => [
                'ar' => ['دوالي الخصية', 'توسّع في الأوردة داخل كيس الصفن. قد لا يسبب أعراضاً، ويرتبط أحياناً بانخفاض الخصوبة.'],
                'en' => ['Varicocele', 'Enlarged veins inside the scrotum. It may cause no symptoms and is sometimes linked to reduced fertility.'],
            ],
            'undescended-testis' => [
                'ar' => ['الخصية المعلّقة', 'خصية لم تنزل إلى كيس الصفن في الطفولة. يُنصح من لديه هذا التاريخ بالحديث مع طبيبه وتعلّم الفحص الذاتي.'],
                'en' => ['Undescended testis', 'A testicle that did not move down into the scrotum in childhood. People with this history are advised to talk to their doctor and learn self-examination.'],
            ],
            'testicular-torsion' => [
                'ar' => ['التواء الخصية', 'حالة طارئة يلتف فيها الحبل المنوي فيقطع تدفق الدم، وقد تسبب ألماً مفاجئاً وشديداً. تحتاج علاجاً عاجلاً في المستشفى.'],
                'en' => ['Testicular torsion', 'An emergency where the spermatic cord twists and cuts off blood flow, which can cause sudden, severe pain. It needs urgent hospital treatment.'],
            ],
            'infertility' => [
                'ar' => ['العقم (تأخر الإنجاب)', 'يُعرَّف عادةً بعدم حدوث حمل بعد 12 شهراً من الجماع المنتظم دون وسائل منع. لا يحدده رفيق، بل الطبيب بعد التقييم.'],
                'en' => ['Infertility', 'Usually defined as no pregnancy after 12 months of regular unprotected intercourse. Rafiq does not determine it — a doctor does after evaluation.'],
            ],
        ];

        foreach ($terms as $key => $translations) {
            $term = GlossaryTerm::query()->updateOrCreate(['key' => $key]);

            foreach ($translations as $locale => [$label, $definition]) {
                $term->translations()->updateOrCreate(['locale' => $locale], [
                    'term' => $label,
                    'definition_markdown' => $definition,
                    'definition_html' => $renderer->toHtml($definition),
                    'review_status' => ReviewStatus::Approved,
                    'publish_status' => PublishStatus::Published,
                    'medically_approved_by' => $reviewer?->id,
                    'medically_approved_at' => now(),
                ]);
            }
        }
    }

    /**
     * @return list<array{key: string, category: string, kind: ArticleKind, sort: int, sources: array<string, string>, translations: array<string, array{title: string, slug: string, summary: string, body: string}>}>
     */
    private function articles(): array
    {
        return [
            [
                'key' => 'testicular-pain-when-urgent',
                'category' => 'warning-signs',
                'kind' => ArticleKind::Article,
                'sort' => 1,
                'sources' => ['SRC-001' => 'direct', 'SRC-002' => 'direct'],
                'translations' => [
                    'ar' => [
                        'title' => 'ألم الخصية: متى يكون حالة طارئة؟',
                        'slug' => 'alam-alkhusya-mata-yakun-tari',
                        'summary' => 'بعض أنواع ألم الخصية تحتاج علاجاً عاجلاً في المستشفى. تعرّف على العلامات التي لا تحتمل الانتظار.',
                        'body' => "## علامات تستدعي الطوارئ فوراً\n\nاتصل بالطوارئ أو اذهب إلى أقرب قسم طوارئ الآن إذا كان لديك:\n\n- ألم مفاجئ وشديد في الخصية.\n- ألم في الخصية مع غثيان أو قيء أو ألم في البطن.\n- ألم في الخصية يستمر أكثر من ساعة أو يستمر وأنت مرتاح.\n\nقد تدل هذه العلامات على مشكلة تحتاج علاجاً سريعاً في المستشفى. **لا تقد السيارة بنفسك**؛ اطلب من أحد إيصالك أو اتصل بالإسعاف، وأحضر معك قائمة بأدويتك.\n\n## علامات تستدعي مراجعة الطبيب قريباً\n\nاطلب موعداً عاجلاً مع طبيب إذا لاحظت:\n\n- انزعاجاً أو ألماً في الخصيتين يقلقك.\n- كتلة أو تورماً.\n- تغيراً في شكل الخصية أو ملمسها، أو أن إحداهما أصبحت أكبر من الأخرى.\n\n## لا تشخّص نفسك\n\nأسباب ألم الخصية كثيرة، والطبيب وحده يستطيع تحديد السبب بعد الفحص.",
                    ],
                    'en' => [
                        'title' => 'Testicular pain: when is it an emergency?',
                        'slug' => 'testicular-pain-when-is-it-an-emergency',
                        'summary' => 'Some testicular pain needs urgent hospital treatment. Learn the signs that should not wait.',
                        'body' => "## Signs that need emergency care now\n\nCall emergency services or go to the nearest emergency department now if you have:\n\n- Sudden, severe pain in a testicle.\n- Testicle pain with feeling sick, being sick or tummy pain.\n- Testicle pain that lasts more than an hour or continues while you rest.\n\nThese can be signs of a problem that needs quick hospital treatment. **Don't drive yourself** — ask someone to take you or call an ambulance, and bring a list of any medicines you take.\n\n## Signs that need a doctor soon\n\nAsk for an urgent appointment with a doctor if you notice:\n\n- Aching or discomfort in your testicles that worries you.\n- A lump or swelling.\n- A change in the shape or feel of a testicle, or one becoming bigger than the other.\n\n## Don't diagnose yourself\n\nTesticle pain has many possible causes, and only a doctor can find the cause after an examination.",
                    ],
                ],
            ],
            [
                'key' => 'testicular-lumps-and-swellings',
                'category' => 'warning-signs',
                'kind' => ArticleKind::Article,
                'sort' => 2,
                'sources' => ['SRC-002' => 'direct'],
                'translations' => [
                    'ar' => [
                        'title' => 'الكتل والتورم في الخصية',
                        'slug' => 'alkutal-wa-altawarrum-fi-alkhusya',
                        'summary' => 'معظم كتل الخصية لها أسباب غير خطيرة، لكن الفحص الطبي هو الطريق الوحيد للتأكد.',
                        'body' => "## ماذا أفعل إذا لاحظت كتلة؟\n\nراجع الطبيب إذا لاحظت كتلة أو تورماً أو تغيراً في شكل الخصية أو ملمسها، أو إذا أصبحت إحدى الخصيتين أكبر من الأخرى، أو إذا كان لديك انزعاج لا يزول.\n\n## هل الكتل خطيرة؟\n\nكثير من الكتل والتورمات سببها حالات غير ضارة، مثل تجمع سائل أو توسع في الأوردة. ومع ذلك لا يمكن معرفة السبب دون فحص، ولذلك **لا تحاول تشخيص نفسك**.\n\n## متى تذهب إلى الطوارئ؟\n\nإذا رافق الكتلة أو التورم ألم مفاجئ وشديد، أو ألم مع غثيان أو قيء أو ألم في البطن، فاتصل بالطوارئ فوراً.",
                    ],
                    'en' => [
                        'title' => 'Testicle lumps and swellings',
                        'slug' => 'testicle-lumps-and-swellings',
                        'summary' => 'Most testicle lumps have harmless causes, but a medical examination is the only way to be sure.',
                        'body' => "## What should I do if I notice a lump?\n\nSee a doctor if you notice a lump, swelling or a change in the shape or feel of a testicle, if one testicle becomes bigger than the other, or if you have discomfort that doesn't go away.\n\n## Are lumps serious?\n\nMany lumps and swellings are caused by harmless conditions, such as a build-up of fluid or swollen veins. But the cause can't be known without an examination, so **don't try to diagnose yourself**.\n\n## When should I go to an emergency department?\n\nIf a lump or swelling comes with sudden severe pain, or pain with feeling sick, being sick or tummy pain, call emergency services straight away.",
                    ],
                ],
            ],
            [
                'key' => 'when-to-seek-fertility-evaluation',
                'category' => 'fertility',
                'kind' => ArticleKind::Article,
                'sort' => 1,
                'sources' => ['SRC-003' => 'direct', 'SRC-005' => 'needs_review'],
                'translations' => [
                    'ar' => [
                        'title' => 'متى يُنصح بتقييم الخصوبة؟',
                        'slug' => 'mata-yunsah-bitaqyim-alkhusuba',
                        'summary' => 'تأخر الحمل أمر شائع، والتقييم الطبي للطرفين خطوة روتينية وليس حكماً على أحد.',
                        'body' => "## ما المقصود بتأخر الإنجاب؟\n\nيُعرَّف عادةً بعدم حدوث حمل بعد 12 شهراً من الجماع المنتظم دون استخدام وسائل منع الحمل. ويسهم العامل الذكوري كلياً أو جزئياً في نحو نصف الحالات لدى الأزواج.\n\n## متى يُقيَّم الرجل؟\n\nبعد 12 شهراً من المحاولة المنتظمة دون حمل، يُنصح عادةً بتقييم الرجل والمرأة معاً، بالتوازي. هذا إجراء روتيني ولا يعني بالضرورة وجود مشكلة.\n\n## ماذا يشمل التقييم عادةً؟\n\n- أسئلة عن التاريخ الطبي والإنجابي والعائلي ونمط الحياة.\n- فحص سريري.\n- تحليل السائل المنوي.\n\nيقرر الطبيب الفحوص المناسبة لكل شخص، ورفيق لا يقيّم الخصوبة ولا يفسّر النتائج.",
                    ],
                    'en' => [
                        'title' => 'When is a fertility evaluation recommended?',
                        'slug' => 'when-is-a-fertility-evaluation-recommended',
                        'summary' => 'Delays in getting pregnant are common, and evaluating both partners is a routine step — not a judgement on anyone.',
                        'body' => "## What does infertility mean?\n\nIt is usually defined as no pregnancy after 12 months of regular intercourse without contraception. A male factor contributes, fully or partly, in about half of couples.\n\n## When is the man evaluated?\n\nAfter 12 months of regular trying without a pregnancy, both partners are usually advised to be evaluated in parallel. This is routine and does not necessarily mean there is a problem.\n\n## What does an evaluation usually include?\n\n- Questions about medical, reproductive and family history and lifestyle.\n- A physical examination.\n- A semen analysis.\n\nA doctor decides which tests suit each person. Rafiq does not assess fertility or interpret results.",
                    ],
                ],
            ],
            [
                'key' => 'smoking-and-reproductive-health',
                'category' => 'lifestyle',
                'kind' => ArticleKind::Article,
                'sort' => 1,
                'sources' => ['SRC-003' => 'direct', 'SRC-005' => 'needs_review'],
                'translations' => [
                    'ar' => [
                        'title' => 'التدخين والصحة الإنجابية',
                        'slug' => 'altadkhin-wa-alsiha-alinjabiya',
                        'summary' => 'التدخين من العادات المرتبطة بتأثيرات سلبية على الصحة الإنجابية لدى الرجل.',
                        'body' => "## ماذا نعرف؟\n\nيرتبط التدخين بنمط حياة أقل صحة، وبزيادة تضرر المادة الوراثية (DNA) في الحيوانات المنوية. ولهذا يُسأل عنه عادةً عند تقييم الخصوبة.\n\n## ماذا يمكنني أن أفعل؟\n\n- إذا كنت تفكر في الإقلاع، يمكن لطبيبك أو لخدمات الإقلاع عن التدخين مساعدتك.\n- أخبر طبيبك بعاداتك بصراحة؛ ذلك يساعده على تقديم نصيحة مناسبة لك.\n\n> هذا المقال معلومات عامة وليس تقييماً لحالتك.",
                    ],
                    'en' => [
                        'title' => 'Smoking and reproductive health',
                        'slug' => 'smoking-and-reproductive-health',
                        'summary' => 'Smoking is one of the habits linked to negative effects on male reproductive health.',
                        'body' => "## What do we know?\n\nSmoking is linked to a less healthy lifestyle and to more damage to the genetic material (DNA) in sperm. That is why it is usually asked about during a fertility evaluation.\n\n## What can I do?\n\n- If you are thinking about quitting, your doctor or a stop-smoking service can help.\n- Be open with your doctor about your habits; it helps them give advice that fits you.\n\n> This article is general information, not an assessment of your situation.",
                    ],
                ],
            ],
            [
                'key' => 'anabolic-steroids-and-fertility',
                'category' => 'lifestyle',
                'kind' => ArticleKind::Article,
                'sort' => 2,
                'sources' => ['SRC-003' => 'direct'],
                'translations' => [
                    'ar' => [
                        'title' => 'الستيرويدات البنائية والتستوستيرون والخصوبة',
                        'slug' => 'alsteroidat-albina-iya-wa-alkhusuba',
                        'summary' => 'بعض المواد، مثل الستيرويدات البنائية والتستوستيرون، تُصنَّف ضمن ما قد يضر بإنتاج الحيوانات المنوية.',
                        'body' => "## لماذا يهم هذا؟\n\nتُدرج الإرشادات الطبية الستيرويدات البنائية والتستوستيرون ضمن الأدوية التي قد تؤثر سلباً على الخصيتين وإنتاج الحيوانات المنوية.\n\n## ماذا أفعل إذا كنت أستخدمها أو استخدمتها سابقاً؟\n\n- أخبر طبيبك بذلك، خاصة إذا كنت تخطط للإنجاب.\n- **لا تغيّر أي علاج موصوف لك أو توقفه دون استشارة طبيبك.**\n\nرفيق لا يقدم توصيات علاجية؛ الطبيب هو من يقيّم حالتك ويقترح الخطوة المناسبة.",
                    ],
                    'en' => [
                        'title' => 'Anabolic steroids, testosterone and fertility',
                        'slug' => 'anabolic-steroids-testosterone-and-fertility',
                        'summary' => 'Some substances, such as anabolic steroids and testosterone, are classed among those that can harm sperm production.',
                        'body' => "## Why does this matter?\n\nMedical guidelines list anabolic steroids and testosterone among medicines that can harm the testicles and sperm production.\n\n## What should I do if I use them or have used them?\n\n- Tell your doctor, especially if you are planning to have children.\n- **Don't change or stop any prescribed treatment without talking to your doctor.**\n\nRafiq does not give treatment advice; your doctor assesses your situation and suggests the right next step.",
                    ],
                ],
            ],
            [
                'key' => 'how-rafiq-works',
                'category' => 'about-rafiq',
                'kind' => ArticleKind::PlatformGuide,
                'sort' => 1,
                'sources' => [],
                'translations' => [
                    'ar' => [
                        'title' => 'كيف يعمل رفيق؟',
                        'slug' => 'kayfa-yaamal-rafiq',
                        'summary' => 'دليل سريع للمسارات الأربعة وما يحدث لبياناتك.',
                        'body' => "## أربعة مسارات\n\n- **أريد أن أتعلّم:** مقالات موثّقة دون أي أسئلة.\n- **لدي قلق صحي:** أسئلة قصيرة تبدأ بعلامات الخطر، ثم إرشاد للخطوة التالية.\n- **أستعد للإنجاب:** إرشاد حول متى قد يكون التقييم الطبي مناسباً.\n- **أريد التحضير لموعد طبي:** قائمة أسئلة تطبعها أو تحفظها على جهازك.\n\n## خصوصيتك\n\nلا نطلب اسمك أو أي بيانات تعريفية، ولا تحتاج إلى حساب. إجابات التقييم لا تُحفظ على خوادمنا.\n\n## حدود رفيق\n\nرفيق منصة توعية وإرشاد، لا يشخّص ولا يصف علاجاً ولا يغني عن الطبيب. في حالة الطوارئ اتصل بالطوارئ فوراً.",
                    ],
                    'en' => [
                        'title' => 'How does Rafiq work?',
                        'slug' => 'how-does-rafiq-work',
                        'summary' => 'A quick guide to the four journeys and what happens to your data.',
                        'body' => "## Four journeys\n\n- **I want to learn:** referenced articles with no questions.\n- **I have a health concern:** short questions that start with warning signs, then guidance on your next step.\n- **I'm preparing for conception:** guidance on when a medical evaluation may be appropriate.\n- **I want to prepare for a doctor's appointment:** a list of questions to print or save on your device.\n\n## Your privacy\n\nWe never ask for your name or identifying details, and you don't need an account. Assessment answers are not stored on our servers.\n\n## Rafiq's limits\n\nRafiq is an awareness and guidance platform. It does not diagnose, prescribe or replace a doctor. In an emergency, call emergency services straight away.",
                    ],
                ],
            ],
        ];
    }
}
