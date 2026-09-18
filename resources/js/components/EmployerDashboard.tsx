import { FormEvent, useCallback, useEffect, useState } from "react";
import { BriefcaseBusiness, Building2, FileText, Pencil, Plus, Save, Users } from "lucide-react";
import { ApiError, api, jsonBody } from "../api";
import type { Company, EmployerApplicant, EmployerOpportunity, Meta, User } from "../types";

type Props = { user: User | null; meta: Meta; onLogin: () => void };
type Tab = "opportunities" | "applicants" | "company";

const opportunityStatus = { draft: "پیش‌نویس", published: "منتشرشده", expired: "منقضی" } as const;
const applicantStatus = { opened: "بازشده", applied: "ارسال‌شده", reviewing: "در حال بررسی", interview: "دعوت به مصاحبه", offer: "پیشنهاد همکاری", rejected: "رد شده", withdrawn: "انصراف متقاضی" } as const;

function errorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    const validation = Object.values(error.errors).flat()[0];
    return validation || error.message;
  }
  return "انجام عملیات ممکن نشد. دوباره تلاش کنید.";
}

function nullable(value: FormDataEntryValue | null): string | null {
  const text = String(value || "").trim();
  return text || null;
}

export default function EmployerDashboard({ user, meta, onLogin }: Props) {
  const [company, setCompany] = useState<Company | null>(null);
  const [opportunities, setOpportunities] = useState<EmployerOpportunity[]>([]);
  const [applicants, setApplicants] = useState<EmployerApplicant[]>([]);
  const [selected, setSelected] = useState<EmployerOpportunity | null>(null);
  const [editing, setEditing] = useState<EmployerOpportunity | "new" | null>(null);
  const [tab, setTab] = useState<Tab>("opportunities");
  const [loading, setLoading] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");

  const loadOpportunities = useCallback(async () => {
    const response = await api<{ data: EmployerOpportunity[] }>("/employer/opportunities");
    setOpportunities(response.data);
  }, []);

  const load = useCallback(async () => {
    if (!user?.email_verified) return;
    setLoading(true); setError("");
    try {
      const response = await api<{ company: Company | null }>("/employer/company");
      setCompany(response.company);
      if (response.company?.status === "verified") await loadOpportunities();
    } catch (exception) { setError(errorMessage(exception)); }
    finally { setLoading(false); }
  }, [loadOpportunities, user]);

  useEffect(() => { void load(); }, [load]);

  async function saveCompany(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); setBusy(true); setError(""); setMessage("");
    const data = Object.fromEntries(new FormData(event.currentTarget));
    try {
      const response = await api<{ company: Company; message: string }>("/employer/company", {
        method: company ? "PUT" : "POST", ...jsonBody(data),
      });
      setCompany(response.company); setMessage(response.message); setTab(response.company.status === "verified" ? "opportunities" : "company");
      if (response.company.status === "verified") await loadOpportunities();
    } catch (exception) { setError(errorMessage(exception)); }
    finally { setBusy(false); }
  }

  async function saveOpportunity(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); setBusy(true); setError(""); setMessage("");
    const form = new FormData(event.currentTarget);
    const salaryFrom = nullable(form.get("monthly_salary_from"));
    const salaryTo = nullable(form.get("monthly_salary_to"));
    const payload = {
      category_id: Number(form.get("category_id")),
      title_fa: String(form.get("title_fa")), title_de: String(form.get("title_de")),
      description_fa: String(form.get("description_fa")), description_de: nullable(form.get("description_de")),
      city: String(form.get("city")), state: nullable(form.get("state")), training_type: String(form.get("training_type")),
      start_date: nullable(form.get("start_date")), application_deadline: nullable(form.get("application_deadline")),
      monthly_salary_from: salaryFrom ? Number(salaryFrom) : null, monthly_salary_to: salaryTo ? Number(salaryTo) : null,
      required_german_level: String(form.get("required_german_level")), education_requirement: nullable(form.get("education_requirement")),
      skills: String(form.get("skills") || "").split(",").map((item) => item.trim()).filter(Boolean),
      accepts_international: form.get("accepts_international") === "on", visa_support: String(form.get("visa_support")),
      contact_email: nullable(form.get("contact_email")), status: String(form.get("status")),
    };
    const current = editing === "new" ? null : editing;
    try {
      const response = await api<{ opportunity: EmployerOpportunity; message: string }>(
        current ? `/employer/opportunities/${current.slug}` : "/employer/opportunities",
        { method: current ? "PATCH" : "POST", ...jsonBody(payload) },
      );
      setMessage(response.message); setEditing(null); await loadOpportunities();
    } catch (exception) { setError(errorMessage(exception)); }
    finally { setBusy(false); }
  }

  async function showApplicants(opportunity: EmployerOpportunity) {
    setSelected(opportunity); setTab("applicants"); setLoading(true); setError("");
    try {
      const response = await api<{ data: EmployerApplicant[] }>(`/employer/opportunities/${opportunity.slug}/applicants`);
      setApplicants(response.data);
    } catch (exception) { setError(errorMessage(exception)); }
    finally { setLoading(false); }
  }

  async function updateApplicant(application: EmployerApplicant, status: string, interviewAt?: string | null) {
    setBusy(true); setError("");
    try {
      const response = await api<{ application: EmployerApplicant; message: string }>(`/employer/applications/${application.id}`, {
        method: "PATCH", ...jsonBody({ status, interview_at: interviewAt || null }),
      });
      setApplicants((items) => items.map((item) => item.id === application.id ? response.application : item));
      setMessage(response.message);
    } catch (exception) { setError(errorMessage(exception)); }
    finally { setBusy(false); }
  }

  if (!user) return <section className="page-surface container empty-state"><Building2/><h3>برای استفاده از پنل کارفرما وارد شوید</h3><p>پس از ورود می‌توانید شرکت را ثبت و فرصت آوسبیلدونگ منتشر کنید.</p><button className="primary-button" onClick={onLogin}>ورود / ثبت‌نام</button></section>;
  if (!user.email_verified) return <section className="page-surface container empty-state"><Building2/><h3>ابتدا ایمیل حساب را تأیید کنید</h3><p>ثبت شرکت و انتشار فرصت فقط برای حساب تأییدشده فعال است.</p><a className="primary-button" href="/account">تأیید ایمیل</a></section>;
  if (loading && !company) return <section className="page-surface container center-state">در حال دریافت پنل کارفرما…</section>;

  if (!company) return <section className="page-surface container employer-shell"><div className="employer-intro"><Building2/><div><h2>ثبت شرکت</h2><p>اطلاعات واقعی شرکت را وارد کنید. نام شرکت روی فرصت‌های منتشرشده نمایش داده می‌شود.</p></div></div>{error&&<p className="form-error">{error}</p>}<CompanyForm company={null} busy={busy} onSubmit={saveCompany}/></section>;

  if (company.status !== "verified") {
    const reviewText = company.status === "suspended"
      ? "دسترسی شرکت تعلیق شده است. برای بررسی با پشتیبانی تماس بگیرید."
      : company.status === "under_review"
        ? "مدارک شرکت در حال بررسی است. پس از تأیید، انتشار فرصت فعال می‌شود."
        : "شرکت ثبت شده و در انتظار بررسی مدیر است. تا پیش از تأیید امکان انتشار فرصت وجود ندارد.";

    return <section className="page-surface container employer-shell"><div className="employer-intro"><Building2/><div><h2>{company.name}</h2><p>{reviewText}</p></div></div>{error&&<p className="form-error">{error}</p>}{message&&<p className="form-success">{message}</p>}<CompanyForm company={company} busy={busy} onSubmit={saveCompany}/></section>;
  }

  return <section className="page-surface container employer-shell">
    <div className="employer-head"><div><span>پنل شرکت</span><h2>{company.name}</h2></div><button className="primary-button" onClick={() => { setEditing("new"); setTab("opportunities"); }}><Plus/> فرصت جدید</button></div>
    <div className="employer-tabs"><button className={tab==="opportunities"?"active":""} onClick={()=>setTab("opportunities")}><BriefcaseBusiness/> فرصت‌ها</button><button className={tab==="applicants"?"active":""} onClick={()=>setTab("applicants")}><Users/> متقاضیان</button><button className={tab==="company"?"active":""} onClick={()=>setTab("company")}><Building2/> اطلاعات شرکت</button></div>
    {error&&<p className="form-error">{error}</p>}{message&&<p className="form-success">{message}</p>}
    {tab==="company"&&<CompanyForm company={company} busy={busy} onSubmit={saveCompany}/>}
    {tab==="opportunities"&&<>{editing&&<OpportunityForm key={editing==="new"?"new":editing.id} opportunity={editing==="new"?null:editing} meta={meta} busy={busy} onSubmit={saveOpportunity} onCancel={()=>setEditing(null)}/>}<div className="employer-opportunity-list">{opportunities.map((opportunity)=><article key={opportunity.id}><div><span className={`employer-status ${opportunity.status}`}>{opportunityStatus[opportunity.status]}</span><h3 dir="ltr">{opportunity.title_de}</h3><p>{opportunity.city} · {opportunity.applicants_count.toLocaleString("fa-IR")} متقاضی</p></div><div className="employer-row-actions"><button onClick={()=>setEditing(opportunity)}><Pencil/> ویرایش</button><button onClick={()=>void showApplicants(opportunity)}><Users/> مشاهده متقاضیان</button></div></article>)}{!opportunities.length&&!editing&&<div className="empty-state"><BriefcaseBusiness/><h3>هنوز فرصتی ثبت نشده است</h3><button className="primary-button" onClick={()=>setEditing("new")}><Plus/> ایجاد اولین فرصت</button></div>}</div></>}
    {tab==="applicants"&&<ApplicantList opportunities={opportunities} selected={selected} applicants={applicants} busy={busy} onSelect={showApplicants} onUpdate={updateApplicant}/>}
  </section>;
}

