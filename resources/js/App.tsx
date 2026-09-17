import { FormEvent, ReactNode, useCallback, useEffect, useState } from "react";
import { ArrowLeft, BadgeEuro, Bookmark, BriefcaseBusiness, Building2, CalendarDays, Check, ChevronLeft, FileCheck2, FileText, GraduationCap, House, LayoutGrid, ListChecks, LogOut, MapPin, Menu, Mic, Search, Sparkles, UserRound, Wrench, X } from "lucide-react";
import { api } from "./api";
import AuthDialog from "./components/AuthDialog";
import CvBuilder from "./components/CvBuilder";
import CoverLetterBuilder from "./components/CoverLetterBuilder";
import InterviewPractice from "./components/InterviewPractice";
import ApplicationsPage from "./components/ApplicationsPage";
import EmployerDashboard from "./components/EmployerDashboard";
import EligibilityAssessment from "./components/EligibilityAssessment";
import OpportunityCard from "./components/OpportunityCard";
import ReportOpportunityDialog from "./components/ReportOpportunityDialog";
import ProfileDrawer from "./components/ProfileDrawer";
import type { GermanCv, Meta, Opportunity, OpportunityCollection, Profile, User } from "./types";

type View = "home" | "search" | "details" | "guide" | "eligibility" | "tools" | "interview" | "applications" | "employer";
type Filters = { q: string; category: string; city: string; german_level: string; international: boolean; sort: string };
const emptyMeta: Meta = { categories: [], cities: [], german_levels: ["A2", "B1", "B2", "C1"] };
const initialFilters: Filters = { q: "", category: "", city: "", german_level: "", international: false, sort: "latest" };
const fa = new Intl.NumberFormat("fa-IR");

function opportunitySlugFromPath(): string | null {
  const match = window.location.pathname.match(/^\/opportunities\/([^/]+)\/?$/);
  return match ? decodeURIComponent(match[1]) : null;
}

function AppIcon({ children, tone = "blue" }: { children: ReactNode; tone?: string }) { return <span className={`app-icon ${tone}`}>{children}</span>; }

