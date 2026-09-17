import { FormEvent, useState } from "react";
import { AlertTriangle, CheckCircle2, Flag, X } from "lucide-react";
import { api, ApiError } from "../api";
import type { Opportunity, User } from "../types";

type Props = {
  opportunity: Opportunity;
  user: User | null;
  onClose: () => void;
};

const reasons = [
  { value: "scam", label: "کلاهبرداری یا آگهی مشکوک" },
  { value: "broken_link", label: "لینک درخواست خراب است" },
  { value: "incorrect_info", label: "اطلاعات آگهی اشتباه است" },
  { value: "expired", label: "فرصت منقضی شده است" },
  { value: "other", label: "مشکل دیگری وجود دارد" },
] as const;

export default function ReportOpportunityDialog({ opportunity, user, onClose }: Props) {
  const [reason, setReason] = useState("");
  const [details, setDetails] = useState("");
  const [email, setEmail] = useState(user?.email || "");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  async function submit(event: FormEvent) {
    event.preventDefault();
    setSubmitting(true);
    setError("");

    try {
      const response = await api<{ message: string }>(`/opportunities/${opportunity.slug}/reports`, {
        method: "POST",
        body: JSON.stringify({
          reason,
          details: details.trim() || null,
          reporter_email: user ? undefined : email.trim(),
          website: "",
        }),
      });
      setSuccess(response.message);
    } catch (exception) {
      if (exception instanceof ApiError) {
        const firstValidationError = Object.values(exception.errors).flat()[0];
        setError(firstValidationError || exception.message);
      } else {
        setError("ثبت گزارش ممکن نشد. لطفاً دوباره تلاش کنید.");
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="modal-backdrop report-backdrop" role="presentation" onMouseDown={(event) => {
      if (event.target === event.currentTarget) onClose();
    }}>
      <section className="report-dialog" role="dialog" aria-modal="true" aria-labelledby="report-title">
        <button type="button" className="dialog-close" onClick={onClose} aria-label="بستن">
          <X size={19} />
        </button>

        {success ? (
          <div className="report-success" role="status">
            <CheckCircle2 size={52} />
            <h2 id="report-title">گزارش ثبت شد</h2>
            <p>{success}</p>
            <button type="button" className="primary-button" onClick={onClose}>بستن</button>
          </div>
        ) : (
          <form onSubmit={submit}>
            <div className="report-heading">
              <span><Flag size={20} /></span>
              <div>
                <h2 id="report-title">گزارش آگهی نامعتبر</h2>
                <p>{opportunity.title_fa} — {opportunity.employer_name}</p>
              </div>
            </div>

            <p className="report-guidance">
              مشکل را دقیق انتخاب کنید. گزارش شما عمومی نمی‌شود و فقط برای بررسی مدیران استفاده خواهد شد.
            </p>

            <fieldset className="report-reasons">
              <legend>چه مشکلی وجود دارد؟</legend>
              {reasons.map((item) => (
                <label key={item.value} className={reason === item.value ? "selected" : ""}>
                  <input
                    type="radio"
                    name="reason"
                    value={item.value}
                    checked={reason === item.value}
                    onChange={() => setReason(item.value)}
                    required
                  />
                  <span>{item.label}</span>
                </label>
              ))}
            </fieldset>

            {!user && (
              <label className="report-field">
                ایمیل برای پیگیری
                <input
                  type="email"
                  value={email}
                  onChange={(event) => setEmail(event.target.value)}
                  placeholder="name@example.com"
                  dir="ltr"
                  required
                />
              </label>
            )}

            <label className="report-field">
              توضیحات {['scam', 'incorrect_info', 'other'].includes(reason) ? '(الزامی)' : '(اختیاری)'}
              <textarea
                value={details}
                onChange={(event) => setDetails(event.target.value)}
                placeholder="برای بررسی سریع‌تر، مشکل را با جزئیات بنویسید…"
                minLength={10}
                maxLength={2000}
                required={['scam', 'incorrect_info', 'other'].includes(reason)}
                rows={4}
              />
              <small>{new Intl.NumberFormat("fa-IR").format(details.length)} از ۲۰۰۰ نویسه</small>
            </label>

            {error && <div className="report-error" role="alert"><AlertTriangle size={17} /> {error}</div>}

            <div className="report-actions">
              <button type="button" className="secondary-button" onClick={onClose}>انصراف</button>
              <button type="submit" className="danger-button" disabled={submitting || !reason}>
                <Flag size={17} /> {submitting ? "در حال ثبت…" : "ثبت گزارش"}
              </button>
            </div>
          </form>
        )}
      </section>
    </div>
  );
}
