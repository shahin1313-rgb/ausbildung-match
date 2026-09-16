import { useEffect, useMemo, useState } from "react";
import { CheckCircle2, ChevronLeft, ChevronRight, Clock3, Mic, RotateCcw } from "lucide-react";

const questions = [
  "لطفاً خودتان را کوتاه معرفی کنید.",
  "چرا این رشته آوسبیلدونگ را انتخاب کرده‌اید؟",
  "چرا می‌خواهید دوره آوسبیلدونگ را در آلمان شروع کنید؟",
  "مهم‌ترین نقطه قوت شما برای این موقعیت چیست؟",
  "از یک موقعیت دشوار و روشی که آن را حل کردید بگویید.",
  "چگونه با کار تیمی و دریافت بازخورد برخورد می‌کنید؟",
  "برای تقویت زبان آلمانی خود چه برنامه‌ای دارید؟",
  "چه پرسشی از کارفرما یا مرکز آموزشی دارید؟",
];

const storageKey = "ausbildung-match-interview-answers";

export default function InterviewPractice() {
  const [index, setIndex] = useState(0);
  const [seconds, setSeconds] = useState(120);
  const [finished, setFinished] = useState(false);
  const [answers, setAnswers] = useState<string[]>(() => {
    try { return JSON.parse(localStorage.getItem(storageKey) || "[]"); } catch { return []; }
  });

  useEffect(() => { localStorage.setItem(storageKey, JSON.stringify(answers)); }, [answers]);
  useEffect(() => {
    if (finished || seconds <= 0) return;
    const timer = window.setInterval(() => setSeconds((current) => current - 1), 1000);
    return () => window.clearInterval(timer);
  }, [finished, index, seconds]);

  const answered = useMemo(() => answers.filter((answer) => answer?.trim()).length, [answers]);
  const time = `${String(Math.floor(seconds / 60)).padStart(2, "0")}:${String(seconds % 60).padStart(2, "0")}`;
  const move = (next: number) => { setIndex(Math.max(0, Math.min(questions.length - 1, next))); setSeconds(120); };
  const reset = () => { setAnswers([]); setIndex(0); setSeconds(120); setFinished(false); localStorage.removeItem(storageKey); };

  if (finished) return <section className="page-surface container interview-card"><CheckCircle2 className="practice-complete"/><h2>تمرین پایان یافت</h2><p>به {answered.toLocaleString("fa-IR")} پرسش از {questions.length.toLocaleString("fa-IR")} پرسش پاسخ داده‌اید. پاسخ‌ها در همین مرورگر ذخیره شده‌اند و می‌توانید برای بهترشدن دوباره مرورشان کنید.</p><button className="primary-button wide-action" onClick={() => setFinished(false)}>مرور پاسخ‌ها</button><button className="secondary-button wide-action" onClick={reset}><RotateCcw/> شروع دوباره</button></section>;

  return <section className="page-surface container interview-card"><div className="interview-topic"><span className="app-icon blue"><Mic/></span><div><h2>تمرین مصاحبه آوسبیلدونگ</h2><p>پاسخ خود را بنویسید یا با صدای بلند تمرین کنید.</p></div></div><div className="question-progress"><span>سؤال {(index + 1).toLocaleString("fa-IR")} از {questions.length.toLocaleString("fa-IR")}</span><i><b style={{ width: `${((index + 1) / questions.length) * 100}%` }}/></i></div><h2>{questions[index]}</h2><textarea className="interview-answer" rows={8} value={answers[index] || ""} onChange={(event) => setAnswers((current) => { const next = [...current]; next[index] = event.target.value; return next; })} placeholder="نکات کلیدی پاسخ خود را اینجا بنویسید…"/><span className={`timer ${seconds === 0 ? "expired" : ""}`}><Clock3/> {seconds === 0 ? "زمان پیشنهادی پایان یافت؛ همچنان می‌توانید پاسخ را کامل کنید." : time}</span><div className="practice-actions"><button className="secondary-button" disabled={index === 0} onClick={() => move(index - 1)}><ChevronRight/> قبلی</button>{index < questions.length - 1 ? <button className="primary-button" onClick={() => move(index + 1)}>بعدی <ChevronLeft/></button> : <button className="primary-button" onClick={() => setFinished(true)}>پایان تمرین</button>}</div></section>;
}