export default function App() {
  const [view, setView] = useState<View>("home"), [meta, setMeta] = useState<Meta>(emptyMeta), [filters, setFilters] = useState<Filters>(initialFilters), [searchDraft, setSearchDraft] = useState("");
  const [opportunities, setOpportunities] = useState<Opportunity[]>([]), [latest, setLatest] = useState<Opportunity[]>([]), [selected, setSelected] = useState<Opportunity | null>(null), [page, setPage] = useState(1), [lastPage, setLastPage] = useState(1), [total, setTotal] = useState(0), [catalogTotal, setCatalogTotal] = useState(0);
  const [loading, setLoading] = useState(true), [error, setError] = useState(""), [user, setUser] = useState<User | null>(null), [authFeedback, setAuthFeedback] = useState(""), [authOpen, setAuthOpen] = useState(false), [profileOpen, setProfileOpen] = useState(false), [cvOpen, setCvOpen] = useState(false), [coverOpen, setCoverOpen] = useState(false), [mobileMenu, setMobileMenu] = useState(false), [favoritesOnly, setFavoritesOnly] = useState(false), [profileCompletion, setProfileCompletion] = useState(0), [cvCompletion, setCvCompletion] = useState(0);
  const [reportingOpportunity, setReportingOpportunity] = useState<Opportunity | null>(null);

  const loadOpportunities = useCallback(async () => {
    setLoading(true); setError("");
    try {
      let response: OpportunityCollection;
      if (favoritesOnly && user) response = await api<OpportunityCollection>(`/favorites?page=${page}`);
      else { const params = new URLSearchParams({ page: String(page), per_page: "12", sort: filters.sort }); Object.entries(filters).forEach(([k,v]) => { if (v && !["sort","international"].includes(k)) params.set(k, String(v).toLowerCase()); }); if (filters.international) params.set("international", "1"); response = await api<OpportunityCollection>(`/opportunities?${params}`); }
      setOpportunities(response.data); setLastPage(response.meta.last_page); setTotal(response.meta.total);
    } catch { setError("دریافت فرصت‌ها ممکن نشد. اتصال سرور و دیتابیس را بررسی کنید."); } finally { setLoading(false); }
  }, [favoritesOnly, filters, page, user]);
  useEffect(() => {
    api<Meta>("/meta").then(setMeta).catch(() => undefined);
    api<OpportunityCollection>("/opportunities?per_page=6&sort=latest").then((response) => { setLatest(response.data); setCatalogTotal(response.meta.total); }).catch(() => undefined);
    api<{ user: User }>("/auth/me").then(r => setUser(r.user)).catch(() => setUser(null));
  }, []);
  const openOpportunityPath = useCallback(async (slug: string) => {
    setLoading(true); setError("");
    try {
      const response = await api<{ data: Opportunity }>(`/opportunities/${encodeURIComponent(slug)}`);
      setSelected(response.data); setView("details");
    } catch {
      setSelected(null); setView("details");
      setError("این فرصت پیدا نشد یا مهلت آن به پایان رسیده است.");
    } finally { setLoading(false); }
  }, []);
  useEffect(() => {
    const slug = opportunitySlugFromPath();
    if (slug) void openOpportunityPath(slug);

    const onPopState = () => {
      const currentSlug = opportunitySlugFromPath();
      if (currentSlug) void openOpportunityPath(currentSlug);
      else { setSelected(null); setView("search"); }
    };
    window.addEventListener("popstate", onPopState);
    return () => window.removeEventListener("popstate", onPopState);
  }, [openOpportunityPath]);
  useEffect(() => {
    if (!user || !user.email_verified) { setProfileCompletion(0); setCvCompletion(0); return; }
    Promise.all([api<{ profile: Profile }>("/profile"), api<{ cv: GermanCv }>("/german-cv")]).then(([profileResponse, cvResponse]) => {
      const profile = profileResponse.profile;
      const profileFields = [profile.phone, profile.country, profile.birth_date, profile.german_level !== "none", profile.education_level, profile.education_title, profile.skills?.length, profile.preferred_category_ids?.length, profile.preferred_cities?.length, profile.available_from];
      setProfileCompletion(Math.round(profileFields.filter(Boolean).length / profileFields.length * 100));
      const cv = cvResponse.cv;
      const cvFields = [cv.headline, cv.summary, cv.contact?.phone, cv.contact?.city, cv.experiences?.length, cv.education?.length, cv.skills?.length, cv.languages?.length];
      setCvCompletion(Math.round(cvFields.filter(Boolean).length / cvFields.length * 100));
    }).catch(() => undefined);
  }, [user, profileOpen, cvOpen]);
  useEffect(() => { void loadOpportunities(); }, [loadOpportunities]); useEffect(() => { window.scrollTo({ top: 0, behavior: "smooth" }); }, [view]);
  const navigate = (next: View) => {
    if (window.location.pathname.startsWith("/opportunities/")) {
      window.history.pushState({}, "", "/");
    }
    setView(next); setMobileMenu(false);
  };
  function setFilter<K extends keyof Filters>(key: K, value: Filters[K]) { setPage(1); setFavoritesOnly(false); setFilters(c => ({ ...c, [key]: value })); }
  function submitSearch(e: FormEvent) { e.preventDefault(); setFilter("q", searchDraft.trim()); navigate("search"); }
  async function toggleFavorite(o: Opportunity) { if (!user) { setAuthOpen(true); return; } if (!user.email_verified) { window.location.href="/account"; return; } setOpportunities(a => a.map(x => x.id === o.id ? {...x,is_favorite:!x.is_favorite}:x)); try { await api(`/favorites/${o.slug}`, { method: o.is_favorite ? "DELETE" : "PUT" }); } catch { setOpportunities(a => a.map(x => x.id === o.id ? {...x,is_favorite:o.is_favorite}:x)); } }
  async function apply(o: Opportunity) {
    if (o.application_mode === "internal") {
      if (!user) { setAuthOpen(true); return; }
      if (!user.email_verified) { window.location.href = "/account"; return; }
      try {
        const response = await api<{ message: string }>(`/applications/${o.slug}`, { method: "POST", body: JSON.stringify({}) });
        window.alert(response.message);
        navigate("applications");
      } catch (exception) {
        window.alert(exception instanceof Error ? exception.message : "ارسال درخواست ممکن نشد.");
      }
      return;
    }
    if (o.application_url) window.open(o.application_url, "_blank", "noopener,noreferrer");
    if (user) void api(`/application-clicks/${o.slug}`, { method:"POST", body:JSON.stringify({channel:"application_url"}) }).catch(() => undefined);
  }
  const openDetails = (o: Opportunity) => {
    setSelected(o);
    window.history.pushState({}, "", `/opportunities/${encodeURIComponent(o.slug)}`);
    setView("details");
    setMobileMenu(false);
  };
  const findCategory = (slug: string) => { setFilter("category", slug); navigate("search"); };
  const findMatches = (germanLevel: string) => { setFilter("german_level", germanLevel); setFilter("sort", user ? "match" : "latest"); navigate("search"); };
  const featured = latest[0] || opportunities[0], activeFilterCount = [filters.category, filters.city, filters.german_level, filters.international].filter(Boolean).length;
  const navItems: Array<[View,string]> = [["search","فرصت‌ها"],["applications","درخواست‌های من"],["guide","راهنمای رشته‌ها"],["eligibility","بررسی شرایط"],["tools","ابزارها"],["employer","پنل کارفرما"]];
  return <div className="app-shell" dir="rtl"><header className="site-header"><div className="container nav-shell"><button className="brand" onClick={() => navigate("home")}><span className="brand-mark"><BriefcaseBusiness/></span><span><strong>Ausbildung</strong><b>Match</b></span></button><nav className={mobileMenu ? "mobile-open":""}>{navItems.map(([k,l]) => <button key={k} className={view===k?"active":""} onClick={() => navigate(k)}>{l}</button>)}</nav><div className="nav-actions">{user ? <><a className="nav-profile" href="/account"><UserRound size={18}/>{user.name.split(" ")[0]}</a><button className="icon-button" onClick={async()=>{await api("/auth/logout",{method:"POST"});setUser(null);}}><LogOut size={18}/></button></>:<button className="login-button" onClick={()=>setAuthOpen(true)}>ورود / ثبت‌نام</button>}<button className="mobile-toggle" onClick={()=>setMobileMenu(!mobileMenu)}>{mobileMenu?<X/>:<Menu/>}</button></div></div></header><main>
  {user && !user.email_verified && <div className="verify-banner container" role="status">{authFeedback || "ایمیل شما هنوز تأیید نشده است."} برای استفاده از حساب، <a href="/account">وضعیت ایمیل و ارسال دوباره لینک</a> را بررسی کنید.</div>}
  {view==="home"&&<Home total={catalogTotal} meta={meta} featured={latest.slice(0,2)} searchDraft={searchDraft} setSearchDraft={setSearchDraft} submitSearch={submitSearch} navigate={navigate} openDetails={openDetails} findCategory={findCategory} user={user} profileCompletion={profileCompletion} openProfile={()=>user?setProfileOpen(true):setAuthOpen(true)}/>}
  {view==="search"&&<SearchPage opportunities={opportunities} loading={loading} error={error} filters={filters} meta={meta} activeFilterCount={activeFilterCount} setFilter={setFilter} toggleFavorite={toggleFavorite} apply={apply} report={setReportingOpportunity} openDetails={openDetails} favoritesOnly={favoritesOnly} setFavoritesOnly={setFavoritesOnly} total={total} page={page} lastPage={lastPage} setPage={setPage} reload={loadOpportunities}/>}
  {view==="details"&&(loading&&!selected?<div className="empty-state container"><p>در حال دریافت فرصت…</p></div>:error&&!selected?<div className="empty-state container"><p>{error}</p><button onClick={()=>navigate("search")}>بازگشت به فرصت‌ها</button></div>:<DetailsPage opportunity={selected||featured} onBack={()=>navigate("search")} onFavorite={toggleFavorite} onApply={apply} onReport={setReportingOpportunity}/>)}
  {view==="guide"&&<GuidePage meta={meta} findCategory={findCategory}/>} {view==="eligibility"&&<><BlueHero eyebrow="ارزیابی واقعی و قابل توضیح" title="ارزیابی شرایط اولیه Ausbildung" text="سن، تحصیلات، زبان و سابقه‌ات را بررسی کن و ببین برای شروع اقدام چه قدم‌هایی باقی مانده است." icon={<ListChecks/>}/><EligibilityAssessment user={user} onLogin={()=>setAuthOpen(true)} onEditProfile={()=>setProfileOpen(true)} findMatches={findMatches}/></>} {view==="tools"&&<ToolsPage openCv={()=>user?setCvOpen(true):setAuthOpen(true)} openCover={()=>user?setCoverOpen(true):setAuthOpen(true)} navigate={navigate} cvCompletion={cvCompletion}/>} {view==="interview"&&<><BlueHero eyebrow="تمرین مرحله‌به‌مرحله" title="تمرین مصاحبه" text="پاسخ‌هایت را مرور کن و برای سؤال‌های رایج آماده شو." icon={<Mic/>}/><InterviewPractice/></>} {view==="applications"&&<><BlueHero eyebrow="وضعیت واقعی درخواست‌ها" title="درخواست‌های من" text="فرصت‌ها و مرحله‌ای را که خودت ثبت کرده‌ای پیگیری کن." icon={<FileCheck2/>}/><ApplicationsPage user={user} onLogin={()=>setAuthOpen(true)} openDetails={openDetails}/></>} {view==="employer"&&<><BlueHero eyebrow="مدیریت واقعی استخدام" title="پنل کارفرما" text="شرکت را ثبت، فرصت منتشر و وضعیت متقاضیان را مدیریت کنید." icon={<Building2/>}/><EmployerDashboard user={user} meta={meta} onLogin={()=>setAuthOpen(true)}/></>}</main><BottomNav view={view} navigate={navigate} openProfile={()=>user?setProfileOpen(true):setAuthOpen(true)}/>
  {authOpen&&<AuthDialog onClose={()=>setAuthOpen(false)} onSuccess={(u,message)=>{setUser(u);setAuthFeedback(message);setAuthOpen(false);}}/>}{profileOpen&&<ProfileDrawer meta={meta} onClose={()=>setProfileOpen(false)} onSaved={()=>api<{user:User}>("/auth/me").then(r=>setUser(r.user))}/>} {cvOpen&&user&&<CvBuilder user={user} onClose={()=>setCvOpen(false)}/>} {coverOpen&&user&&<CoverLetterBuilder user={user} opportunity={selected||featured||null} onClose={()=>setCoverOpen(false)}/>} {reportingOpportunity&&<ReportOpportunityDialog opportunity={reportingOpportunity} user={user} onClose={()=>setReportingOpportunity(null)}/>}</div>;
}

