import { FormEvent, useEffect, useState } from "react";
import { AlertTriangle, Check, CheckCircle2, Lightbulb, ListChecks } from "lucide-react";
import { api, jsonBody } from "../api";
import type { Profile, User } from "../types";

type AssessmentValues = {
  age: string;
  education_level: string;
  german_level: string;
  work_experience_years: string;
};

type AssessmentResult = {
  score: number;
  level: { code: "weak" | "medium" | "good" | "very_good"; label: string };
  readiness: { code: "ready" | "conditional" | "preparation"; can_start: boolean; label: string; summary: string };
  breakdown: Record<"age" | "education" | "language" | "experience", { score: number; max: number }>;
  strengths: string[];
  missing: string[];
  improvements: string[];
  explanation: string;
  disclaimer: string;
};

type Props = {
  user: User | null;
  onLogin: () => void;
  onEditProfile: () => void;
  findMatches: (germanLevel: string) => void;
};

const emptyValues: AssessmentValues = {
  age: "",
  education_level: "",
  german_level: "",
  work_experience_years: "0",
};

const fa = new Intl.NumberFormat("fa-IR");

function ageFromBirthDate(birthDate: string | null): string {
  if (!birthDate) return "";

  const birth = new Date(`${birthDate}T00:00:00`);
  const today = new Date();
  let age = today.getFullYear() - birth.getFullYear();
  const monthDifference = today.getMonth() - birth.getMonth();

  if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birth.getDate())) age -= 1;

  return age >= 0 ? String(age) : "";
}

