export type User = {
  id: number;
  name: string;
  email: string;
  email_verified: boolean;
  is_admin: boolean;
  profile_completed: boolean;
};

export type Category = {
  id: number;
  slug: string;
  name_fa: string;
  name_de: string;
};

export type Meta = {
  categories: Category[];
  cities: string[];
  german_levels: string[];
};

export type Opportunity = {
  id: number;
  slug: string;
  title_fa: string;
  title_de: string;
  employer_name: string;
  description_fa: string;
  description_de?: string | null;
  city: string;
  state?: string | null;
  training_type: "dual" | "school";
  start_date?: string | null;
  application_deadline?: string | null;
  monthly_salary_from?: number | null;
  monthly_salary_to?: number | null;
  required_german_level: string;
  education_requirement?: string | null;
  skills: string[];
  accepts_international: boolean;
  visa_support: "unknown" | "no" | "possible" | "yes";
  application_url: string;
  contact_email?: string | null;
  published_at?: string | null;
  category: Category;
  source?: { name: string; base_url: string } | null;
  match_score?: number | null;
  match_breakdown?: Record<string, number> | null;
  is_favorite: boolean;
};

export type PaginationMeta = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from?: number | null;
  to?: number | null;
};

export type OpportunityCollection = {
  data: Opportunity[];
  meta: PaginationMeta;
};

export type Profile = {
  phone: string | null;
  country: string | null;
  birth_date: string | null;
  german_level: string;
  education_level: string | null;
  education_title: string | null;
  skills: string[] | null;
  preferred_category_ids: number[] | null;
  preferred_cities: string[] | null;
  work_experience_years: number;
  relocation_ready: boolean;
  available_from: string | null;
};

export type Resume = {
  id: number;
  original_name: string;
  mime_type: string;
  size_bytes: number;
  status: string;
  is_primary: boolean;
  created_at: string;
};

export type GermanCv = {
  headline: string | null;
  summary: string | null;
  contact: { email?: string; phone?: string; city?: string } | null;
  experiences: Array<{ title: string; company?: string; period?: string; description?: string }> | null;
  education: Array<{ title: string; school?: string; period?: string }> | null;
  skills: string[] | null;
  languages: Array<{ name: string; level?: string }> | null;
  certificates: string[] | null;
  cover_letter?: string | null;
};

export type ApplicationStatus = "opened" | "applied" | "reviewing" | "interview" | "offer" | "rejected" | "withdrawn";

export type Application = {
  id: number;
  status: ApplicationStatus;
  applied_at: string | null;
  interview_at: string | null;
  notes: string | null;
  created_at: string;
  opportunity: Opportunity;
};

export type ApplicationCollection = {
  data: Application[];
  summary: { total: number; active: number; interviews: number };
};

export type ApiErrorPayload = {
  message?: string;
  errors?: Record<string, string[]>;
};