function BlueHero({eyebrow,title,text,icon}:{eyebrow:string;title:string;text:string;icon:ReactNode}) { return <section className="blue-hero"><div className="hero-blob one"/><div className="hero-blob two"/><div className="container blue-hero-grid"><div><span className="hero-eyebrow">{eyebrow}</span><h1>{title}</h1><p>{text}</p></div><div className="friendly-illustration"><span className="spark">✦</span><div className="illustration-orbit">{icon}</div><b>آینده روشن‌تر<br/>برای تو</b></div></div></section>; }

function Home({total,meta,featured,searchDraft,setSearchDraft,submitSearch,navigate,openDetails,findCategory,user,profileCompletion,openProfile}:any) { return <><section className="home-hero"><div className="hero-blob one"/><div className="hero-blob two"/><div className="container home-hero-grid"><div className="hero-copy"><span className="hero-eyebrow"><Sparkles size={17}/> آینده شغلی تو از اینجا شروع می‌شود</span><h1>سلام، آماده‌ای مسیر حرفه‌ای‌ات را در آلمان بسازی؟</h1><p>فرصت‌های آوسبیلدونگ را پیدا کن، شرایطت را بسنج و مدارک درخواست را آماده کن.</p><div className="hero-actions"><button className="primary-button" onClick={()=>navigate("search")}><Search/> جست‌وجوی آوسبیلدونگ</button><button className="outline-light" onClick={()=>navigate("eligibility")}><ListChecks/> بررسی شرایط من</button></div></div><div className="friendly-illustration large"><span className="spark">✦</span><div className="illustration-orbit"><GraduationCap/></div><b>قدم بعدی<br/>را پیدا کن</b></div></div></section><section className="home-content container"><form className="floating-search" onSubmit={submitSearch}><Search/><input value={searchDraft} onChange={(e:any)=>setSearchDraft(e.target.value)} placeholder="رشته، شهر یا شرکت"/><button>جست‌وجو</button></form><SectionHeading title="فرصت‌های تازه" action={()=>navigate("search")}/>{featured.length?<div className="featured-row">{featured.map((item:Opportunity)=><MiniOpportunity key={item.id} item={item} onClick={()=>openDetails(item)}/>)}</div>:<div className="empty-state"><p>هنوز فرصت منتشرشده‌ای وجود ندارد.</p></div>}<div className="quick-grid">{meta.categories.slice(0,2).map((category:any)=><Quick key={category.id} icon={<LayoutGrid/>} title={category.name_fa} text="مشاهده فرصت‌های موجود" onClick={()=>findCategory(category.slug)}/>)}<Quick icon={<Wrench/>} title="ابزارهای درخواست" text="رزومه و انگیزه‌نامه" onClick={()=>navigate("tools")}/></div><div className="profile-banner"><div className="progress-ring" style={{background:`radial-gradient(circle,#fff 56%,transparent 58%),conic-gradient(var(--blue) 0 ${profileCompletion}%,#e8eef5 ${profileCompletion}%)`}}>{fa.format(profileCompletion)}٪</div><div><h3>{user?"پروفایلت را کامل کن":"برای پیشنهادهای دقیق‌تر وارد شو"}</h3><p>{user?"درصد نمایش‌داده‌شده از اطلاعات واقعی پروفایل محاسبه می‌شود.":"پس از ورود، پروفایل و امتیاز تطابق فرصت‌ها برایت فعال می‌شود."}</p></div><button onClick={openProfile}>{user?"تکمیل پروفایل":"ورود / ثبت‌نام"} <ArrowLeft/></button></div><div className="stat-row"><span><b>{fa.format(total)}</b> فرصت فعال</span><span><b>{fa.format(meta.cities.length)}</b> شهر</span><span><b>{fa.format(meta.categories.length)}</b> حوزه شغلی</span></div></section></>; }
function SectionHeading({title,action}:any){return <div className="section-heading"><div><span>آخرین داده‌های منتشرشده</span><h2>{title}</h2></div><button onClick={action}>مشاهده همه <ChevronLeft/></button></div>};
function MiniOpportunity({item,onClick}:{item:Opportunity;onClick:()=>void}) { return <button className="mini-opportunity" onClick={onClick}><AppIcon><Building2/></AppIcon><div><b>{item.title_fa}</b><span dir="ltr">{item.title_de}</span><small><MapPin/> {item.city} · زبان {item.required_german_level}</small></div><ChevronLeft/></button>; }
function Quick({icon,title,text,onClick}:any){return <button onClick={onClick}><AppIcon>{icon}</AppIcon><b>{title}</b><span>{text}</span></button>}

