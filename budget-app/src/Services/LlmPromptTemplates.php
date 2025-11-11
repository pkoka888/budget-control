<?php
namespace BudgetApp\Services;

class LlmPromptTemplates {
    /**
     * Budget Analyzer prompt template
     */
    public static function getBudgetAnalyzerPrompt(array $context): string {
        $stats = $context['financial_stats'];
        $budgetHealth = $stats['budget_health'];
        $topCategories = $stats['top_expense_categories'];

        $categoryList = '';
        foreach (array_slice($topCategories, 0, 3) as $cat) {
            $categoryList .= "- {$cat['name']}: {$cat['total']} CZK ({$cat['percentage']}%)" . PHP_EOL;
        }

        return <<<PROMPT
You are an MCP-aligned financial expert. Given USER_CONTEXT and FINANCIAL_STATS, return:

1. Top three spending leaks with CZK amounts and specific reduction strategies.
2. Three actionable monthly savings moves, each with estimated CZK impact and difficulty level (Easy/Medium/Hard).
3. Recommended emergency fund target (CZK) and months of runway needed based on current expenses.

**USER CONTEXT:**
- Monthly Income: {$budgetHealth['savings_rate']}% savings rate
- Budget Compliance: {$budgetHealth['budget_compliance']}%
- Categories over budget: {$budgetHealth['over_budget_categories']}

**FINANCIAL STATS:**
- Monthly Expenses: {$stats['budget_status']['expenses']} CZK
- Monthly Income: {$stats['budget_status']['income']} CZK
- Net Income: {$stats['budget_status']['net_income']} CZK

**TOP SPENDING CATEGORIES:**
{$categoryList}

Respond with concise markdown sections. Focus on actionable, specific recommendations for a Czech IT technician.
PROMPT;
    }

    /**
     * Cash-Flow Forecaster prompt template
     */
    public static function getCashFlowForecasterPrompt(array $context): string {
        $stats = $context['financial_stats'];
        $timelines = $context['transaction_timelines'] ?? [];
        $recurring = $context['recurring_bills'] ?? [];
        $debt = $context['debt_list'] ?? [];

        $recentPattern = '';
        if (!empty($timelines)) {
            $lastWeek = array_slice($timelines, -7);
            $avgDaily = array_sum(array_column($lastWeek, 'net')) / count($lastWeek);
            $recentPattern = "Recent 7-day average net cash flow: " . round($avgDaily, 2) . " CZK/day";
        }

        $recurringTotal = $recurring['monthly_total'] ?? 0;
        $debtTotal = $debt['total_minimum_payment'] ?? 0;

        return <<<PROMPT
Act as a Czech personal finance strategist. Using TRANSACTION_TIMELINES, RECURRING_BILLS, and DEBT_LIST, produce:

**90-DAY CASH-FLOW PROJECTION:**
- Best case scenario (10% income increase, 5% expense reduction)
- Worst case scenario (10% income decrease, 5% expense increase)
- Most likely scenario (current trends continue)
- Include monthly balances and key risk points

**OPTIMAL DEBT PAYOFF ORDER:**
- List debts by priority (avalanche method: highest interest first)
- Monthly payment schedule with interest saved
- Timeline to debt freedom

**IMMEDIATE RED FLAGS:**
- Identify any liquidity risks within 90 days
- Minimum cash buffer recommendations

**CONTEXT:**
- {$recentPattern}
- Monthly recurring obligations: {$recurringTotal} CZK
- Monthly debt payments: {$debtTotal} CZK
- Current emergency runway: {$stats['savings_runway']['runway_months']} months

Include specific Czech financial resources and government support programs where relevant.
PROMPT;
    }

