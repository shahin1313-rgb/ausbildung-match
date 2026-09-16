import { FormEvent, useEffect, useState } from "react";
import { Download, LoaderCircle, Save, X } from "lucide-react";
import { api, ApiError, jsonBody } from "../api";
import type { GermanCv, Opportunity, User } from "../types";

type Props = { user: User; opportunity: Opportunity | null; onClose: () => void };

function template(user: User, opportunity: Opportunity | null): string {
  const role = opportunity?.title_de || "[Ausbildungsberuf]";
  const company = opportunity?.employer_name || "[Unternehmen]";

  return `Betreff: Bewerbung um einen Ausbildungsplatz als ${role}\n\nSehr geehrte Damen und Herren,\n\nmit großem Interesse bewerbe ich mich bei ${company} um einen Ausbildungsplatz als ${role}. Die Verbindung aus praktischem Lernen und fachlicher Entwicklung motiviert mich besonders.\n\nGerne überzeuge ich Sie in einem persönlichen Gespräch von meiner Motivation und Lernbereitschaft.\n\nMit freundlichen Grüßen\n${user.name}`;
}

export default function CoverLetterBuilder({ user, opportunity, onClose }: Props) {
  const [letter, setLetter] = useState("");
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");

  useEffect(() => {
    api<{ cv: GermanCv }>("/german-cv")
      .then(({ cv }) => setLetter(cv.cover_letter?.trim() || template(user, opportunity)))
      .catch(() => setError("دریافت انگیزه‌نامه ممکن نشد."))
      .finally(() => setLoading(false));
  }, [opportunity, user]);

  async function save(event: FormEvent) {
    event.preventDefault();
    setBusy(true); setError(""); setMessage("");
    try {
      const response = await api<{ message: string }>("/german-cv", { method: "PUT", ...jsonBody({ cover_letter: letter }) });
      setMessage(response.message.replace("رزومه آلمانی", "انگیزه‌نامه"));
    } catch (exception) {
      const first = exception instanceof ApiError ? Object.values(exception.errors)[0]?.[0] : null;
      setError(first || "ذخیره انگیزه‌نامه انجام نشد.");
    } finally { setBusy(false); }
  }

  return <div className="modal-backdrop cv-backdrop" onMouseDown={onClose}>
    <section className="cv-dialog cover-letter-dialog" onMouseDown={(event) => event.stopPropagation()}>
      <header className="cv-dialog-header no-print"><div><span>Anschreiben</span><h2>انگیزه‌نامه آلمانی</h2></div><button type="button" className="icon-button" onClick={onClose}><X /></button></header>
      {loading ? <div className="center-state"><LoaderCircle className="spin" /> در حال بارگذاری…</div> : <form onSubmit={save} className="cover-letter-workspace">
        <div className="no-print"><p className="drawer-intro">متن اولیه از نام شما و فرصت انتخاب‌شده ساخته شده است. پیش از ارسال، جزئیات شخصی و دلیل علاقه‌تان را اضافه کنید.</p><textarea dir="ltr" value={letter} onChange={(event) => setLetter(event.target.value)} rows={22} required />{error && <div className="form-error">{error}</div>}{message && <div className="form-success">{message}</div>}<div className="cv-form-actions"><button className="primary-button" disabled={busy}><Save size={17}/>{busy ? "در حال ذخیره…" : "ذخیره انگیزه‌نامه"}</button><button className="secondary-button" type="button" onClick={() => window.print()}><Download size={17}/> چاپ / PDF</button></div></div>
        <article className="cover-letter-preview" dir="ltr">{letter.split("\n").map((line, index) => <p key={index}>{line || <br/>}</p>)}</article>
      </form>}
    </section>
  </div>;
}