function SearchPage(p:any) { return <><BlueHero eyebrow="فرصت مناسب همین نزدیکی‌ست" title="جست‌وجوی آوسبیلدونگ" text="فرصت‌های منتشرشده را با رشته، شهر و شرایط خودت فیلتر کن." icon={<Search/>}/><section className="page-surface container search-surface"><div className="search-box"><Search/><input value={p.filters.q} onChange={(e)=>p.setFilter("q",e.target.value)} placeholder="عنوان رشته، شهر یا شرکت"/></div>{!p.favoritesOnly&&<div className="filter-chips"><select value={p.filters.city} onChange={(e)=>p.setFilter("city",e.target.value)}><option value="">همه شهرها</option>{p.meta.cities.map((x:string)=><option key={x}>{x}</option>)}</select><select value={p.filters.german_level} onChange={(e)=>p.setFilter("german_level",e.target.value)}><option value="">سطح زبان</option>{p.meta.german_levels.map((x:string)=><option key={x}>{x}</option>)}</select><select value={p.filters.category} onChange={(e)=>p.setFilter("category",e.target.value)}><option value="">همه رشته‌ها</option>{p.meta.categories.map((x:any)=><option key={x.id} value={x.slug}>{x.name_fa}</option>)}</select><label><input type="checkbox" checked={p.filters.international} onChange={(e)=>p.setFilter("international",e.target.checked)}/> پذیرش بین‌المللی</label></div>}<div className="results-head"><h2>{p.favoritesOnly?"ذخیره‌شده‌ها":`${fa.format(p.total)} نتیجه پیدا شد`}</h2></div>{p.loading?<div className="cards-grid">{[1,2,3].map(x=><div className="skeleton-card" key={x}/>)}</div>:p.error?<div className="empty-state"><p>{p.error}</p><button onClick={p.reload}>تلاش دوباره</button></div>:p.opportunities.length===0?<div className="empty-state"><p>فرصتی مطابق فیلترهای فعلی پیدا نشد.</p></div>:<div className="cards-grid">{p.opportunities.map((item:Opportunity)=><div className="card-wrap" key={item.id}><OpportunityCard opportunity={item} onFavorite={p.toggleFavorite} onApply={p.apply} onReport={p.report}/><button className="card-detail-link" onClick={()=>p.openDetails(item)}>مشاهده جزئیات کامل <ChevronLeft/></button></div>)}</div>}{p.lastPage>1&&<div className="pagination"><button disabled={p.page<=1} onClick={()=>p.setPage(p.page-1)}>قبلی</button><span>{fa.format(p.page)} از {fa.format(p.lastPage)}</span><button disabled={p.page>=p.lastPage} onClick={()=>p.setPage(p.page+1)}>بعدی</button></div>}</section></>; }

