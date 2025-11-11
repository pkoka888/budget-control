# 📊 Budget Control Research & Data Analysis Extraction

> **Source Documents:**
> - `docs/ENHANCED-FEATURES-SPEC.md` (Research Study)
> - `docs/CSV-ANALYSIS.md` (Data Analysis)

---

## 🔬 RESEARCH STUDY: Enhanced Features Specification

### Research Methodology
- **Status:** Research Complete
- **Apps Analyzed:** YNAB, Mint, PocketGuard, Trello, Todoist, TickTick, Asana
- **Focus Areas:** Budget apps, task management, family productivity

### Key Research Findings

#### User Behavior Patterns
- **What Users Love:**
  - Auto-import functionality
  - Visual dashboards
  - "Safe to spend" calculations
  - Subscription detection
  - Visual progress indicators

- **What Users Hate:**
  - Manual transaction entry
  - Steep learning curves
  - Complex reports
  - High subscription costs
  - Feature overwhelm

#### Best Practices Identified
- **Top Performing Apps:**
  - Simplifi: Ease of use
  - PocketGuard: Simplicity
  - YNAB: Flexibility without complexity

- **Success Metrics:**
  - 73% of users abandon complex apps within 30 days
  - Context separation reduces cognitive load by 40%
  - Gamification increases task completion by 90%

### Design Principles Derived

#### Core Philosophy
1. **Simple first, complex optional**
   - 5-minute setup
   - 10-minute weekly maintenance

2. **Context-aware organization**
   - Personal/Friends/Work separation
   - Mental model isolation

3. **Automation without overwhelm**
   - Smart defaults
   - Easy manual override

4. **Family-focused design**
   - 2 parents + kids support
   - Shared & private spaces

5. **Mobile-first approach**
   - On-the-go expense/task entry
   - Touch-friendly interfaces

6. **Visual over numerical**
   - Charts and progress bars
   - Color-coded feedback

### Budget-Specific Insights

#### Forecasting & Predictions
- "Safe to Spend" calculation methodology
- Spending pace indicators
- Recurring expense detection
- Monthly projection algorithms

#### Automation Features
- Auto-categorization with confidence scoring
- Recurring transaction management
- Smart alerts and notifications
- Weekly insight emails

#### Category Management
- Default category sets for different contexts
- Custom category creation
- Subcategory support
- Archive vs delete functionality

### Task Management Insights

#### Kanban Effectiveness
- 3-column system (To Do → In Progress → Done)
- WIP limits (3-5 tasks optimal)
- Visual progress tracking

#### Collaboration Patterns
- Family member assignment
- Permission tiers (Owner/Editor/Member/Viewer/Guest)
- Activity feeds and notifications

#### Gamification Research
- Points and leaderboard systems
- Achievement badges
- Allowance integration for kids
- Streak-based rewards

---

## 📊 DATA ANALYSIS: CSV Transaction Patterns

### Dataset Overview
- **Total Transactions:** 1,636
- **Date Range:** Multiple years (needs exact calculation)
- **Currency:** CZK (Czech Koruna)
- **Data Source:** Bank export (Czech banking format)

### Category Distribution Analysis

#### Top Expense Categories (by frequency)
1. **Groceries (Potraviny):** 174 transactions
2. **Life Insurance (Životní pojištění):** 106 transactions
3. **Dining Out (Restaurace):** 88 transactions
4. **Tobacco (Trafika):** 81 transactions
5. **Fuel (Pohonné hmoty):** 75 transactions

#### Income Patterns
- **Primary Income:** COM PLUS CZ a.s. = 49,145 Kč/month
- **Other Income Sources:** 46 transactions (miscellaneous)
- **Irregular Income:** 12 transactions

### Merchant Pattern Recognition

#### Grocery Stores
- ALBERT, Tesco Bilina, Kaufland Marienberg, Lidl, Penny, Potraviny Martin

#### Restaurants & Dining
- Oskarshausen GmbH, Bistro Jezero Most, Tehumen a.s., Restaurace Pod Lampou, Catering Benda

#### Tech/Subscription Services
- OpenAI (ChatGPT), GitHub, Perplexity AI, Warp Pro, Factory AI, Ollama, Kilo Code LLC

