import { FormEvent, useState } from "react";
import { LogIn, UserPlus, X } from "lucide-react";
import Swal from "sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";
import { api, ApiError, jsonBody } from "../api";
import type { User } from "../types";

type Props = {
  onClose: () => void;
  onSuccess: (user: User, message: string) => void;
};

type AuthField = "name" | "email" | "password" | "password_confirmation" | "accept_terms" | "accept_privacy";
type FieldErrors = Partial<Record<AuthField, string>>;

const authFieldNames: Record<AuthField, string> = {
  name: "نام و نام خانوادگی",
  email: "ایمیل",
  password: "رمز عبور",
  password_confirmation: "تکرار رمز عبور",
  accept_terms: "پذیرش شرایط استفاده",
  accept_privacy: "تأیید سیاست حریم خصوصی",
};

function firstError(errors: FieldErrors): string {
  return Object.values(errors).find(Boolean) || "اطلاعات فرم را بررسی و دوباره تلاش کنید.";
}

function clientValidation(mode: "login" | "register", data: FormData): FieldErrors {
  const errors: FieldErrors = {};
  const email = String(data.get("email") || "").trim();
  const password = String(data.get("password") || "");

  if (mode === "register" && !String(data.get("name") || "").trim()) errors.name = "نام و نام خانوادگی را وارد کنید.";
  if (!email) errors.email = "آدرس ایمیل را وارد کنید.";
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.email = "آدرس ایمیل معتبر نیست؛ نمونه صحیح: name@example.com";
  if (!password) errors.password = "رمز عبور را وارد کنید.";

  if (mode === "register") {
    if (password && password.length < 8) errors.password = "رمز عبور باید حداقل ۸ نویسه باشد.";
    else if (password && (!/[A-Za-z\u0600-\u06ff]/.test(password) || !/\d/.test(password))) errors.password = "رمز عبور باید حداقل یک حرف و یک عدد داشته باشد.";
    if (String(data.get("password_confirmation") || "") !== password) errors.password_confirmation = "رمز عبور و تکرار آن یکسان نیستند.";
    if (data.get("accept_terms") !== "on") errors.accept_terms = "برای ساخت حساب باید شرایط استفاده را بپذیرید.";
    if (data.get("accept_privacy") !== "on") errors.accept_privacy = "برای ساخت حساب باید سیاست حریم خصوصی را تأیید کنید.";
  }

  return errors;
}

function apiFieldErrors(exception: ApiError): FieldErrors {
  const result: FieldErrors = {};
  for (const [field, messages] of Object.entries(exception.errors)) {
    if (field in authFieldNames && messages[0]) result[field as AuthField] = messages[0];
  }
  return result;
}

function apiErrorMessage(exception: ApiError, validationErrors: FieldErrors): string {
  const validationMessage = Object.values(validationErrors).find(Boolean);
  if (validationMessage) return validationMessage;
  if (exception.status === 401) return "نشست شما منقضی شده است. صفحه را تازه‌سازی و دوباره تلاش کنید.";
  if (exception.status === 403) return "اجازه انجام این درخواست را ندارید. دوباره وارد حساب شوید و تلاش کنید.";
  if (exception.status === 419) return "اعتبار فرم منقضی شده است. صفحه را تازه‌سازی و دوباره تلاش کنید.";
  if (exception.status === 422) return "بعضی اطلاعات فرم معتبر نیست. فیلدهای مشخص‌شده را اصلاح کنید.";
  if (exception.status === 429) return "تعداد تلاش‌ها زیاد است. چند دقیقه صبر کنید و دوباره تلاش کنید.";
  if (exception.status >= 500) return "در حال حاضر سرویس در دسترس نیست. چند دقیقه دیگر دوباره تلاش کنید.";
  return /[\u0600-\u06ff]/.test(exception.message)
    ? exception.message
    : "انجام درخواست ممکن نشد. اطلاعات فرم و اتصال اینترنت را بررسی و دوباره تلاش کنید.";
}

async function showAuthAlert(icon: "error" | "success", title: string, message: string) {
  await Swal.fire({
    icon,
    title,
    text: message,
    confirmButtonText: icon === "success" ? "ادامه" : "متوجه شدم",
    customClass: { popup: "auth-swal", confirmButton: "auth-swal-confirm" },
    buttonsStyling: false,
  });
}

