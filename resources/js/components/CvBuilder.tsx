import { FormEvent, useEffect, useState } from "react";
import { Download, LoaderCircle, Save, X } from "lucide-react";
import { api, ApiError, jsonBody } from "../api";
import type { GermanCv, User } from "../types";

type Props = {
  user: User;
  onClose: () => void;
};

const emptyCv: GermanCv = {
  headline: "",
  summary: "",
  contact: {},
  experiences: [],
  education: [],
  skills: [],
  languages: [],
  certificates: [],
};

export default function CvBuilder({ user, onClose }: Props) {
  const [cv, setCv] = useState<GermanCv>(emptyCv);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");

  useEffect(() => {
    api<{ cv: GermanCv }>("/german-cv")
      .then((response) => setCv({ ...emptyCv, ...response.cv, contact: response.cv.contact || {} }))
      .catch(() => setError("دریافت رزومه ممکن نشد."))
      .finally(() => setLoading(false));
  }, []);

  function setField<K extends keyof GermanCv>(field: K, value: GermanCv[K]) {
    setCv((current) => ({ ...current, [field]: value }));
  }

  async function save(event: FormEvent) {
    event.preventDefault();
    setBusy(true);
    setError("");
    setMessage("");

    const payload = {
      ...cv,
      experiences: (cv.experiences || []).filter((item) => item.title.trim()),
      education: (cv.education || []).filter((item) => item.title.trim()),
      languages: (cv.languages || []).filter((item) => item.name.trim()),
      skills: cv.skills || [],
      certificates: cv.certificates || [],
    };

    try {
      const response = await api<{ message: string; cv: GermanCv }>("/german-cv", { method: "PUT", ...jsonBody(payload) });
      setCv(response.cv);
      setMessage(response.message);
    } catch (exception) {
      const first = exception instanceof ApiError ? Object.values(exception.errors)[0]?.[0] : null;
      setError(first || "ذخیره رزومه انجام نشد.");
    } finally {
      setBusy(false);
    }
  }

  const experience = cv.experiences?.[0] || { title: "", company: "", period: "", description: "" };
  const education = cv.education?.[0] || { title: "", school: "", period: "" };

  return (
    <div className="modal-backdrop cv-backdrop" onMouseDown={onClose}>
      <section className="cv-dialog" onMouseDown={(event) => event.stopPropagation()}>
        <header className="cv-dialog-header no-print">
          <div><span>Lebenslauf</span><h2>رزومه آلمانی</h2></div>
          <button type="button" className="icon-button" onClick={onClose}><X /></button>
        </header>

        {loading ? <div className="center-state"><LoaderCircle className="spin" /> در حال بارگذاری…</div> : (
          <form onSubmit={save} className="cv-workspace">
            <div className="cv-form no-print">
              <label>عنوان حرفه‌ای<input dir="ltr" value={cv.headline || ""} onChange={(event) => setField("headline", event.target.value)} placeholder="Ausbildungssuchende/r Fachinformatiker/in" /></label>
              <label>درباره من<textarea dir="ltr" rows={4} value={cv.summary || ""} onChange={(event) => setField("summary", event.target.value)} placeholder="Motivierte Bewerberin mit ..." /></label>
              <div className="form-grid">
                <label>تلفن<input dir="ltr" value={cv.contact?.phone || ""} onChange={(event) => setField("contact", { ...cv.contact, phone: event.target.value })} /></label>
                <label>شهر<input dir="ltr" value={cv.contact?.city || ""} onChange={(event) => setField("contact", { ...cv.contact, city: event.target.value })} /></label>
              </div>
              <h3>تجربه کاری اصلی</h3>
              <div className="form-grid">
                <label>عنوان<input dir="ltr" value={experience.title} onChange={(event) => setField("experiences", [{ ...experience, title: event.target.value }])} /></label>
                <label>شرکت<input dir="ltr" value={experience.company || ""} onChange={(event) => setField("experiences", [{ ...experience, company: event.target.value }])} /></label>
                <label>بازه زمانی<input dir="ltr" value={experience.period || ""} onChange={(event) => setField("experiences", [{ ...experience, period: event.target.value }])} placeholder="2023 – 2025" /></label>
              </div>
              <label>شرح تجربه<textarea dir="ltr" rows={3} value={experience.description || ""} onChange={(event) => setField("experiences", [{ ...experience, description: event.target.value }])} /></label>
              <h3>تحصیلات</h3>
              <div className="form-grid">
                <label>مدرک/رشته<input dir="ltr" value={education.title} onChange={(event) => setField("education", [{ ...education, title: event.target.value }])} /></label>
                <label>مرکز آموزشی<input dir="ltr" value={education.school || ""} onChange={(event) => setField("education", [{ ...education, school: event.target.value }])} /></label>
                <label>بازه زمانی<input dir="ltr" value={education.period || ""} onChange={(event) => setField("education", [{ ...education, period: event.target.value }])} /></label>
              </div>
              <label>مهارت‌ها<input dir="ltr" value={(cv.skills || []).join(", ")} onChange={(event) => setField("skills", event.target.value.split(",").map((item) => item.trim()).filter(Boolean))} /></label>
              <label>زبان‌ها (نمونه: Deutsch B1, Englisch B2)<input dir="ltr" value={(cv.languages || []).map((item) => `${item.name} ${item.level || ""}`.trim()).join(", ")} onChange={(event) => setField("languages", event.target.value.split(",").map((item) => item.trim()).filter(Boolean).map((item) => { const pieces = item.split(/\s+/); const level = pieces.pop() || ""; return { name: pieces.join(" ") || level, level: pieces.length ? level : "" }; }))} /></label>
              {error && <div className="form-error">{error}</div>}
              {message && <div className="form-success">{message}</div>}
              <div className="cv-form-actions">
                <button className="primary-button" type="submit" disabled={busy}><Save size={17} /> {busy ? "در حال ذخیره…" : "ذخیره رزومه"}</button>
                <button className="secondary-button" type="button" onClick={() => window.print()}><Download size={17} /> چاپ / PDF</button>
              </div>
            </div>

            <article className="cv-preview" dir="ltr">
              <div className="cv-preview-top">
                <div className="cv-monogram">{user.name.trim().slice(0, 1).toUpperCase()}</div>
                <div><h1>{user.name}</h1><p>{cv.headline || "Ausbildungskandidat/in"}</p></div>
              </div>
              <div className="cv-contact">
                <span>{cv.contact?.email || user.email}</span><span>{cv.contact?.phone || "+49 …"}</span><span>{cv.contact?.city || "Deutschland"}</span>
              </div>
              <section><h2>PROFIL</h2><p>{cv.summary || "Kurzes berufliches Profil und Motivation für die gewünschte Ausbildung."}</p></section>
              {experience.title && <section><h2>BERUFSERFAHRUNG</h2><div className="cv-item"><h3>{experience.title}</h3><b>{experience.company} · {experience.period}</b><p>{experience.description}</p></div></section>}
              {education.title && <section><h2>AUSBILDUNG</h2><div className="cv-item"><h3>{education.title}</h3><b>{education.school} · {education.period}</b></div></section>}
              <div className="cv-two-columns">
                <section><h2>KENNTNISSE</h2><div className="cv-tags">{(cv.skills || []).map((skill) => <span key={skill}>{skill}</span>)}</div></section>
                <section><h2>SPRACHEN</h2>{(cv.languages || []).map((language) => <p key={`${language.name}-${language.level}`}>{language.name} — {language.level}</p>)}</section>
              </div>
            </article>
          </form>
        )}
      </section>
    </div>
  );
}