#### Transportation
- Fuel stations: Ono Havran, Benzina, Shell patterns
- Public transport: MHD patterns

### Recurring Expense Detection

#### Fixed Monthly Payments
- **Loan Payments:** Multiple loans totaling ~19,000 Kč/month
  - Uver 1: 4,878 Kč
  - Uver 2: 6,300 Kč
  - Uver 3: 7,793 Kč
- **Insurance:** Nelinka = 443 Kč/month
- **Life Insurance:** 106 transactions (monthly pattern)

#### Subscription Services Identified
- Netflix, Spotify, Google services
- AI tools: ChatGPT (576.15 Kč), GitHub (86.32 Kč), Perplexity (524.20 Kč)

### Geographic Analysis
- **Primary Country:** Czech Republic (majority)
- **Other Countries:** Germany (Freital, Marienberg), Ireland (Dublin), USA, Canada
- **Key Cities:** Bilina, Teplice, Most, Chomutov, Ostrov, Havran, Slany

### Smart Categorization Rules Developed

#### Pattern-Based Rules
```javascript
// Groceries
"ALBERT|TESCO|KAUFLAND|LIDL|PENNY|BILLA|POTRAVINY": "Groceries"

// Restaurants
"RESTAURACE|BISTRO|PIZZA|BURGER|KFC|MCDONALD": "Dining Out"

// Tech/SaaS
"OPENAI|GITHUB|PERPLEXITY|CHATGPT|AI|WARP|FACTORY": "Electronics"
"GOOGLE PLAY|APP STORE|NETFLIX|SPOTIFY": "Entertainment"

// Transport
"BENZINA|SHELL|OMV|MOL|ONO.*HAVRAN": "Fuel"
"UBER|BOLT|LIFTAGO|TAXI": "Transportation"
```

#### Transaction Type Rules
- "Inkaso úvěru" → Loan Repayment
- "Příchozí úhrada" → Income
- "Debetní úrok" → Interest
- "Cena za služby" → Fees

#### Amount Pattern Recognition
- Recurring amounts: 6300, 443, 7793, 4878 Kč (loan payments)
- Monthly salary: 49,145 Kč

### Data Quality Issues Identified
1. **Empty Categories:** 442 transactions need auto-categorization
2. **Malformed Amounts:** Some with quotes ("4,000.00")
3. **Inconsistent Merchant Names:** Variations (GECO vs "330 GECO VAM DEKUJE")
4. **Mixed Languages:** Czech merchants + English tech services
5. **Missing Data:** Empty merchant fields

### Implementation Recommendations

#### CSV Import Strategy
- **Column Mapping:** Date, Merchant, Amount, Category, Location, Type
- **Amount Handling:** Remove quotes, handle decimal commas
- **Date Parsing:** DD.MM.YYYY → YYYY-MM-DD format
- **Category Priority:** Existing → Merchant pattern → Transaction type → Amount pattern → Fallback

#### Auto-Categorization Algorithm
1. Use existing category if present
2. Apply merchant name pattern matching
3. Check transaction type
4. Detect recurring amounts
5. Fallback to "Uncategorized"

---

## 🎯 Key Takeaways for Budget Control App

### From Research Study
- **Simplicity is critical** - 73% abandonment rate for complex apps
- **Visual feedback works** - Charts > numbers for user engagement
- **Context separation helps** - 40% cognitive load reduction
- **Mobile-first design** - Families manage finances on-the-go
- **Automation must be optional** - Users want control
- **Gamification motivates** - 90% higher completion with rewards

### From Data Analysis
- **Pattern recognition is feasible** - 85%+ auto-categorization possible
- **Recurring detection crucial** - Monthly bills, subscriptions, loans
- **Merchant normalization needed** - Handle name variations
- **Multi-language support required** - CZ/EN category mapping
- **Location awareness valuable** - Geographic spending patterns
- **Real data validates assumptions** - Czech family spending patterns

### Combined Insights
- Research provides theoretical framework
- Data analysis validates practical implementation
- Together enable data-driven feature development
- User behavior research + transaction patterns = effective budget control

---

**Extraction Date:** 2025-11-06
**Sources:** ENHANCED-FEATURES-SPEC.md, CSV-ANALYSIS.md