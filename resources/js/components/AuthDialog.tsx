import { FormEvent, useState } from "react";
import { LogIn, UserPlus, X } from "lucide-react";
import { api, ApiError, jsonBody } from "../api";
import type { User } from "../types";

type Props = {
  onClose: () => void;
  onSuccess: (user: User, message: string) => void;
};

export default function AuthDialog({ onClose, onSuccess }: Props) {
  const [mode, setMode] = useState<"login" | "register">("login");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setBusy(true);
    setError("");
    const data = new FormData(event.currentTarget);

    const payload = mode === "login"
      ? {
          email: data.get("email"),
          password: data.get("password"),
          remember: data.get("remember") === "on",
        }
      : {
          name: data.get("name"),
          email: data.get("email"),
          password: data.get("password"),
          password_confirmation: data.get("password_confirmation"),
          accept_terms: data.get("accept_terms") === "on",
          accept_privacy: data.get("accept_privacy") === "on",
        };

    try {
      const response = await api<{ user: User; message: string }>(`/auth/${mode}`, {
        method: "POST",
        ...jsonBody(payload),
      });
      onSuccess(response.user, response.message);
    } catch (exception) {
      if (exception instanceof ApiError) {
        const firstValidationError = Object.values(exception.errors)[0]?.[0];
        setError(firstValidationError || exception.message);
      } else {
        setError("اتصال به سرور برقرار نشد.");
      }
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="modal-backdrop" role="presentation" onMouseDown={onClose}>
      <section className="auth-dialog" role="dialog" aria-modal="true" aria-label="ورود یا ثبت‌نام" onMouseDown={(event) => event.stopPropagation()}>
        <button className="dialog-close" type="button" onClick={onClose} aria-label="بستن"><X size={21} /></button>
        <div className="auth-icon">{mode === "login" ? <LogIn /> : <UserPlus />}</div>
        <h2>{mode === "login" ? "خوش آمدید" : "ساخت حساب کاربری"}</h2>
        <p>{mode === "login" ? "برای ذخیره فرصت‌ها و دیدن درصد تطابق وارد شوید." : "پروفایل خود را بسازید تا فرصت‌های مناسب‌تر را پیدا کنید."}</p>

        <div className="auth-tabs" role="tablist">
          <button type="button" className={mode === "login" ? "active" : ""} onClick={() => { setMode("login"); setError(""); }}>ورود</button>
          <button type="button" className={mode === "register" ? "active" : ""} onClick={() => { setMode("register"); setError(""); }}>ثبت‌نام</button>
        </div>

        <form onSubmit={submit} className="stack-form">
          {mode === "register" && (
            <label>نام و نام خانوادگی<input name="name" autoComplete="name" required /></label>
          )}
          <label>ایمیل<input name="email" type="email" autoComplete="email" dir="ltr" required /></label>
          <label>رمز عبور<input name="password" type="password" minLength={8} autoComplete={mode === "login" ? "current-password" : "new-password"} dir="ltr" required /></label>
          {mode === "register" && (
            <label>تکرار رمز عبور<input name="password_confirmation" type="password" minLength={8} autoComplete="new-password" dir="ltr" required /></label>
          )}
          {mode === "register" && <>
            <label className="check-line legal-consent"><input name="accept_terms" type="checkbox" required /> <span><a href="/terms" target="_blank">شرایط استفاده</a> را خوانده‌ام و می‌پذیرم.</span></label>
            <label className="check-line legal-consent"><input name="accept_privacy" type="checkbox" required /> <span><a href="/privacy" target="_blank">سیاست حریم خصوصی</a> و پردازش داده‌های حساب را تأیید می‌کنم.</span></label>
          </>}
          {mode === "login" && <label className="check-line"><input name="remember" type="checkbox" /> مرا به خاطر بسپار</label>}
          {mode === "login" && <a href="/forgot-password">رمز عبور را فراموش کرده‌اید؟</a>}
          {error && <div className="form-error" role="alert">{error}</div>}
          <button className="primary-button wide" type="submit" disabled={busy}>
            {busy ? "کمی صبر کنید…" : mode === "login" ? "ورود به حساب" : "ساخت حساب"}
          </button>
        </form>
      </section>
    </div>
  );
}