function DetailsPage({opportunity,onBack,onFavorite,onApply,onReport}:any) { if(!opportunity)return <div className="empty-state container">فرصتی انتخاب نشده است.</div>; return <><section className="detail-hero"><div className="container"><button className="round-back" onClick={onBack}><ArrowLeft/></button><button className="round-save" onClick={()=>onFavorite(opportunity)}><Bookmark fill={opportunity.is_favorite?"currentColor":"none"}/></button><div className="detail-title"><AppIcon><BriefcaseBusiness/></AppIcon><div><h1 dir="ltr">{opportunity.title_de}</h1><p>{opportunity.employer_name}</p><span><MapPin/> {opportunity.city}{opportunity.state?` · ${opportunity.state}`:""}</span><span><CalendarDays/> شروع: {opportunity.start_date||"در آگهی مشخص نشده"}</span><span><BadgeEuro/> {opportunity.monthly_salary_from?`${fa.format(opportunity.monthly_salary_from)} €`:"حقوق در آگهی مشخص نشده"}</span></div></div><div className="friendly-illustration compact"><GraduationCap/></div></div></section><section className="page-surface container detail-content"><article className="content-card"><h2>درباره دوره</h2><p>{opportunity.description_fa}</p></article><article className="content-card"><h2>شرایط ثبت‌شده در آگهی</h2><ul className="check-list"><li>زبان آلمانی {opportunity.required_german_level}</li>{opportunity.education_requirement&&<li>{opportunity.education_requirement}</li>}<li>{opportunity.accepts_international?"پذیرش متقاضی بین‌المللی اعلام شده":"پذیرش بین‌المللی در منبع تأیید نشده"}</li></ul></article>{opportunity.skills.length>0&&<article className="content-card"><h2>مهارت‌های درج‌شده</h2><div className="tag-list">{opportunity.skills.map((x:string)=><span key={x}>{x}</span>)}</div></article>}<button className="primary-button wide-action" onClick={()=>onApply(opportunity)}>{opportunity.application_mode==="internal"?"ارسال درخواست مستقیم":"بازکردن صفحه درخواست کارفرما"} <ArrowLeft/></button><button type="button" className="detail-report-button" onClick={()=>onReport(opportunity)}>گزارش کلاهبرداری، لینک خراب، اطلاعات اشتباه یا فرصت منقضی</button></section></>; }

