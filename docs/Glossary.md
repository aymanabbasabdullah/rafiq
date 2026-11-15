# مسرد مصطلحات المشروع (Project Glossary)

> ملف إضافي — سبب الإضافة: توحيد المصطلحات بين الفرق (منتج/تطوير/طبي) ومنع التناقض. **ليس** المسرد الطبي الموجّه للمستخدمين (ذلك محتوى M08).

| المصطلح | التعريف في رفيق |
|---|---|
| **Journey (J1–J4)** | المسارات: J1 Learn، J2 Concern، J3 Conception، J4 Doctor Visit Prep. |
| **Age Gate** | سؤال واحد لتأكيد أن العمر ≥ 18 (لا تاريخ ميلاد). |
| **Safety Screening** | أسئلة علامات الخطر (Red Flags) الإلزامية قبل التقييم. |
| **Assessment** | أسئلة خاصة بالمسار، قابلة للتخطي، لا نص حر. |
| **Clinical Rules Engine** | المحرك الحتمي الذي يقيّم الإجابات مقابل Ruleset. |
| **Rule / Rule Version** | قاعدة (CR-xxx) وإصداراتها؛ لكل إصدار حالة Draft / Pending Medical Review / Approved / Retired. |
| **Ruleset Release** | حزمة مُصدَّرة (مثل RS-2026.1) من إصدارات القواعد والأسئلة والنصوص؛ واحدة فقط منشورة. |
| **Priority P0–P4** | Emergency / Urgent / Clinical Evaluation / Preventive / Informational. **GATE** = غير مؤهل (CR-001). |
| **Guidance** | النصوص المعتمدة التي تشرح الأولوية والخطوة التالية — ليست تشخيصاً. |
| **Report** | عرض الإرشاد القابل للطباعة/الحفظ محلياً؛ لا يُخزّن. |
| **Demo Mode** | تشغيل Ruleset غير معتمد خارج Production فقط مع شريط تحذير. |
| **Stateless evaluation** | الإجابات تُعالج في طلب واحد ولا تُخزن (ADR-003). |
| **Rafiq AI Companion** | المساعد المبني على Gemini بأوضاع: platform_guide، explain_article، explain_term، general_education. |
| **Grounding / Chunk** | تقييد الـ AI بمقاطع من المحتوى المنشور والمعتمد. |
| **Pre-filter / Red-flag interceptor / Post-filter** | طبقات حتمية قبل/بعد استدعاء Gemini. |
| **CorePrompt** | تعليمات الأمان الأساسية الثابتة في الكود. |
| **Four-eyes principle** | لا يعتمد الشخص ما كتبه أو عدّله. |
| **Medical Reviewer** | أدمن بدور طبي مُتحقق من أهليته. |
| **Kill switch** | إيقاف فوري للتقييم (`RAFIQ_ASSESSMENT_ENABLED`) أو للـ AI (`RAFIQ_AI_ENABLED`). |
| **SAFE-009** | رسالة السلامة العامة عند تعطل الخدمة. |
| **[مصدر] / [استنتاج] / [مراجعة]** | تصنيف دعم المعلومة: مدعومة بمرجع / استنتاج تصميمي / تحتاج مراجعة طبية. |
| **SRC-xxx** | معرّف مصدر في Medical-Sources-Register. |
| **ADR / OQ / AS / RQ** | قرار معماري / سؤال مفتوح / افتراض / سؤال مراجعة طبية. |
