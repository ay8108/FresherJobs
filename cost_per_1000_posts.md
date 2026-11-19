# Gemini API Classification Cost Analysis (Per 1,000 Job Posts)

**Platform:** Laravel 12 Job Scraping & LLM Classification Engine (`Fresher-Jobs`)  
**AI Service Implementation:** `App\Services\GeminiService::classifyJobsBatch()`  
**Configured Model Identifier:** `gemini-flash-lite-latest` (`.env` setting `GEMINI_MODEL`, `config/services.php`)  
**API Model Resolution:** `models/gemini-flash-lite-latest` (Reported runtime model: `gemini-3.5-flash-lite`)  
**Batching Architecture:** **Application-Level Prompt Batching** (Synchronous `generateContent` passing a JSON array of 10 jobs per call; **NOT Google's asynchronous Batch API** `/v1beta/batches`)  
**Pricing Source:** Official Google AI for Developers Pricing ([https://ai.google.dev/pricing](https://ai.google.dev/pricing))  
**Document Updated:** November 18, 2026  

---

## 1. Executive Cost Summary

> [!IMPORTANT]
> **Token Count Disclosure:** Individual job rows in the database do not persist Google Cloud `usageMetadata` token counters. The token values below are **explicitly labelled as engineering estimates** calculated from actual character counts and hard limits enforced in `GeminiService` (e.g. descriptions truncated to 300 characters via `mb_substr(strip_tags($desc), 0, 300)`). A live single-word probe to the endpoint confirmed active `usageMetadata` returning `serviceTier: "standard"`.

| Metric | Measured / Estimated Value | Verification Notes |
| :--- | :---: | :--- |
| **Configured Model** | `gemini-flash-lite-latest` | Verified from `config/services.php` & `.env` |
| **API Runtime Version** | `gemini-3.5-flash-lite` | Verified from `modelVersion` property in live API response |
| **Batching Mechanism** | **Application-Level Prompt Batching** | 10 jobs concatenated into one prompt array; calls synchronous `generateContent` |
| **Asynchronous Batch API Used?** | **No** | Does not call `/v1beta/batches`; standard synchronous rates apply |
| **Estimated Input Tokens (per 1,000 jobs)** | **~137,000 tokens** *(Estimate)* | ~1,370 tokens/batch (450 system prompt + 920 for 10 trimmed jobs) across 100 batches |
| **Estimated Output Tokens (per 1,000 jobs)** | **~25,000 tokens** *(Estimate)* | ~250 tokens/batch (~25 tokens per structured JSON output object) across 100 batches |
| **Official Rate: Input Tokens** | **$0.30 / 1,000,000 tokens** | Standard Google AI Studio Pay-As-You-Go rate applicable to `gemini-3.5-flash-lite` |
| **Official Rate: Output Tokens** | **$2.50 / 1,000,000 tokens** | Standard Google AI Studio Pay-As-You-Go rate applicable to `gemini-3.5-flash-lite` |
| **Estimated Input Cost (1,000 jobs)** | **$0.041100 USD** | $\frac{137{,}000}{1{,}000{,}000} \times \$0.30$ |
| **Estimated Output Cost (1,000 jobs)** | **$0.062500 USD** | $\frac{25{,}000}{1{,}000{,}000} \times \$2.50$ |
| **Total Estimated API Cost per 1,000 Jobs** | **$0.103600 USD** (~ ₹8.60 INR) | **~$0.10 per 1,000 posts** |
| **Free Tier Cost** | **$0.00 USD** | When operated within Google AI Studio free tier limits (up to 15 RPM / 1,500 RPD) |

---

## 2. Estimated Token Breakdown (Per Batch of 10 Jobs)

### A. Input Tokens (Estimated)
1. **System & Formatting Prompt:**
   - Role enumeration (27 standard roles), critical city rules, experience constraints, and schema guidelines: **~450 tokens**.
2. **Job Data Payload (10 jobs per batch):**
   - Each job payload includes `id`, `title`, `company_name`, `location`, `employment_type`, and `description` (truncated via `mb_substr(strip_tags($description), 0, 300)`).
   - Average estimated tokens per trimmed job payload: **~92 tokens**.
   - 10 jobs per batch: **~920 tokens**.
3. **Total Estimated Input per Batch:** ~450 + ~920 = **~1,370 tokens**.
4. **Total Estimated Input for 1,000 Jobs (100 batches):** 100 × 1,370 = **~137,000 tokens**.

### B. Output Tokens (Estimated)
1. Output JSON structure returned per job:
   ```json
   {"id": 291, "role": "Digital Marketing Specialist", "city": "Mumbai", "experience_level": "Internship", "confidence": 0.95}
   ```
2. Size per classified item: ~110 characters = **~25 tokens per job**.
3. Output tokens per 10-job batch: **~250 tokens**.
4. **Total Estimated Output for 1,000 Jobs (100 batches):** 100 × 250 = **~25,000 tokens**.

---

## 3. Cost Calculation Formulas

$$\text{Input Cost} = \frac{137{,}000\text{ tokens}}{1{,}000{,}000} \times \$0.30 = \$0.041100\text{ USD}$$

$$\text{Output Cost} = \frac{25{,}000\text{ tokens}}{1{,}000{,}000} \times \$2.50 = \$0.062500\text{ USD}$$

$$\text{Total Cost per 1,000 Posts} = \$0.041100 + \$0.062500 = \mathbf{\$0.103600\text{ USD}} \quad (\approx ₹8.60\text{ INR})$$

### Monthly Projected Operating Cost (10,000 Posts/Month)
- **Monthly API Incurred Cost:** $0.1036 × 10 = **~$1.04 USD** (~ ₹86.00 INR / month).
- Application-level prompt batching (10 jobs per call) amortizes system prompt overhead across 10 jobs, reducing token consumption by ~80% compared to single-item requests.