function GuidePage({meta,findCategory}:any){return <><BlueHero eyebrow="مسیرهای موجود در سامانه" title="راهنمای رشته‌ها" text="حوزه موردنظر را انتخاب کن و فقط فرصت‌های واقعی همان دسته را ببین." icon={<LayoutGrid/>}/><section className="page-surface container guide-grid"><article className="content-card"><h2>حوزه‌های آوسبیلدونگ</h2><div className="guide-categories">{meta.categories.map((category:any)=><button key={category.id} onClick={()=>findCategory(category.slug)}><AppIcon><GraduationCap/></AppIcon><span><b>{category.name_fa}</b><small dir="ltr">{category.name_de}</small></span><ChevronLeft/></button>)}</div>{meta.categories.length===0&&<p>هنوز دسته فعالی در سامانه ثبت نشده است.</p>}</article><article className="content-card"><h2>پیش از اقدام بررسی کن</h2><div className="document-row"><Check/>سطح زبان موردنیاز همان آگهی</div><div className="document-row"><Check/>تاریخ شروع و مهلت درخواست</div><div className="document-row"><Check/>شرایط مدرک تحصیلی و پذیرش بین‌المللی</div><div className="document-row"><Check/>نشانی صفحه رسمی درخواست کارفرما</div></article></section></>}