export default function EligibilityAssessment({ user, onLogin, onEditProfile, findMatches }: Props) {
  const [values, setValues] = useState<AssessmentValues>(emptyValues);
  const [result, setResult] = useState<AssessmentResult | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [profileLoaded, setProfileLoaded] = useState(false);

  useEffect(() => {
    setProfileLoaded(false);
    if (!user?.email_verified) return;

    api<{ profile: Profile }>("/profile")
      .then(({ profile }) => {
        setValues({
          age: ageFromBirthDate(profile.birth_date),
          education_level: profile.education_level || "",
          german_level: profile.german_level === "none" ? "" : profile.german_level,
          work_experience_years: String(profile.work_experience_years || 0),
        });
        setProfileLoaded(true);
      })
      .catch(() => undefined);
  }, [user]);

  const set = (key: keyof AssessmentValues, value: string) => {
    setValues((current) => ({ ...current, [key]: value }));
    setResult(null);
  };

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setBusy(true);
    setError("");

    try {
      const response = await api<{ assessment: AssessmentResult }>("/eligibility/assess", {
        method: "POST",
        ...jsonBody({
          age: Number(values.age),
          education_level: values.education_level,
          german_level: values.german_level,
          work_experience_years: Number(values.work_experience_years),
        }),
      });
      setResult(response.assessment);
    } catch (exception) {
      setError(exception instanceof Error ? exception.message : "محاسبه ارزیابی ممکن نشد. دوباره تلاش کنید.");
    } finally {
      setBusy(false);
    }
  };

  if (result) {
    const language = values.german_level.toLowerCase();

    return <section className="page-surface container eligibility-card" aria-live="polite">
      <div className="result-grid">
        <div className="assessment-summary">
          <span className={`readiness-badge ${result.readiness.code}`}>{result.readiness.label}</span>
          <h2>امتیاز آمادگی اولیه</h2>
          <p>{result.readiness.summary}</p>
          <div className="score-ring" style={{ background: `radial-gradient(circle,#fff 57%,transparent 59%),conic-gradient(var(--blue) 0 ${result.score}%,#e8eef5 ${result.score}%)` }}>{fa.format(result.score)}٪</div>
        </div>
        <div className={`result-visual ${result.readiness.can_start ? "positive" : "attention"}`}>
          {result.readiness.can_start ? <CheckCircle2 /> : <AlertTriangle />}
          <strong>سطح ارزیابی: {result.level.label}</strong>
          <span>{result.disclaimer}</span>
        </div>
      </div>

      <div className="breakdown-grid">
        <span><b>{fa.format(result.breakdown.age.score)}</b> از ۲۵<small>سن</small></span>
        <span><b>{fa.format(result.breakdown.education.score)}</b> از ۲۵<small>تحصیلات</small></span>
        <span><b>{fa.format(result.breakdown.language.score)}</b> از ۳۰<small>زبان</small></span>
        <span><b>{fa.format(result.breakdown.experience.score)}</b> از ۲۰<small>سابقه</small></span>
      </div>

      <div className="assessment-feedback-grid">
        <article className="assessment-feedback strengths">
          <h3><CheckCircle2 /> نقاط قوت</h3>
          {result.strengths.length ? <ul>{result.strengths.map((item) => <li key={item}>{item}</li>)}</ul> : <p>هنوز نقطه قوت مشخصی از پاسخ‌ها استخراج نشده است.</p>}
        </article>
        <article className="assessment-feedback missing">
          <h3><AlertTriangle /> موارد نیازمند توجه</h3>
          {result.missing.length ? <ul>{result.missing.map((item) => <li key={item}>{item}</li>)}</ul> : <p>کمبود اصلی در این ارزیابی اولیه دیده نشد.</p>}
        </article>
      </div>

      <div className="tip-box">
        <Lightbulb />
        <div><b>اقدام‌های پیشنهادی</b>{result.improvements.length ? <ul>{result.improvements.map((item) => <li key={item}>{item}</li>)}</ul> : <span>پروفایل را به‌روز نگه دار و شرایط هر آگهی را جداگانه بررسی کن.</span>}</div>
      </div>
      <p className="assessment-explanation">{result.explanation}</p>
      {language && language !== "none" && <button className="primary-button wide-action" onClick={() => findMatches(language)}>مشاهده فرصت‌های متناسب با زبان</button>}
      <button className="secondary-button wide-action" onClick={() => setResult(null)}>ویرایش پاسخ‌ها</button>
    </section>;
  }

  return <section className="page-surface container eligibility-card">
    <form className="assessment-form" onSubmit={submit}>
      <div className="assessment-form-heading">
        <span><ListChecks /></span>
        <div><h2>اطلاعات اولیه</h2><p>سن دقیق، آخرین مدرک، زبان و سابقه مرتبط را وارد کن.</p></div>
      </div>

      {profileLoaded && <div className="profile-prefill"><Check /> اطلاعات موجود از پروفایل وارد شد. قبل از محاسبه آن‌ها را بررسی کن.</div>}
      {!user && <div className="profile-prefill guest">می‌توانی بدون حساب ارزیابی را انجام بدهی. برای تکمیل خودکار در دفعات بعد، <button type="button" onClick={onLogin}>وارد شو</button>.</div>}
      {user && user.email_verified && !profileLoaded && <div className="profile-prefill guest">اطلاعات پروفایل کامل نیست؟ <button type="button" onClick={onEditProfile}>پروفایل را تکمیل کن</button> یا فرم زیر را دستی وارد کن.</div>}

      <label>سن
        <input type="number" min="14" max="65" required value={values.age} onChange={(event) => set("age", event.target.value)} placeholder="برای مثال ۲۶" />
      </label>
      <label>آخرین مدرک تحصیلی
        <select required value={values.education_level} onChange={(event) => set("education_level", event.target.value)}>
          <option value="">انتخاب کنید</option>
          <option value="below_diploma">کمتر از دیپلم</option>
          <option value="diploma">دیپلم</option>
          <option value="associate">کاردانی</option>
          <option value="bachelor">کارشناسی</option>
          <option value="master">کارشناسی ارشد</option>
          <option value="doctorate">دکتری</option>
        </select>
      </label>
      <label>سطح زبان آلمانی
        <select required value={values.german_level} onChange={(event) => set("german_level", event.target.value)}>
          <option value="">انتخاب کنید</option>
          <option value="none">هنوز شروع نکرده‌ام</option>
          <option value="a1">A1</option><option value="a2">A2</option><option value="b1">B1</option>
          <option value="b2">B2</option><option value="c1">C1</option><option value="c2">C2</option>
        </select>
      </label>
      <label>سابقه مرتبط (سال)
        <input type="number" min="0" max="50" required value={values.work_experience_years} onChange={(event) => set("work_experience_years", event.target.value)} />
      </label>
      {error && <div className="form-error" role="alert">{error}</div>}
      <button className="primary-button wide-action" disabled={busy}>{busy ? "در حال محاسبه…" : "بررسی امکان اقدام"}</button>
      <small className="assessment-disclaimer">این ابزار تصمیم رسمی یا مشاوره مهاجرتی نیست؛ نتیجه برای اولویت‌بندی قدم‌های بعدی استفاده می‌شود.</small>
    </form>
  </section>;
}