function CompanyForm({ company, busy, onSubmit }: { company: Company | null; busy: boolean; onSubmit: (event: FormEvent<HTMLFormElement>) => void }) {
  return <form className="employer-form" onSubmit={onSubmit} key={company?.id || "new-company"}><div className="form-grid"><label>نام نمایشی شرکت<input name="name" required maxLength={190} defaultValue={company?.name}/></label><label>نام حقوقی<input name="legal_name" maxLength={190} defaultValue={company?.legal_name||""}/></label><label>ایمیل تماس<input name="contact_email" type="email" required defaultValue={company?.contact_email}/></label><label>تلفن<input name="phone" dir="ltr" defaultValue={company?.phone||""}/></label><label>شهر<input name="city" required defaultValue={company?.city}/></label><label>وب‌سایت<input name="website" type="url" dir="ltr" placeholder="https://" defaultValue={company?.website||""}/></label></div><label>نشانی<input name="address" defaultValue={company?.address||""}/></label><label>معرفی شرکت<textarea name="description" rows={5} maxLength={5000} defaultValue={company?.description||""}/></label><button className="primary-button" disabled={busy}><Save/> {busy?"در حال ذخیره…":company?"ذخیره تغییرات":"ثبت شرکت"}</button></form>;
}

function OpportunityForm({ opportunity, meta, busy, onSubmit, onCancel }: { opportunity: EmployerOpportunity | null; meta: Meta; busy: boolean; onSubmit: (event: FormEvent<HTMLFormElement>) => void; onCancel: () => void }) {
  return <form className="employer-form opportunity-editor" onSubmit={onSubmit}><div className="editor-title"><h3>{opportunity?"ویرایش فرصت":"ایجاد فرصت جدید"}</h3><button type="button" onClick={onCancel}>بستن</button></div><div className="form-grid"><label>عنوان فارسی<input name="title_fa" required defaultValue={opportunity?.title_fa}/></label><label>عنوان آلمانی<input name="title_de" required dir="ltr" defaultValue={opportunity?.title_de}/></label><label>دسته<select name="category_id" required defaultValue={opportunity?.category_id||""}><option value="">انتخاب کنید</option>{meta.categories.map((category)=><option key={category.id} value={category.id}>{category.name_fa}</option>)}</select></label><label>شهر<input name="city" required defaultValue={opportunity?.city}/></label><label>ایالت<input name="state" defaultValue={opportunity?.state||""}/></label><label>نوع دوره<select name="training_type" defaultValue={opportunity?.training_type||"dual"}><option value="dual">دوگانه (Dual)</option><option value="school">مدرسه‌ای</option></select></label><label>تاریخ شروع<input name="start_date" type="date" defaultValue={opportunity?.start_date||""}/></label><label>مهلت درخواست<input name="application_deadline" type="date" defaultValue={opportunity?.application_deadline||""}/></label><label>حقوق از (€)<input name="monthly_salary_from" type="number" min="0" defaultValue={opportunity?.monthly_salary_from||""}/></label><label>حقوق تا (€)<input name="monthly_salary_to" type="number" min="0" defaultValue={opportunity?.monthly_salary_to||""}/></label><label>سطح زبان<select name="required_german_level" defaultValue={opportunity?.required_german_level||"b1"}><option value="a2">A2</option><option value="b1">B1</option><option value="b2">B2</option><option value="c1">C1</option></select></label><label>وضعیت<select name="status" defaultValue={opportunity?.status||"draft"}><option value="draft">ذخیره پیش‌نویس</option><option value="published">انتشار عمومی</option><option value="expired">منقضی</option></select></label><label>حمایت ویزا<select name="visa_support" defaultValue={opportunity?.visa_support||"unknown"}><option value="unknown">نامشخص</option><option value="no">خیر</option><option value="possible">ممکن است</option><option value="yes">بله</option></select></label><label>ایمیل تماس<input name="contact_email" type="email" defaultValue={opportunity?.contact_email||""}/></label></div><label>شرایط تحصیلی<input name="education_requirement" defaultValue={opportunity?.education_requirement||""}/></label><label>مهارت‌ها (با ویرگول جدا کنید)<input name="skills" defaultValue={opportunity?.skills.join(", ")||""}/></label><label>توضیح فارسی<textarea name="description_fa" required rows={6} defaultValue={opportunity?.description_fa}/></label><label>توضیح آلمانی<textarea name="description_de" dir="ltr" rows={6} defaultValue={opportunity?.description_de||""}/></label><label className="check-line"><input name="accepts_international" type="checkbox" defaultChecked={opportunity?.accepts_international}/> پذیرش متقاضی بین‌المللی</label><button className="primary-button" disabled={busy}><Save/> {busy?"در حال ذخیره…":"ذخیره فرصت"}</button></form>;
}

