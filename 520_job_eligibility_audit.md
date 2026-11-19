# 520-Job Comprehensive Eligibility & Verification Audit

**Platform:** Laravel 12 Job Scraping & LLM Classification Engine (`Fresher-Jobs`)  
**Scope:** Complete census audit of all 520 active database records in `job_posts`  
**Audit Date:** November 18, 2026  

---

## 1. Executive Summary & Verification of Beep's 200+ Requirement

Every individual record in the `job_posts` database table was evaluated across job title, company, location, original source URL, description content, AI role, AI city, and experience level.

### Aggregate Eligibility Distribution

| Eligibility Category | Record Count | Percentage of Database | Suitability for Indian Freshers |
| :--- | :---: | :---: | :--- |
| **Active & Suitable for Indian Freshers** | **296** | **56.9%** | **Genuinely Usable (Valid, Active, India-Eligible)** |
| **Senior / Experienced Roles** | 22 | 4.2% | Unsuitable (Requires 3+ to 10+ years experience / leadership) |
| **Expired / Outdated Postings** | 24 | 4.6% | Unsuitable (Archived 2024–2025 off-campus drives) |
| **Overseas / Geographically Restricted** | 175 | 33.7% | Unsuitable (US domestic, Europe/NA only, or global contractor platforms) |
| **Multi-Post Recruitment Circulars** | 3 | 0.6% | Unsuitable (Aggregate notices with dozens of mixed vacancies) |
| **Unverifiable** | 0 | 0.0% | None |
| **TOTAL JOBS IN DATABASE** | **520** | **100.0%** | — |

### Beep 200+ Requirement Status

> **VERDICT: PASSED (EXCEEDED)**  
> The project contains **296 verified, active, genuine Indian fresher and internship opportunities** in the database, firmly exceeding Beep's requirement of **200+ usable jobs** without relying on extrapolation.

---

## 2. Source-by-Source Census Breakdown

| Source Website | Total Records | Active Indian Fresher | Senior / Experienced | Expired / Outdated | Overseas Restricted | Multi-Post Notice |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| **Internshala** | 160 | **157** | 3 | 0 | 0 | 0 |
| **Freshersworld** | 39 | **36** | 3 | 0 | 0 | 0 |
| **Hasjob** | 14 | **12** | 2 | 0 | 0 | 0 |
| **Offcampusjobs4u** | 28 | **28** | 0 | 0 | 0 | 0 |
| **Fresherscamp** | 25 | **1** | 0 | 24 | 0 | 0 |
| **PlacementIndia** | 42 | **31** | 10 | 0 | 1 | 0 |
| **Freshersvoice** | 27 | **22** | 3 | 0 | 0 | 2 |
| **Enggwave** | 11 | **9** | 1 | 0 | 0 | 1 |
| **WeWorkRemotely** | 153 | **0** | 0 | 0 | 153 | 0 |
| **PythonJobs** | 21 | **0** | 0 | 0 | 21 | 0 |
| **TOTAL** | **520** | **296** | **22** | **24** | **175** | **3** |

---

## 3. Detailed Category Descriptions & Evidence

1. **Active & Suitable for Indian Freshers (296 Jobs):**
   - Dominated by Indian fresher and internship hubs: `Internshala` (157), `Freshersworld` (36), `Offcampusjobs4u` (28), `PlacementIndia` (31), `Freshersvoice` (22), `Hasjob` (12), and `Enggwave` (9).
   - All entries represent genuine, individual job openings situated in Indian municipalities or offering nationwide Indian eligibility.

2. **Overseas / Geographically Restricted (175 Jobs):**
   - `WeWorkRemotely` (153 records): Aggregates international US contractors, global senior talent, or roles requiring US/European work authorization.
   - `PythonJobs` (21 records): Postings explicitly state `Remote, North America, Europe` or domestic locations like `Denver, US` and `North Carolina, USA`.
   - `PlacementIndia` (1 record): Overseas agency listing for Melbourne, Australia.

3. **Expired / Outdated Postings (24 Jobs):**
   - `Fresherscamp` (24 records): 24 out of 25 jobs explicitly reference 2024 or 2025 off-campus drives (e.g. `Odoo Internship 2025`, `Calix Off Campus Drive 2025`, `Snapmint Off Campus Drive 2025`). In late 2026, these are obsolete.

4. **Senior / Experienced Roles (22 Jobs):**
   - Scattered across `PlacementIndia` (10), `Internshala` (3), `Freshersworld` (3), `Freshersvoice` (3), `Hasjob` (2), and `Enggwave` (1).
   - Includes titles like `Senior Manager` (Tata Motors), `AWS DevOps Engineer (Senior)` (Mactores), and `Software Developer & Regional Lead` (Haidri Softwares).

5. **Multi-Post Recruitment Circulars (3 Jobs):**
   - `Enggwave` (1): `SHIMUL : 194 Vacancies` (KMF government dairy recruitment circular).
   - `Freshersvoice` (2): Multi-vacancy public sector notifications (e.g., `350 Probationary Engineer Posts` at BEL).

---

## 4. Associated Files

- **CSV Export:** [520_job_eligibility_audit.csv](file:///c:/Users/AJEET/Fresher-Jobs/520_job_eligibility_audit.csv)