    /**
     * Savings/Debt Coach prompt template
     */
    public static function getSavingsDebtCoachPrompt(array $context): string {
        $stats = $context['financial_stats'];
        $debt = $context['debt_list'];
        $runway = $stats['savings_runway'];

        $debtDetails = '';
        foreach (($debt['debts'] ?? []) as $d) {
            $debtDetails .= "- {$d['name']}: {$d['balance']} CZK ({$d['type']})" . PHP_EOL;
        }

        return <<<PROMPT
You are a specialized Savings and Debt Coach for Czech IT professionals. Analyze the financial situation and provide:

**DEBT OPTIMIZATION STRATEGY:**
1. Prioritized payoff order (avalanche vs snowball method recommendation)
2. Monthly payment plan with specific amounts
3. Interest savings calculations
4. Refinancing opportunities in Czech Republic

**SAVINGS ACCELERATION PLAN:**
1. Emergency fund building strategy (target: {$runway['emergency_fund_target']} CZK)
2. High-yield savings options available in CZ
3. Automated savings techniques
4. Side income opportunities for IT professionals

**CURRENT SITUATION:**
- Total Debt: {$debt['total_debt']} CZK
- Emergency Fund: {$runway['emergency_fund_current']} CZK
- Monthly Expenses: {$runway['monthly_expenses']} CZK
- Current Runway: {$runway['runway_months']} months

**DEBT DETAILS:**
{$debtDetails}

Provide specific, actionable steps with Czech financial products and local bank recommendations.
PROMPT;
    }

    /**
     * Career Uplift Advisor prompt template
     */
    public static function getCareerUpliftAdvisorPrompt(array $context): string {
        $skills = $context['user_skills'];
        $market = $context['market_data'];

        return <<<PROMPT
You advise a Czech IT technician skilled in AI-assisted coding, open to remote or partial relocation. From USER_SKILLS, TARGET_REGIONS, and MARKET_DATA, list the five highest-demand roles:

**TOP 5 HIGH-DEMAND ROLES:**
For each role provide:
- Core responsibilities and required skills
- Typical salary bands (CZK/EUR/USD) with regional variations
- Visa/contract considerations and remote feasibility
- Fastest upskilling/certification steps (≤6 weeks)
- Current market demand indicators

**USER PROFILE:**
- Skills: {$skills['skills']}
- Current Role: {$skills['current_role']}
- Target Regions: {$skills['target_regions']}
- Relocation Willingness: {$skills['relocation_willingness']}
- Remote Work Preference: {$skills['remote_work_preference']}

**MARKET CONTEXT:**
- High-demand roles: AI Engineer, Full Stack Developer, DevOps Engineer, Data Scientist, Cloud Architect
- Czech salary ranges: Junior 800K-1.2M CZK, Mid 1.2M-1.8M CZK, Senior 1.8M-2.5M CZK
- EU ranges: Junior €45K-65K, Mid €65K-85K, Senior €85K-120K

**30-DAY JOB SEARCH SPRINT:**
Provide a structured action plan with daily/weekly milestones, resume optimization tips, and networking strategies specific to Czech IT market.

Finish with relocation incentives and work permit guidance for target regions.
PROMPT;
    }

    /**
     * Income Strategy prompt template
     */
    public static function getIncomeStrategyPrompt(array $context): string {
        $skills = $context['user_skills'];

        return <<<PROMPT
Recommend side-income streams leveraging coding + AI automation skills. For each idea provide:

**SIDE INCOME OPPORTUNITIES:**
1. Description and market demand explanation
2. Required skills and time investment
3. Platforms/communities to find work (Upwork, EU marketplaces, Czech-specific hubs)
4. Expected hourly/weekly/monthly pay range
5. First-week action checklist
6. Scaling potential and long-term viability

**TARGET PROFILE:**
- Skills: {$skills['skills']} (AI-assisted coding focus)
- Current Role: {$skills['current_role']}
- Time Availability: Family-friendly, partial work-from-home balance
- Location: Czech Republic with EU access

**PLATFORM RECOMMENDATIONS:**
- Upwork/Fiverr for freelance coding
- Czech platforms: Czechitas, StartupJobs, JobDNES
- EU marketplaces: Toptal, Gigster, Lemon.io
- AI-specific: Replit, GitHub Sponsors, Patreon

**INCOME PROJECTIONS:**
- Freelance coding: 500-2000 CZK/hour
- AI tool development: 1000-5000 CZK/project
- Consulting: 2000-8000 CZK/hour
- Content creation: 500-2000 CZK/month per course/tutorial

Tailor suggestions to someone balancing family duties with technical work. Include passive income options using automation.
PROMPT;
    }

