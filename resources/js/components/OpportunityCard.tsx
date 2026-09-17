import { useState } from "react";
import {
  BadgeEuro,
  Bookmark,
  Building2,
  CalendarDays,
  ChevronDown,
  ExternalLink,
  Globe2,
  HeartHandshake,
  MapPin,
  Flag,
} from "lucide-react";
import type { Opportunity } from "../types";

type Props = {
  opportunity: Opportunity;
  onFavorite: (opportunity: Opportunity) => void;
  onApply: (opportunity: Opportunity) => void;
  onReport: (opportunity: Opportunity) => void;
};

const dateFormatter = new Intl.DateTimeFormat("fa-IR", {
  year: "numeric",
  month: "long",
  day: "numeric",
});

const numberFormatter = new Intl.NumberFormat("fa-IR");

function dateLabel(value?: string | null): string {
  if (!value) return "توافقی";
  return dateFormatter.format(new Date(`${value}T12:00:00`));
}

function salaryLabel(opportunity: Opportunity): string {
  if (!opportunity.monthly_salary_from) return "اعلام نشده";
  const from = numberFormatter.format(opportunity.monthly_salary_from);
  const to = opportunity.monthly_salary_to
    ? ` تا ${numberFormatter.format(opportunity.monthly_salary_to)}`
    : "";
  return `${from}${to} یورو`;
}

function visaLabel(value: Opportunity["visa_support"]): string {
  return {
    yes: "حمایت ویزا",
    possible: "حمایت ویزا ممکن است",
    no: "بدون حمایت ویزا",
    unknown: "وضعیت ویزا نامشخص",
  }[value];
}

export default function OpportunityCard({ opportunity, onFavorite, onApply, onReport }: Props) {
  const [expanded, setExpanded] = useState(false);
  const hasScore = typeof opportunity.match_score === "number";

  return (
    <article className="opportunity-card">
      <div className="card-accent" />
      <div className="card-topline">
        <span className="category-pill">{opportunity.category.name_fa}</span>
        <button
          type="button"
          className={`icon-button ${opportunity.is_favorite ? "is-active" : ""}`}
          onClick={() => onFavorite(opportunity)}
          aria-label={opportunity.is_favorite ? "حذف از ذخیره‌شده‌ها" : "ذخیره فرصت"}
          title={opportunity.is_favorite ? "حذف از ذخیره‌شده‌ها" : "ذخیره فرصت"}
        >
          <Bookmark size={20} fill={opportunity.is_favorite ? "currentColor" : "none"} />
        </button>
      </div>

      <h3>{opportunity.title_fa}</h3>
      <p className="german-title" dir="ltr">{opportunity.title_de}</p>

      <div className="employer-row">
        <Building2 size={17} />
        <span>{opportunity.employer_name}</span>
      </div>

      <div className="facts-grid">
        <span><MapPin size={16} /> {opportunity.city}</span>
        <span><BadgeEuro size={16} /> {salaryLabel(opportunity)}</span>
        <span><CalendarDays size={16} /> شروع: {dateLabel(opportunity.start_date)}</span>
        <span><Globe2 size={16} /> زبان {opportunity.required_german_level}</span>
      </div>

      <div className="card-badges">
        {opportunity.accepts_international && (
          <span className="soft-badge success"><Globe2 size={14} /> پذیرش بین‌المللی</span>
        )}
        <span className="soft-badge"><HeartHandshake size={14} /> {visaLabel(opportunity.visa_support)}</span>
      </div>

      <div className={`card-details ${expanded ? "is-open" : ""}`}>
        <p>{opportunity.description_fa}</p>
        <div className="skill-list">
          {opportunity.skills.map((skill) => <span key={skill}>{skill}</span>)}
        </div>
        <p className="deadline">مهلت ارسال درخواست: {dateLabel(opportunity.application_deadline)}</p>
        {opportunity.source && (
          <p className="source-line">منبع: <a href={opportunity.source.base_url} target="_blank" rel="noreferrer">{opportunity.source.name}</a></p>
        )}
      </div>

      <div className="card-actions">
        <button type="button" className="details-button" onClick={() => setExpanded((value) => !value)}>
          {expanded ? "بستن جزئیات" : "مشاهده جزئیات"}
          <ChevronDown size={17} className={expanded ? "rotate" : ""} />
        </button>
        <button type="button" className="apply-button" onClick={() => onApply(opportunity)}>
          درخواست <ExternalLink size={16} />
        </button>
      </div>

      <button type="button" className="report-link" onClick={() => onReport(opportunity)}>
        <Flag size={14} /> گزارش مشکل در آگهی
      </button>

      <div className={`match-strip ${hasScore ? "has-score" : ""}`}>
        {hasScore ? (
          <>
            <strong>{numberFormatter.format(opportunity.match_score!)}٪ تطابق</strong>
            <span>براساس پروفایل شما</span>
          </>
        ) : (
          <>
            <strong>امتیاز تطابق هوشمند</strong>
            <span>با تکمیل پروفایل فعال می‌شود</span>
          </>
        )}
      </div>
    </article>
  );
}
