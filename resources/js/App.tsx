import { FormEvent, useCallback, useEffect, useState } from "react";
import {
  ArrowLeft,
  BookOpenCheck,
  Bookmark,
  BriefcaseBusiness,
  CheckCircle2,
  ChevronLeft,
  ChevronRight,
  FileUser,
  Filter,
  Languages,
  LogOut,
  Menu,
  Search,
  ShieldCheck,
  Sparkles,
  UserRound,
  X,
} from "lucide-react";
import { api } from "./api";
import AuthDialog from "./components/AuthDialog";
import CvBuilder from "./components/CvBuilder";
import OpportunityCard from "./components/OpportunityCard";
import ProfileDrawer from "./components/ProfileDrawer";
import type { Meta, Opportunity, OpportunityCollection, User } from "./types";

type Filters = {
  q: string;
  category: string;
  city: string;
  german_level: string;
  international: boolean;
  sort: string;
};

const emptyMeta: Meta = { categories: [], cities: [], german_levels: ["A2", "B1", "B2", "C1"] };
const initialFilters: Filters = { q: "", category: "", city: "", german_level: "", international: false, sort: "latest" };

export default function App() {
  const [meta, setMeta] = useState<Meta>(emptyMeta);
  const [filters, setFilters] = useState<Filters>(initialFilters);
  const [searchDraft, setSearchDraft] = useState("");
  const [opportunities, setOpportunities] = useState<Opportunity[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [user, setUser] = useState<User | null>(null);
  const [authOpen, setAuthOpen] = useState(false);
  const [profileOpen, setProfileOpen] = useState(false);
  const [cvOpen, setCvOpen] = useState(false);
  const [mobileMenu, setMobileMenu] = useState(false);
  const [favoritesOnly, setFavoritesOnly] = useState(false);

  const loadOpportunities = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      let response: OpportunityCollection;
      if (favoritesOnly && user) {
        response = await api<OpportunityCollection>(`/favorites?page=${page}`);
      } else {
        const params = new URLSearchParams({ page: String(page), per_page: "12", sort: filters.sort });
        if (filters.q) params.set("q", filters.q);
        if (filters.category) params.set("category", filters.category);
        if (filters.city) params.set("city", filters.city);
        if (filters.german_level) params.set("german_level", filters.german_level.toLowerCase());
        if (filters.international) params.set("international", "1");
        response = await api<OpportunityCollection>(`/opportunities?${params}`);
      }
      setOpportunities(response.data);
      setLastPage(response.meta.last_page);
      setTotal(response.meta.total);
    } catch {
      setError("دریافت فرصت‌ها ممکن نشد. اتصال سرور و دیتابیس را بررسی کنید.");
    } finally {
      setLoading(false);
    }
  }, [favoritesOnly, filters, page, user]);

  useEffect(() => {
    api<Meta>("/meta").then(setMeta).catch(() => undefined);
    api<{ user: User }>("/auth/me").then((response) => setUser(response.user)).catch(() => setUser(null));
  }, []);

  useEffect(() => {
    void loadOpportunities();
  }, [loadOpportunities]);

  function setFilter<K extends keyof Filters>(key: K, value: Filters[K]) {
    setPage(1);
    setFavoritesOnly(false);
    setFilters((current) => ({ ...current, [key]: value }));
  }

  function submitSearch(event: FormEvent) {
    event.preventDefault();
    setFilter("q", searchDraft.trim());
    document.querySelector("#opportunities")?.scrollIntoView({ behavior: "smooth" });
  }

  async function refreshUser() {
    const response = await api<{ user: User }>("/auth/me");
    setUser(response.user);
  }

  async function logout() {
    await api("/auth/logout", { method: "POST" });
    setUser(null);
    setFavoritesOnly(false);
  }

  async function toggleFavorite(opportunity: Opportunity) {
    if (!user) {
      setAuthOpen(true);
      return;
    }
    setOpportunities((current) => current.map((item) => item.id === opportunity.id ? { ...item, is_favorite: !item.is_favorite } : item));
    try {
      await api(`/favorites/${opportunity.slug}`, { method: opportunity.is_favorite ? "DELETE" : "PUT" });
      if (favoritesOnly && opportunity.is_favorite) setOpportunities((current) => current.filter((item) => item.id !== opportunity.id));
    } catch {
      setOpportunities((current) => current.map((item) => item.id === opportunity.id ? { ...item, is_favorite: opportunity.is_favorite } : item));
    }
  }

  function apply(opportunity: Opportunity) {
    window.open(opportunity.application_url, "_blank", "noopener,noreferrer");
    if (user) void api(`/application-clicks/${opportunity.slug}`, { method: "POST", body: JSON.stringify({ channel: "application_url" }) }).catch(() => undefined);
  }

  function showFavorites() {
    if (!user) {
      setAuthOpen(true);
      return;
    }
    setFavoritesOnly(true);
    setPage(1);
    document.querySelector("#opportunities")?.scrollIntoView({ behavior: "smooth" });
  }

  const activeFilterCount = [filters.category, filters.city, filters.german_level, filters.international].filter(Boolean).length;

  return (
    <>
      <header className="site-header">
        <div className="container nav-shell">
          <a href="#top" className="brand" aria-label="صفحه اصلی">
            <span className="brand-mark"><BriefcaseBusiness /></span>
            <span><strong>Ausbildung</strong><b>Match</b></span>
          </a>
          <nav className={mobileMenu ? "mobile-open" : ""}>
            <a href="#opportunities" onClick={() => setMobileMenu(false)}>فرصت‌ها</a>
            <a href="#how-it-works" onClick={() => setMobileMenu(false)}>راهنما</a>
            <button type="button" onClick={showFavorites}><Bookmark size={17} /> ذخیره‌شده‌ها</button>
            {user?.is_admin && <a href="/admin">پنل مدیریت</a>}
          </nav>
          <div className="nav-actions">
            {user ? (
              <>
                <button type="button" className="nav-profile" onClick={() => setProfileOpen(true)}><UserRound size={18} /> {user.name.split(" ")[0]}</button>
                <button type="button" className="icon-button" onClick={logout} title="خروج"><LogOut size={19} /></button>
              </>
            ) : <button type="button" className="login-button" onClick={() => setAuthOpen(true)}>ورود / ثبت‌نام</button>}
            <button type="button" className="mobile-toggle" onClick={() => setMobileMenu((value) => !value)} aria-label="منو">{mobileMenu ? <X /> : <Menu />}</button>
          </div>
        </div>
      </header>

      <main id="top">
        <section className="hero">
          <div className="hero-orb orb-one" /><div className="hero-orb orb-two" />
          <div className="container hero-content">
            <div className="hero-copy">
              <span className="eyebrow"><Sparkles size={16} /> مسیر حرفه‌ای شما در آلمان</span>
              <h1>آوسبیلدونگ مناسب خودت را <em>هوشمندانه</em> پیدا کن</h1>
              <p>فرصت‌های معتبر آموزش حرفه‌ای آلمان را جست‌وجو کن، پروفایلت را بساز و ببین هر موقعیت چقدر با شرایط تو هماهنگ است.</p>
              <form className="hero-search" onSubmit={submitSearch}>
                <Search size={21} />
                <input value={searchDraft} onChange={(event) => setSearchDraft(event.target.value)} placeholder="عنوان رشته، شرکت یا شهر…" aria-label="جست‌وجو" />
                <button type="submit">جست‌وجو <ArrowLeft size={18} /></button>
              </form>
              <div className="trust-row">
                <span><CheckCircle2 /> منابع شفاف</span><span><ShieldCheck /> اطلاعات امن</span><span><Languages /> کاملاً فارسی</span>
              </div>
            </div>
            <div className="hero-card-wrap" aria-hidden="true">
              <div className="hero-match-card">
                <span className="mini-label">فرصت پیشنهادی امروز</span>
                <div className="hero-card-title"><div className="company-logo">AM</div><div><strong>Fachinformatiker/in</strong><span>Berlin · Ausbildung Dual</span></div></div>
                <div className="hero-score"><div className="score-ring"><b>۹۲٪</b></div><div><strong>تطابق عالی</strong><span>زبان، مهارت و علاقه‌مندی</span></div></div>
                <div className="fake-bars"><i /><i /><i /></div>
              </div>
              <div className="floating-chip"><span>+{new Intl.NumberFormat("fa-IR").format(total || 120)}</span> فرصت فعال</div>
            </div>
          </div>
        </section>

        <section className="stats-strip">
          <div className="container">
            <div><strong>{new Intl.NumberFormat("fa-IR").format(total)}</strong><span>فرصت در سامانه</span></div>
            <div><strong>{new Intl.NumberFormat("fa-IR").format(meta.cities.length)}</strong><span>شهر آلمان</span></div>
            <div><strong>{new Intl.NumberFormat("fa-IR").format(meta.categories.length)}</strong><span>حوزه شغلی</span></div>
            <div><strong>۱۰۰٪</strong><span>رایگان برای متقاضی</span></div>
          </div>
        </section>

        <section className="opportunities-section" id="opportunities">
          <div className="container">
            <div className="section-heading">
              <div><span className="section-kicker">جست‌وجوی فرصت‌ها</span><h2>{favoritesOnly ? "فرصت‌های ذخیره‌شده" : "جدیدترین موقعیت‌ها"}</h2></div>
              {favoritesOnly && <button className="text-button" onClick={() => { setFavoritesOnly(false); setPage(1); }}>نمایش همه فرصت‌ها</button>}
            </div>

            {!favoritesOnly && (
              <div className="filter-panel">
                <span className="filter-title"><Filter size={18} /> فیلترها {activeFilterCount > 0 && <b>{activeFilterCount}</b>}</span>
                <label>حوزه
                  <select value={filters.category} onChange={(event) => setFilter("category", event.target.value)}><option value="">همه حوزه‌ها</option>{meta.categories.map((category) => <option key={category.id} value={category.slug}>{category.name_fa}</option>)}</select>
                </label>
                <label>شهر
                  <select value={filters.city} onChange={(event) => setFilter("city", event.target.value)}><option value="">همه شهرها</option>{meta.cities.map((city) => <option key={city} value={city}>{city}</option>)}</select>
                </label>
                <label>سطح زبان
                  <select value={filters.german_level} onChange={(event) => setFilter("german_level", event.target.value)}><option value="">هر سطحی</option>{meta.german_levels.map((level) => <option key={level}>{level}</option>)}</select>
                </label>
                <label>مرتب‌سازی
                  <select value={filters.sort} onChange={(event) => setFilter("sort", event.target.value)}>
                    <option value="latest">جدیدترین</option><option value="start">تاریخ شروع</option><option value="salary">بیشترین حقوق</option>{user?.profile_completed && <option value="match">بیشترین تطابق</option>}
                  </select>
                </label>
                <label className="international-toggle"><input type="checkbox" checked={filters.international} onChange={(event) => setFilter("international", event.target.checked)} /><span>فقط پذیرش بین‌المللی</span></label>
                {activeFilterCount > 0 && <button type="button" className="clear-filters" onClick={() => { setFilters({ ...initialFilters, q: filters.q }); setPage(1); }}>پاک کردن فیلترها</button>}
              </div>
            )}

            {loading ? (
              <div className="cards-grid skeleton-grid">{Array.from({ length: 6 }).map((_, index) => <div className="skeleton-card" key={index}><i /><i /><i /><i /></div>)}</div>
            ) : error ? (
              <div className="empty-state"><ShieldCheck /><h3>فرصت‌ها بارگذاری نشدند</h3><p>{error}</p><button className="secondary-button" onClick={loadOpportunities}>تلاش دوباره</button></div>
            ) : opportunities.length ? (
              <div className="cards-grid">{opportunities.map((opportunity) => <OpportunityCard key={opportunity.id} opportunity={opportunity} onFavorite={toggleFavorite} onApply={apply} />)}</div>
            ) : (
              <div className="empty-state"><Search /><h3>نتیجه‌ای پیدا نشد</h3><p>فیلترها را تغییر دهید یا عبارت دیگری جست‌وجو کنید.</p></div>
            )}

            {lastPage > 1 && <div className="pagination"><button disabled={page <= 1} onClick={() => setPage((value) => value - 1)}><ChevronRight /></button><span>صفحه {new Intl.NumberFormat("fa-IR").format(page)} از {new Intl.NumberFormat("fa-IR").format(lastPage)}</span><button disabled={page >= lastPage} onClick={() => setPage((value) => value + 1)}><ChevronLeft /></button></div>}
          </div>
        </section>

        <section className="how-section" id="how-it-works">
          <div className="container">
            <div className="section-heading centered"><div><span className="section-kicker">ساده و کاربردی</span><h2>سه قدم تا فرصت مناسب</h2></div></div>
            <div className="steps-grid">
              <article><span>۱</span><UserRound /><h3>پروفایل بساز</h3><p>زبان، تحصیلات، مهارت و شهرهای موردعلاقه‌ات را ثبت کن.</p></article>
              <article><span>۲</span><Sparkles /><h3>تطابق را ببین</h3><p>سامانه برای هر فرصت یک امتیاز قابل‌فهم محاسبه می‌کند.</p></article>
              <article><span>۳</span><BookOpenCheck /><h3>درخواست بده</h3><p>جزئیات را بررسی کن و از مسیر رسمی منبع اقدام کن.</p></article>
            </div>
          </div>
        </section>

        <section className="cta-section">
          <div className="container cta-card">
            <div><span>آماده‌ای شروع کنی؟</span><h2>رزومه آلمانی‌ات را همین‌جا بساز</h2><p>یک Lebenslauf تمیز بساز و آن را با چاپ مرورگر به PDF تبدیل کن.</p></div>
            <button className="light-button" onClick={() => user ? setCvOpen(true) : setAuthOpen(true)}><FileUser /> ساخت رزومه آلمانی</button>
          </div>
        </section>
      </main>

      <footer><div className="container"><div className="brand footer-brand"><span className="brand-mark"><BriefcaseBusiness /></span><span><strong>Ausbildung</strong><b>Match</b></span></div><p>این سامانه واسطه استخدام نیست؛ پیش از ارسال درخواست، جزئیات را در منبع رسمی بررسی کنید.</p><span>© {new Date().getFullYear()} Ausbildung Match</span></div></footer>

      {authOpen && <AuthDialog onClose={() => setAuthOpen(false)} onSuccess={(authenticatedUser) => { setUser(authenticatedUser); setAuthOpen(false); }} />}
      {profileOpen && <ProfileDrawer meta={meta} onClose={() => setProfileOpen(false)} onSaved={() => void refreshUser()} />}
      {cvOpen && user && <CvBuilder user={user} onClose={() => setCvOpen(false)} />}
    </>
  );
}
