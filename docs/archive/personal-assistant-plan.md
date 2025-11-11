# Personal Assistant Integration Plan

## Vision
Turn the budget-control app into an ultra-smart assistant that ingests the user’s full bank-history JSON and delivers proactive budgeting, crisis planning, and income-growth coaching tailored to a Czech IT technician using AI for coding.

---

## Month-by-Month Roadmap

### Month 1 – Data Intake & Context
- Normalize the bank-history JSON into accounts, transactions, merchants, cohorts, and cash-flow events with deterministic ingestion scripts and audit logs.
- Build categorization/tagging (rule-based first, ML-ready hooks later) plus aggregate tables for recurring income, expenses, and balances.
- Add a landing-dashboard intake form capturing household size, location, rent/mortgage, commute, job role, salary, remote willingness, relocation targets, financial goals priority, risk tolerance, urgency slider, AI skill usage, and language proficiency.
- Surface initial insight cards (income vs. expenses, recurring payments, low-balance alerts) backed by the new aggregates.
- Ship automated tests for parsers and seeding scripts so every new JSON drop is reproducible.

### Month 2 – Insight Engine & Baseline Advisor
- Create analytics workers for budget health, savings runway, debt tracking, cash-flow forecasts, and subscription watchdog.
- Implement an MCP financial adapter that summarizes stats + personal context into structured payloads for LLM calls.
- Define/implement the first LLM prompt set (Budget Analyzer, Cash-Flow Forecaster, Savings/Debt Coach) with caching, rate limits, and redaction.
- Update dashboard widgets to show LLM insight panels, “Regenerate with latest data” CTA, and a timeline of nudges.
- Add a crisis-mode toggle that tightens thresholds, highlights emergency actions, and escalates notifications.

### Month 3 – Smart Coach & Income Growth
- Extend the data store with goal tracking (emergency fund, debt payoff, investment) and scenario planning APIs.
- Build the career/income module collecting skills, preferred regions, job flexibility, and certifications from the intake form.
- Integrate Career Uplift and Income Strategy prompts to highlight global salary bands, visa/contract notes, remote/hybrid opportunities, and AI-leveraged side gigs.
- Add an “Opportunities” dashboard section with curated links, learning paths, and trackable tasks derived from LLM output.
- Enable contextual actions in each widget (e.g., “Optimize transport costs”) that fire task-specific prompts using filtered datasets.
- Launch notification center + weekly digest emails bundling auto insights and advisor commentary.

### Month 4 – Automation & Scaling
- Record recommendation history, allow feedback (thumbs up/down + comments), and use it to retrain heuristics or fine-tune prompt parameters.
- Add proactive automations: auto-generated budgets, subscription cancel checklists, debt refinance reminders, Czech hardship/benefit lookups, mental health/community resources.
- Pull lightweight job-market/RSS/API feeds filtered for AI-enabled technician roles across EU/US markets.
- Harden security (PII encryption, secrets rotation, access auditing) and optimize performance for large JSON imports.
- Run usability sessions + A/B tests before GA release.

---

## Recommended Prompt Templates

### 1. Budget Analyzer
```
You are an MCP-aligned financial expert. Given USER_CONTEXT and FINANCIAL_STATS (income streams, expense categories, cash buffer, upcoming obligations), return:
1. Top three spending leaks with CZK amounts.
2. Three actionable monthly savings moves, each with estimated CZK impact and difficulty.
3. Recommended emergency fund target (CZK) and months of runway needed.
Respond with concise markdown sections.
```

### 2. Cash-Flow & Debt Planner
```
Act as a Czech personal finance strategist. Using TRANSACTION_TIMELINES, RECURRING_BILLS, and DEBT_LIST (balance, rate, min payment), produce:
- 90-day cash-flow projection with best/worst case balances.
- Optimal debt payoff order (avalanche) with monthly payment schedule and interest saved.
- Possible refinancing or government-support programs available in Czech Republic.
Include immediate red flags if liquidity falls below one month of expenses.
```

### 3. Career Uplift Advisor
```
You advise a Czech IT technician skilled in AI-assisted coding, open to remote or partial relocation. From USER_SKILLS, TARGET_REGIONS, and MARKET_DATA, list the five highest-demand roles:
- Role title + core responsibilities
- Typical salary bands (CZK/EUR/USD)
- Visa/contract considerations and remote feasibility
- Fastest upskilling or certification steps (<=6 weeks)
Finish with a 30-day job-search sprint plan.
```

### 4. Income Strategy & Side Gigs
```
Recommend side-income streams leveraging coding + AI automation skills. For each idea provide:
- Description and why demand exists
- Platforms/communities to find work (Upwork, EU marketplaces, Czech-specific hubs)
- Expected hourly/weekly pay range
- First-week action checklist
Tailor suggestions to someone balancing family duties and needing partial work-from-home flexibility.
```

### 5. Resilience Roadmap
```
Create a 30-60-90 day resilience plan combining budgeting, income, debt relief, and wellbeing. Inputs: BUDGET_STATUS, GOALS, CRISIS_URGENCY.
Output sections:
- Day 0-30: cash preservation + emergency funding steps
- Day 31-60: career/income acceleration
- Day 61-90: longer-term investments, insurance, mental health routines
Reference Czech resources (benefits offices, counseling lines) where helpful.
```

---

## How to Use This Plan with LLMs
1. Summarize the latest bank-history ingestion stats plus user intake responses.
2. Choose the prompt template that fits the current widget or user request.
3. Provide anonymized context objects (USER_CONTEXT, FINANCIAL_STATS, etc.) and paste the full template.
4. Store responses alongside metadata (timestamp, dataset hash, prompt version) for traceability.
5. Collect user feedback on each recommendation to refine future prompts.