export default function AuthDialog({ onClose, onSuccess }: Props) {
  const [mode, setMode] = useState<"login" | "register">("login");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setBusy(true);
    setError("");
    setFieldErrors({});
    const data = new FormData(event.currentTarget);
    const localErrors = clientValidation(mode, data);

    if (Object.keys(localErrors).length > 0) {
      const message = firstError(localErrors);
      setFieldErrors(localErrors);
      setError(message);
      const firstInvalidField = Object.keys(localErrors)[0];
      (event.currentTarget.elements.namedItem(firstInvalidField) as HTMLElement | null)?.focus();
      setBusy(false);
      await showAuthAlert("error", "اطلاعات فرم کامل نیست", message);
      return;
    }

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
      await showAuthAlert("success", mode === "login" ? "ورود موفق بود" : "حساب شما ساخته شد", response.message);
      onSuccess(response.user, response.message);
    } catch (exception) {
      if (exception instanceof ApiError) {
        const serverFieldErrors = apiFieldErrors(exception);
        const message = apiErrorMessage(exception, serverFieldErrors);
        setFieldErrors(serverFieldErrors);
        setError(message);
        await showAuthAlert("error", mode === "login" ? "ورود انجام نشد" : "ثبت‌نام انجام نشد", message);
      } else {
        const message = "اتصال به سرور برقرار نشد. اینترنت خود را بررسی کنید و دوباره تلاش کنید.";
        setError(message);
        await showAuthAlert("error", "خطا در اتصال", message);
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
          <button type="button" className={mode === "login" ? "active" : ""} onClick={() => { setMode("login"); setError(""); setFieldErrors({}); }}>ورود</button>
          <button type="button" className={mode === "register" ? "active" : ""} onClick={() => { setMode("register"); setError(""); setFieldErrors({}); }}>ثبت‌نام</button>
        </div>

        <form onSubmit={submit} className="stack-form" noValidate>
          {mode === "register" && (
            <label>نام و نام خانوادگی<input name="name" autoComplete="name" aria-invalid={Boolean(fieldErrors.name)} aria-describedby={fieldErrors.name ? "name-error" : undefined} className={fieldErrors.name ? "has-error" : ""} />{fieldErrors.name && <small id="name-error" className="field-error">{fieldErrors.name}</small>}</label>
          )}
          <label>ایمیل<input name="email" type="email" autoComplete="email" dir="ltr" aria-invalid={Boolean(fieldErrors.email)} aria-describedby={fieldErrors.email ? "email-error" : undefined} className={fieldErrors.email ? "has-error" : ""} />{fieldErrors.email && <small id="email-error" className="field-error">{fieldErrors.email}</small>}</label>
          <label>رمز عبور<input name="password" type="password" autoComplete={mode === "login" ? "current-password" : "new-password"} dir="ltr" aria-invalid={Boolean(fieldErrors.password)} aria-describedby={fieldErrors.password ? "password-error" : undefined} className={fieldErrors.password ? "has-error" : ""} />{fieldErrors.password && <small id="password-error" className="field-error">{fieldErrors.password}</small>}</label>
          {mode === "register" && (
            <label>تکرار رمز عبور<input name="password_confirmation" type="password" autoComplete="new-password" dir="ltr" aria-invalid={Boolean(fieldErrors.password_confirmation)} aria-describedby={fieldErrors.password_confirmation ? "password-confirmation-error" : undefined} className={fieldErrors.password_confirmation ? "has-error" : ""} />{fieldErrors.password_confirmation && <small id="password-confirmation-error" className="field-error">{fieldErrors.password_confirmation}</small>}</label>
          )}
          {mode === "register" && <>
            <label className={`check-line legal-consent ${fieldErrors.accept_terms ? "consent-error" : ""}`}><input name="accept_terms" type="checkbox" aria-invalid={Boolean(fieldErrors.accept_terms)} aria-describedby={fieldErrors.accept_terms ? "terms-error" : undefined} /> <span><a href="/terms" target="_blank">شرایط استفاده</a> را خوانده‌ام و می‌پذیرم.{fieldErrors.accept_terms && <small id="terms-error" className="field-error">{fieldErrors.accept_terms}</small>}</span></label>
            <label className={`check-line legal-consent ${fieldErrors.accept_privacy ? "consent-error" : ""}`}><input name="accept_privacy" type="checkbox" aria-invalid={Boolean(fieldErrors.accept_privacy)} aria-describedby={fieldErrors.accept_privacy ? "privacy-error" : undefined} /> <span><a href="/privacy" target="_blank">سیاست حریم خصوصی</a> و پردازش داده‌های حساب را تأیید می‌کنم.{fieldErrors.accept_privacy && <small id="privacy-error" className="field-error">{fieldErrors.accept_privacy}</small>}</span></label>
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
