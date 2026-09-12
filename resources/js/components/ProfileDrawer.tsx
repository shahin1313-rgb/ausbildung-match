import { ChangeEvent, FormEvent, useEffect, useState } from "react";
import { FileText, LoaderCircle, Trash2, Upload, X } from "lucide-react";
import { api, ApiError, jsonBody } from "../api";
import type { Meta, Profile, Resume } from "../types";

type Props = {
  meta: Meta;
  onClose: () => void;
  onSaved: () => void;
};

const emptyProfile: Profile = {
  phone: null,
  country: null,
  birth_date: null,
  german_level: "none",
  education_level: null,
  education_title: null,
  skills: [],
  preferred_category_ids: [],
  preferred_cities: [],
  work_experience_years: 0,
  relocation_ready: true,
  available_from: null,
};

function dateValue(value: string | null): string {
  return value ? value.slice(0, 10) : "";
}

export default function ProfileDrawer({ meta, onClose, onSaved }: Props) {
  const [profile, setProfile] = useState<Profile>(emptyProfile);
  const [resumes, setResumes] = useState<Resume[]>([]);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");

  useEffect(() => {
    Promise.all([
      api<{ profile: Profile }>("/profile"),
      api<{ resumes: Resume[] }>("/resumes"),
    ])
      .then(([profileResponse, resumeResponse]) => {
        setProfile({ ...emptyProfile, ...profileResponse.profile });
        setResumes(resumeResponse.resumes);
      })
      .catch(() => setError("دریافت اطلاعات پروفایل ممکن نشد."))
      .finally(() => setLoading(false));
  }, []);

  function setField<K extends keyof Profile>(field: K, value: Profile[K]) {
    setProfile((current) => ({ ...current, [field]: value }));
  }

  async function save(event: FormEvent) {
    event.preventDefault();
    setBusy(true);
    setMessage("");
    setError("");

    try {
      const payload = {
        ...profile,
        phone: profile.phone || null,
        country: profile.country || null,
        birth_date: profile.birth_date || null,
        education_level: profile.education_level || null,
        education_title: profile.education_title || null,
        available_from: profile.available_from || null,
        skills: profile.skills || [],
        preferred_category_ids: profile.preferred_category_ids || [],
        preferred_cities: profile.preferred_cities || [],
      };
      const response = await api<{ message: string; profile: Profile }>("/profile", {
        method: "PUT",
        ...jsonBody(payload),
      });
      setProfile(response.profile);
      setMessage(response.message);
      onSaved();
    } catch (exception) {
      const first = exception instanceof ApiError ? Object.values(exception.errors)[0]?.[0] : null;
      setError(first || (exception instanceof Error ? exception.message : "ذخیره انجام نشد."));
    } finally {
      setBusy(false);
    }
  }

  async function uploadResume(event: ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0];
    if (!file) return;
    setBusy(true);
    setMessage("");
    setError("");
    const body = new FormData();
    body.append("resume", file);

    try {
      const response = await api<{ message: string; resume: Resume }>("/resumes", { method: "POST", body });
      setResumes((current) => [response.resume, ...current.map((resume) => ({ ...resume, is_primary: false }))]);
      setMessage(response.message);
    } catch (exception) {
      const first = exception instanceof ApiError ? Object.values(exception.errors)[0]?.[0] : null;
      setError(first || "بارگذاری رزومه انجام نشد.");
    } finally {
      event.target.value = "";
      setBusy(false);
    }
  }

  async function removeResume(id: number) {
    if (!window.confirm("این رزومه حذف شود؟")) return;
    try {
      await api(`/resumes/${id}`, { method: "DELETE" });
      setResumes((current) => current.filter((resume) => resume.id !== id));
      setMessage("رزومه حذف شد.");
    } catch {
      setError("حذف رزومه ممکن نشد.");
    }
  }

  return (
    <div className="drawer-backdrop" onMouseDown={onClose}>
      <aside className="profile-drawer" onMouseDown={(event) => event.stopPropagation()} aria-label="پروفایل کاربری">
        <header>
          <div><span>پروفایل تطبیق</span><h2>اطلاعات شما</h2></div>
          <button type="button" className="icon-button" onClick={onClose} aria-label="بستن"><X /></button>
        </header>

        {loading ? (
          <div className="center-state"><LoaderCircle className="spin" /> در حال دریافت اطلاعات…</div>
        ) : (
          <form onSubmit={save} className="profile-form">
            <div className="drawer-intro">هرچه اطلاعات دقیق‌تر باشد، امتیاز تطابق فرصت‌ها کاربردی‌تر خواهد بود.</div>

            <div className="form-grid">
              <label>سطح زبان آلمانی
                <select value={profile.german_level} onChange={(event) => setField("german_level", event.target.value)} required>
                  <option value="none">هنوز شروع نکرده‌ام</option><option value="a1">A1</option><option value="a2">A2</option>
                  <option value="b1">B1</option><option value="b2">B2</option><option value="c1">C1</option><option value="c2">C2</option>
                </select>
              </label>
              <label>آخرین مدرک
                <select value={profile.education_level || ""} onChange={(event) => setField("education_level", event.target.value || null)}>
                  <option value="">انتخاب کنید</option><option value="below_diploma">زیر دیپلم</option><option value="diploma">دیپلم</option>
                  <option value="associate">کاردانی</option><option value="bachelor">کارشناسی</option><option value="master">کارشناسی ارشد</option><option value="doctorate">دکتری</option>
                </select>
              </label>
              <label>رشته تحصیلی<input value={profile.education_title || ""} onChange={(event) => setField("education_title", event.target.value)} /></label>
              <label>سابقه کار (سال)<input type="number" min="0" max="50" value={profile.work_experience_years} onChange={(event) => setField("work_experience_years", Number(event.target.value))} required /></label>
              <label>کشور محل اقامت<input value={profile.country || ""} onChange={(event) => setField("country", event.target.value)} /></label>
              <label>شماره تماس<input dir="ltr" value={profile.phone || ""} onChange={(event) => setField("phone", event.target.value)} /></label>
              <label>تاریخ تولد<input type="date" value={dateValue(profile.birth_date)} onChange={(event) => setField("birth_date", event.target.value || null)} /></label>
              <label>آماده شروع از<input type="date" value={dateValue(profile.available_from)} onChange={(event) => setField("available_from", event.target.value || null)} /></label>
            </div>

            <label>مهارت‌ها (با ویرگول جدا کنید)
              <input value={(profile.skills || []).join(", ")} onChange={(event) => setField("skills", event.target.value.split(",").map((item) => item.trim()).filter(Boolean))} placeholder="مثلاً PHP, Teamarbeit, Excel" />
            </label>

            <fieldset className="choice-fieldset">
              <legend>حوزه‌های موردعلاقه</legend>
              <div className="choice-grid">
                {meta.categories.map((category) => {
                  const checked = (profile.preferred_category_ids || []).includes(category.id);
                  return <label key={category.id} className={checked ? "selected" : ""}><input type="checkbox" checked={checked} onChange={() => setField("preferred_category_ids", checked ? (profile.preferred_category_ids || []).filter((id) => id !== category.id) : [...(profile.preferred_category_ids || []), category.id])} />{category.name_fa}</label>;
                })}
              </div>
            </fieldset>

            <label>شهرهای ترجیحی (با ویرگول جدا کنید)
              <input value={(profile.preferred_cities || []).join(", ")} onChange={(event) => setField("preferred_cities", event.target.value.split(",").map((item) => item.trim()).filter(Boolean))} placeholder="Berlin, Hamburg" />
            </label>
            <label className="switch-line"><input type="checkbox" checked={profile.relocation_ready} onChange={(event) => setField("relocation_ready", event.target.checked)} /> برای جابه‌جایی به شهر دیگری آماده‌ام</label>

            <section className="resume-box">
              <div><FileText /><div><strong>رزومه فعلی</strong><span>فایل PDF یا Word، حداکثر ۵ مگابایت</span></div></div>
              <label className="upload-button"><Upload size={17} /> انتخاب فایل<input type="file" accept=".pdf,.doc,.docx" onChange={uploadResume} disabled={busy} /></label>
              {resumes.map((resume) => (
                <div className="resume-row" key={resume.id}>
                  <span>{resume.original_name} {resume.is_primary && <small>اصلی</small>}</span>
                  <button type="button" onClick={() => removeResume(resume.id)} aria-label="حذف رزومه"><Trash2 size={17} /></button>
                </div>
              ))}
            </section>

            {error && <div className="form-error">{error}</div>}
            {message && <div className="form-success">{message}</div>}
            <button type="submit" className="primary-button wide" disabled={busy}>{busy ? "در حال ذخیره…" : "ذخیره و محاسبه تطابق"}</button>
          </form>
        )}
      </aside>
    </div>
  );
}