function ToolsPage({openCv,openCover,navigate,cvCompletion}:any){return <><BlueHero eyebrow="مدارک قابل ویرایش و ذخیره" title="رزومه و انگیزه‌نامه" text="اطلاعات را وارد کن، پیش‌نمایش بگیر و نسخه PDF چاپ کن." icon={<FileText/>}/><section className="page-surface container"><div className="tool-grid"><article className="tool-card blue"><AppIcon><FileText/></AppIcon><h2>رزومه آلمانی</h2><span>Lebenslauf</span><div className="progress"><i style={{width:`${cvCompletion}%`}}/></div><small>{fa.format(cvCompletion)}٪ تکمیل بر اساس فیلدهای ذخیره‌شده</small><button onClick={openCv}>ویرایش رزومه <ArrowLeft/></button></article><article className="tool-card green"><AppIcon tone="green"><FileCheck2/></AppIcon><h2>انگیزه‌نامه</h2><span>Anschreiben</span><p>قالب اولیه را با فرصت انتخاب‌شده بساز، ویرایش و ذخیره کن.</p><button onClick={openCover}>ساخت / ویرایش انگیزه‌نامه <ArrowLeft/></button></article></div><button className="interview-link" onClick={()=>navigate("interview")}><Mic/> تمرین مرحله‌ای مصاحبه <ChevronLeft/></button></section></>}
function BottomNav({view,navigate,openProfile}:any){const items:Array<[View,string,ReactNode]>=[["home","خانه",<House/>],["search","جست‌وجو",<Search/>],["applications","درخواست‌ها",<FileText/>],["tools","ابزارها",<BriefcaseBusiness/>]];return <nav className="bottom-nav">{items.map(([k,l,i])=><button key={k} className={view===k?"active":""} onClick={()=>navigate(k)}>{i}<span>{l}</span></button>)}<button onClick={openProfile}><UserRound/><span>پروفایل</span></button></nav>}