function ApplicantList({ opportunities, selected, applicants, busy, onSelect, onUpdate }: { opportunities: EmployerOpportunity[]; selected: EmployerOpportunity | null; applicants: EmployerApplicant[]; busy: boolean; onSelect: (opportunity: EmployerOpportunity) => void; onUpdate: (application: EmployerApplicant, status: string, interviewAt?: string | null) => void }) {
  return <div className="applicant-panel"><label className="opportunity-picker">فرصت<select value={selected?.id||""} onChange={(event)=>{const item=opportunities.find((opportunity)=>opportunity.id===Number(event.target.value));if(item)void onSelect(item);}}><option value="">یک فرصت را انتخاب کنید</option>{opportunities.map((opportunity)=><option key={opportunity.id} value={opportunity.id}>{opportunity.title_de}</option>)}</select></label>{!selected?<div className="empty-state"><Users/><h3>یک فرصت را برای دیدن متقاضیان انتخاب کنید</h3></div>:!applicants.length?<div className="empty-state"><Users/><h3>هنوز متقاضی ثبت نشده است</h3></div>:<div className="applicant-list">{applicants.map((application)=><article key={application.id}><div className="candidate-title"><span className="app-icon blue"><Users/></span><div><h3>{application.candidate.name}</h3><a href={`mailto:${application.candidate.email}`}>{application.candidate.email}</a></div><span className="employer-status">{applicantStatus[application.status]}</span></div><div className="candidate-facts"><span>زبان: {application.candidate.german_level||"ثبت نشده"}</span><span>کشور: {application.candidate.country||"ثبت نشده"}</span><span>تحصیلات: {application.candidate.education_title||"ثبت نشده"}</span></div>{application.candidate.skills.length>0&&<div className="tag-list">{application.candidate.skills.map((skill)=><span key={skill}>{skill}</span>)}</div>}{application.candidate_message&&<p className="candidate-message">{application.candidate_message}</p>}<div className="applicant-actions">{application.resume?<a className="secondary-button" href={application.resume.download_url}><FileText/> دریافت {application.resume.original_name}</a>:<span>رزومه‌ای بارگذاری نشده</span>}<select disabled={busy||application.status==="withdrawn"} value={application.status} onChange={(event)=>void onUpdate(application,event.target.value,application.interview_at)}>{application.status==="applied"&&<option value="applied">ارسال‌شده</option>}{application.status==="withdrawn"&&<option value="withdrawn">انصراف متقاضی</option>}<option value="reviewing">در حال بررسی</option><option value="interview">دعوت به مصاحبه</option><option value="offer">پیشنهاد همکاری</option><option value="rejected">رد شده</option></select>{application.status==="interview"&&<input aria-label="زمان مصاحبه" type="datetime-local" value={application.interview_at?.slice(0,16)||""} onChange={(event)=>void onUpdate(application,"interview",event.target.value)}/>}</div></article>)}</div>}</div>;
}
