import { useCallback, useEffect, useMemo, useState } from "react";
import { Building2, CalendarDays, Clock3, FileText, LogIn, Trash2, Undo2 } from "lucide-react";
import { api, jsonBody } from "../api";
import type { Application, ApplicationCollection, ApplicationStatus, Opportunity, User } from "../types";

type Props = { user: User | null; onLogin: () => void; openDetails: (opportunity: Opportunity) => void };
const labels: Record<ApplicationStatus, string> = { opened: "لینک باز شده", applied: "ارسال‌شده", reviewing: "در حال بررسی", interview: "دعوت به مصاحبه", offer: "پیشنهاد دریافت شد", rejected: "رد شده", withdrawn: "انصراف" };

export default function ApplicationsPage({ user, onLogin, openDetails }: Props) {
  const [items, setItems] = useState<Application[]>([]);
  const [summary, setSummary] = useState({ total: 0, active: 0, interviews: 0 });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const load = useCallback(async () => {
    if (!user) return;
    setLoading(true); setError("");
    try { const response = await api<ApplicationCollection>("/applications"); setItems(response.data); setSummary(response.summary); }
    catch { setError("دریافت درخواست‌ها ممکن نشد."); }
    finally { setLoading(false); }
  }, [user]);
  useEffect(() => { void load(); }, [load]);

  async function patch(item: Application, payload: Partial<Pick<Application, "status" | "interview_at" | "notes">>) {
    const response = await api<{ application: Application }>(`/applications/${item.id}`, { method: "PATCH", ...jsonBody(payload) });
    setItems((current) => current.map((entry) => entry.id === item.id ? response.application : entry));
    void load();
  }
  async function remove(item: Application) {
    if (!window.confirm("این مورد از رهگیر درخواست‌ها حذف شود؟")) return;
    await api(`/applications/${item.id}`, { method: "DELETE" });
    setItems((current) => current.filter((entry) => entry.id !== item.id));
    void load();
  }
  const nextInterview = useMemo(() => items.filter((item) => item.interview_at && new Date(item.interview_at) > new Date()).sort((a, b) => String(a.interview_at).localeCompare(String(b.interview_at)))[0], [items]);

  if (!user) return <section className="page-surface container empty-state"><LogIn/><h3>برای پیگیری درخواست‌ها وارد شوید</h3><p>پس از ورود، فرصت‌هایی که لینک آن‌ها را باز می‌کنید اینجا ثبت می‌شوند؛ وضعیت ارسال را خودتان تأیید می‌کنید.</p><button className="primary-button" onClick={onLogin}>ورود / ثبت‌نام</button></section>;
  return <section className="page-surface container"><div className="application-stats"><span><span className="app-icon blue"><FileText/></span><b>{summary.total.toLocaleString("fa-IR")}</b> همه</span><span><span className="app-icon amber"><Clock3/></span><b>{summary.active.toLocaleString("fa-IR")}</b> فعال</span><span><span className="app-icon purple"><CalendarDays/></span><b>{summary.interviews.toLocaleString("fa-IR")}</b> مصاحبه</span></div>{loading ? <div className="center-state">در حال دریافت درخواست‌ها…</div> : error ? <div className="empty-state"><p>{error}</p><button onClick={load}>تلاش دوباره</button></div> : items.length === 0 ? <div className="empty-state"><FileText/><h3>هنوز موردی ثبت نشده است</h3><p>با بازکردن لینک درخواست یک فرصت، آن فرصت به‌صورت خودکار با وضعیت «لینک باز شده» به این صفحه اضافه می‌شود.</p></div> : <div className="application-list">{items.map((item) => <article key={item.id}><button className="application-main" onClick={() => openDetails(item.opportunity)}><span className="app-icon blue"><Building2/></span><div><b dir="ltr">{item.opportunity.title_de}</b><span>{item.opportunity.employer_name} · {item.opportunity.city}</span></div></button>{item.managed_by_employer?<><span className="status">{labels[item.status]}</span>{item.status!=="withdrawn"&&<button className="secondary-button" onClick={()=>void patch(item,{status:"withdrawn"})}><Undo2/> پس‌گرفتن درخواست</button>}</>:<><select aria-label="وضعیت درخواست" value={item.status} onChange={(event) => void patch(item, { status: event.target.value as ApplicationStatus })}>{Object.entries(labels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select>{item.status==="interview"&&<label className="interview-date">زمان مصاحبه<input type="datetime-local" value={item.interview_at?.slice(0,16)||""} onChange={(event)=>void patch(item,{interview_at:event.target.value||null})}/></label>}<button className="application-delete" onClick={() => void remove(item)} aria-label="حذف از رهگیر"><Trash2/></button></>}</article>)}</div>}{nextInterview && <div className="reminder"><CalendarDays/><div><b>مصاحبه بعدی</b><span>{nextInterview.opportunity.title_de} — {new Date(nextInterview.interview_at!).toLocaleString("fa-IR")}</span></div><button onClick={() => openDetails(nextInterview.opportunity)}>مشاهده فرصت</button></div>}</section>;
}