    /**
     * Resilience Roadmap prompt template
     */
    public static function getResilienceRoadmapPrompt(array $context): string {
        $budget = $context['budget_status'];
        $goals = $context['goals_and_urgency'];
        $urgency = $goals['crisis_urgency'];

        $goalsList = '';
        foreach (($goals['goals'] ?? []) as $goal) {
            $goalsList .= "- {$goal['name']}: {$goal['current_amount']}/{$goal['target_amount']} CZK ({$goal['goal_type']})" . PHP_EOL;
        }

        return <<<PROMPT
Create a 30-60-90 day resilience plan combining budgeting, income, debt relief, and wellbeing. Inputs: BUDGET_STATUS, GOALS, CRISIS_URGENCY.

**DAY 0-30: CASH PRESERVATION + EMERGENCY FUNDING**
- Immediate expense reduction measures
- Emergency fund building acceleration
- Cash flow protection strategies
- Czech government support programs check

**DAY 31-60: CAREER/INCOME ACCELERATION**
- Skill development and certification targets
- Job market positioning improvements
- Side income activation plan
- Networking and opportunity development

**DAY 61-90: LONG-TERM INVESTMENTS, INSURANCE, MENTAL HEALTH**
- Investment strategy development
- Insurance coverage optimization
- Mental health and wellbeing routines
- Long-term financial planning foundation

**CURRENT CONTEXT:**
- Crisis Urgency: {$urgency}
- Financial Health Score: {$goals['financial_health_score']}%
- Emergency Runway: {$goals['emergency_runway_months']} months
- Monthly Net Income: {$budget['net_income']} CZK

**ACTIVE GOALS:**
{$goalsList}

**CZECH-SPECIFIC RESOURCES:**
- Reference government hardship programs (COVID support, unemployment benefits)
- Local counseling services and financial advisors
- Community resources for IT professionals
- Banking products with hardship provisions

Provide specific, actionable steps with timeframes and success metrics. Include mental health considerations and work-life balance.
PROMPT;
    }

    /**
     * Crisis Mode prompt template (enhanced thresholds)
     */
    public static function getCrisisModePrompt(array $context): string {
        $stats = $context['financial_stats'];
        $runway = $stats['savings_runway'];
        $urgency = $context['goals_and_urgency']['crisis_urgency'];

        return <<<PROMPT
**CRISIS MODE ACTIVATED** - {$urgency} urgency level

**IMMEDIATE ACTIONS REQUIRED:**
1. **Cash Flow Emergency Measures**
   - Suspend all non-essential subscriptions immediately
   - Negotiate payment plans with creditors
   - Access emergency credit if runway < 1 month

2. **Expense Reduction Protocol**
   - Cut discretionary spending by 50% minimum
   - Review and cancel recurring payments
   - Implement strict budget controls

3. **Income Acceleration**
   - Activate all available side income streams
   - Update resume and job applications
   - Network aggressively for opportunities

**CRISIS METRICS:**
- Emergency Runway: {$runway['runway_months']} months (TARGET: 3+ months)
- Current Emergency Fund: {$runway['emergency_fund_current']} CZK
- Required Buffer: {$runway['emergency_fund_target']} CZK

**CZECH CRISIS RESOURCES:**
- Employment office registration (Úřad práce)
- Social benefits application (Státní sociální podpora)
- Debt counseling services (Czech Banking Association)
- Mental health support (Linka důvěry: 116 123)

**MONITORING PROTOCOL:**
- Daily cash position checks
- Weekly expense reviews
- Bi-weekly income opportunity assessments
- Immediate notification triggers for runway < 2 weeks

Execute this plan immediately. Provide hourly action items for the first 24 hours.
PROMPT;
    }

    /**
     * Get prompt by type
     */
    public static function getPrompt(string $type, array $context): string {
        return match($type) {
            'budget_analyzer' => self::getBudgetAnalyzerPrompt($context),
            'cash_flow_forecaster' => self::getCashFlowForecasterPrompt($context),
            'savings_debt_coach' => self::getSavingsDebtCoachPrompt($context),
            'career_uplift_advisor' => self::getCareerUpliftAdvisorPrompt($context),
            'income_strategy' => self::getIncomeStrategyPrompt($context),
            'resilience_roadmap' => self::getResilienceRoadmapPrompt($context),
            'crisis_mode' => self::getCrisisModePrompt($context),
            default => throw new \InvalidArgumentException("Unknown prompt type: {$type}")
        };
    }
}