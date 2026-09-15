import { FormEvent, useEffect, useState } from "react";
import { api, ApiError, jsonBody } from "../api";
import type { User } from "../types";

function messageFor(exception: unknown): string {
  if (exception instanceof ApiError) return Object.values(exception.errors)[0]?.[0] || exception.message;
  return "اتصال به سرور برقرار نشد. دوباره تلاش کنید.";
}

function FormPage({ title, description, children }: { title: string; description: string; children: React.ReactNode }) {
  return <main className="auth-page" dir="rtl"><section className="auth-dialog"><h1>{title}</h1><p>{description}</p>{children}<a href="/">بازگشت به صفحهٔ اصلی</a></section></main>;
}

function ForgotPage() {
  const [busy, setBusy] = useState(false), [error, setError] = useState(""), [success, setSuccess] = useState("");
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); setBusy(true); setError(""); setSuccess("");
    const email = new FormData(event.currentTarget).get("email");
    try {
      const result = await api<{ message: string }>("/auth/forgot-password", { method: "POST", ...jsonBody({ email }) });
      setSuccess(result.message);
    } catch (exception) { setError(messageFor(exception)); } finally { setBusy(false); }
  }
  return <FormPage title="بازیابی رمز عبور" description="آدرس ایمیل حساب خود را وارد کنید."><form className="stack-form" onSubmit={submit}><label>ایمیل<input type="email" name="email" required dir="ltr" autoComplete="email" /></label><button className="primary-button wide" disabled={busy}>{busy ? "در حال ارسال…" : "درخواست لینک بازیابی"}</button></form>{error && <p className="form-error" role="alert">{error}</p>}{success && <p className="form-success" role="status">{success}</p>}</FormPage>;
}

function ResetPage() {
  const params = new URLSearchParams(window.location.search);
  const token = params.get("token"), email = params.get("email");
  const [busy, setBusy] = useState(false), [error, setError] = useState(""), [success, setSuccess] = useState("");
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); setBusy(true); setError("");
    const data = new FormData(event.currentTarget);
    try {
      const result = await api<{ message: string }>("/auth/reset-password", { method: "POST", ...jsonBody({ email, token, password: data.get("password"), password_confirmation: data.get("password_confirmation") }) });
      setSuccess(result.message); window.history.replaceState({}, "", "/reset-password");
    } catch (exception) { setError(messageFor(exception)); } finally { setBusy(false); }
  }
  return <FormPage title="تعیین رمز جدید" description="رمز جدید خود را وارد کنید.">{!token || !email ? <p className="form-error" role="alert">لینک بازیابی ناقص است. <a href="/forgot-password">درخواست لینک تازه</a></p> : success ? <p className="form-success" role="status">{success} <a href="/">ورود به حساب</a></p> : <form className="stack-form" onSubmit={submit}><label>رمز جدید<input type="password" name="password" minLength={8} required autoComplete="new-password" /></label><label>تکرار رمز جدید<input type="password" name="password_confirmation" minLength={8} required autoComplete="new-password" /></label><button className="primary-button wide" disabled={busy}>{busy ? "در حال ذخیره…" : "ثبت رمز جدید"}</button></form>}{error && <p className="form-error" role="alert">{error}</p>}</FormPage>;
}

function VerifyPage() {
  const [busy, setBusy] = useState(true), [error, setError] = useState(""), [success, setSuccess] = useState("");
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const id = params.get("id"), hash = params.get("hash"), expires = params.get("expires"), signature = params.get("signature");
    if (!id || !hash || !expires || !signature || !/^\d+$/.test(id) || !/^[a-f0-9]{40}$/.test(hash)) {
      setError("لینک تأیید ناقص است."); setBusy(false); return;
    }
    api<{ message: string }>(`/auth/email/verify/${id}/${hash}?expires=${encodeURIComponent(expires)}&signature=${encodeURIComponent(signature)}`)
      .then(result => { setSuccess(result.message); window.history.replaceState({}, "", "/verify-email"); })
      .catch(exception => setError(messageFor(exception)))
      .finally(() => setBusy(false));
  }, []);
  return <FormPage title="تأیید ایمیل" description="در حال بررسی لینک تأیید…">{busy && <p role="status">کمی صبر کنید…</p>}{error && <p className="form-error" role="alert">{error} <a href="/account">ارسال لینک جدید</a></p>}{success && <p className="form-success" role="status">{success} <a href="/">بازگشت به سایت</a></p>}</FormPage>;
}

function AccountPage() {
  const [user, setUser] = useState<User | null>(null), [busy, setBusy] = useState(false), [loading, setLoading] = useState(true), [error, setError] = useState(""), [success, setSuccess] = useState("");
  useEffect(() => { api<{ user: User }>("/auth/me").then(result => setUser(result.user)).catch(exception => setError(exception instanceof ApiError && exception.status === 401 ? "برای مدیریت حساب ابتدا از صفحه اصلی وارد شوید." : messageFor(exception))).finally(() => setLoading(false)); }, []);
  async function resend() {
    setBusy(true); setError(""); setSuccess("");
    try { const result = await api<{ message: string }>("/auth/email/resend", { method: "POST" }); setSuccess(result.message); } catch (exception) { setError(messageFor(exception)); } finally { setBusy(false); }
  }
  async function change(event: FormEvent<HTMLFormElement>, kind: "email" | "password") {
    event.preventDefault(); setBusy(true); setError(""); setSuccess("");
    const data = Object.fromEntries(new FormData(event.currentTarget).entries());
    try { const result = await api<{ message: string }>(`/auth/${kind}`, { method: "PUT", ...jsonBody(data) }); setSuccess(result.message); if (kind === "email" || kind === "password") setUser(null); } catch (exception) { setError(messageFor(exception)); } finally { setBusy(false); }
  }
  return <FormPage title="مدیریت حساب" description={user ? `ایمیل حساب: ${user.email}` : "وضعیت تأیید ایمیل و تنظیمات امنیتی"}>{loading && <p role="status">در حال دریافت اطلاعات…</p>}{user && <><p role="status">{user.email_verified ? "ایمیل شما تأیید شده است." : "ایمیل شما هنوز تأیید نشده است."}</p>{!user.email_verified && <button className="primary-button wide" onClick={resend} disabled={busy}>{busy ? "در حال ارسال…" : "ارسال دوباره لینک تأیید"}</button>}<h2>تغییر ایمیل</h2><form className="stack-form" onSubmit={event => change(event, "email")}><label>ایمیل جدید<input name="email" type="email" required autoComplete="email" dir="ltr" /></label><label>رمز عبور فعلی<input name="current_password" type="password" required autoComplete="current-password" /></label><button className="primary-button wide" disabled={busy}>ثبت ایمیل جدید</button></form><h2>تغییر رمز عبور</h2><form className="stack-form" onSubmit={event => change(event, "password")}><label>رمز فعلی<input name="current_password" type="password" required autoComplete="current-password" /></label><label>رمز جدید<input name="password" type="password" minLength={8} required autoComplete="new-password" /></label><label>تکرار رمز جدید<input name="password_confirmation" type="password" minLength={8} required autoComplete="new-password" /></label><button className="primary-button wide" disabled={busy}>تغییر رمز</button></form></>}{error && <p className="form-error" role="alert">{error}</p>}{success && <p className="form-success" role="status">{success}</p>}</FormPage>;
}

export default function AuthPages() {
  switch (window.location.pathname) {
    case "/verify-email": return <VerifyPage />;
    case "/forgot-password": return <ForgotPage />;
    case "/reset-password": return <ResetPage />;
    default: return <AccountPage />;
  }
}
