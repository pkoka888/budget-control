# Budget Control - Task Breakdown & Assignment

## Overview

This document breaks down the remaining work into specific tasks assigned to **Cline** (UI/UX focus) and **Kilo** (Backend/Features focus).

**Project Status**: v1.0.0 Complete, Moving to v1.5 (UI) and v2.0 (Features)

---

## CLINE TASKS - Phase 2: UI/UX Enhancement

**Focus**: Making the application beautiful, responsive, and user-friendly
**Estimated Duration**: 3-5 days
**Priority**: High

### Sprint 1: Dashboard & Layout (1-2 days)

#### Task C-1.1: Responsive Layout System
**Objective**: Make the sidebar + main content work on all screen sizes

**Details**:
- [ ] Create breakpoint system for responsive design
- [ ] Implement hamburger menu for mobile (<768px)
- [ ] Create mobile-friendly sidebar (collapsible)
- [ ] Test on mobile (375px), tablet (768px), desktop (1024px)
- [ ] Ensure proper touch targets (48px minimum)
- [ ] Fix table scrolling on mobile

**Deliverables**:
- Working mobile navigation
- Tested breakpoints
- Mobile navigation screenshot

**Files to Modify**:
- `views/layout.php` - Add responsive structure
- `public/assets/css/style.css` - Add media queries

---

#### Task C-1.2: Dashboard Chart Enhancements
**Objective**: Improve chart display and add more chart types

**Details**:
- [ ] Fix Chart.js rendering on small screens
- [ ] Add chart.js responsive configuration
- [ ] Implement bar chart for category comparison
- [ ] Add pie chart for net worth composition
- [ ] Create chart legend improvements
- [ ] Add hover tooltips with values
- [ ] Implement data labels on charts

**Deliverables**:
- Enhanced charts in dashboard
- Multiple chart types working
- Screenshot of enhanced dashboard

**Files to Modify**:
- `views/dashboard.php` - Update chart rendering
- `public/assets/js/main.js` - Add chart.js configurations

---

#### Task C-1.3: Key Metrics Cards Redesign
**Objective**: Make metric cards more visually appealing

**Details**:
- [ ] Add subtle backgrounds/gradients
- [ ] Implement icon system (SVG or Font Awesome)
- [ ] Add trend indicators (up/down arrows)
- [ ] Display percentage change vs previous month
- [ ] Add mini sparklines to cards
- [ ] Improve card hover effects
- [ ] Add helpful tooltips

**Deliverables**:
- Redesigned metric cards
- Icon set implemented
- Visual improvements documented

**Files to Modify**:
- `views/dashboard.php` - Update card HTML
- `public/assets/css/style.css` - Add card styles

---

### Sprint 2: Form & Input Components (1 day)

#### Task C-2.1: Create Reusable Form Components
**Objective**: Build consistent form elements

**Details**:
- [ ] Create form input component (text, email, password)
- [ ] Create select/dropdown component
- [ ] Create date picker component
- [ ] Create currency input with formatting
- [ ] Create textarea component
- [ ] Add form validation messaging
- [ ] Create error states for all inputs

**Deliverables**:
- Form component library
- Sample form page
- Component documentation

**Files to Create**:
- `views/components/form-input.php`
- `views/components/form-select.php`
- `views/components/form-date.php`
- `views/components/form-currency.php`

**Files to Modify**:
- `public/assets/css/style.css` - Add component styles
- `public/assets/js/main.js` - Add form validation

---

#### Task C-2.2: Transaction Input Form
**Objective**: Create improved transaction entry form

**Details**:
- [ ] Design clean transaction entry form
- [ ] Implement category autocomplete
- [ ] Add quick-entry (minimal fields)
- [ ] Add full-entry (all fields) toggle
- [ ] Implement date picker
- [ ] Add amount with currency selector
- [ ] Create recurring transaction toggle
- [ ] Add form validation

**Deliverables**:
- Functional transaction form
- Form validation working
- Screenshot of form

**Files to Modify**:
- `views/transactions/create.php` - Redesign form

---

#### Task C-2.3: Budget Input Form
**Objective**: Create improved budget entry form

**Details**:
- [ ] Design budget creation form
- [ ] Add category selector
- [ ] Add amount input with preview
- [ ] Add month/date range picker
- [ ] Create budget template selector
- [ ] Add rollover option
- [ ] Display budget impact preview

**Deliverables**:
- Functional budget form
- Form validation
- Screenshot

**Files to Modify**:
- `views/budgets/create.php` - Redesign form

---

### Sprint 3: Dark Mode & Theming (1 day)

#### Task C-3.1: Implement Dark Mode
**Objective**: Create dark theme for better accessibility and user choice

**Details**:
- [ ] Define dark color palette
- [ ] Create CSS variables for all colors
- [ ] Implement dark mode toggle button
- [ ] Add localStorage persistence
- [ ] Ensure WCAG AA contrast compliance
- [ ] Test dark mode on all pages
- [ ] Add system preference detection (prefers-color-scheme)

**Deliverables**:
- Working dark mode toggle
- Dark theme applied everywhere
- Screenshot of dark mode

**Files to Modify**:
- `public/assets/css/style.css` - Add CSS variables & dark mode
- `views/layout.php` - Add theme toggle button
- `public/assets/js/main.js` - Add theme switching logic

---

#### Task C-3.2: Theme System Foundation
**Objective**: Create flexible theming system for future customization

**Details**:
- [ ] Extract all colors to CSS variables
- [ ] Create theme configuration file
- [ ] Document theme variables
- [ ] Create color palette documentation
- [ ] Prepare for additional themes (light/dark/custom)

**Deliverables**:
- CSS variables for all colors
- Theme documentation
- Extensible theme system

**Files to Create**:
- `public/assets/css/variables.css`

**Files to Modify**:
- `public/assets/css/style.css` - Use CSS variables

---

### Sprint 4: Accessibility & Polish (1-2 days)

#### Task C-4.1: WCAG 2.1 AA Compliance
**Objective**: Ensure application is accessible to all users

**Details**:
- [ ] Add ARIA labels to interactive elements
- [ ] Implement keyboard navigation (Tab, Enter, Escape)
- [ ] Add focus indicators (visible focus rings)
- [ ] Ensure color contrast ratios (4.5:1 minimum)
- [ ] Create skip navigation links
- [ ] Add alt text to all images
- [ ] Test with screen reader (NVDA/JAWS)
- [ ] Fix form labeling

**Deliverables**:
- WCAG 2.1 AA compliance report
- Accessibility test results
- Updated HTML with ARIA

**Tools**:
- axe DevTools for accessibility audit
- WAVE browser extension
- Screen reader testing

**Files to Modify**:
- All `.php` view files - Add ARIA labels
- `public/assets/js/main.js` - Add keyboard handlers

---

#### Task C-4.2: Loading States & Empty States
**Objective**: Improve user experience during loading and empty scenarios

**Details**:
- [ ] Create skeleton loading screens
- [ ] Add loading spinners
- [ ] Create empty state illustrations
- [ ] Display helpful empty state messages
- [ ] Add "Create first item" CTAs
- [ ] Create error state displays
- [ ] Add success notifications

**Deliverables**:
- Loading state components
- Empty state templates
- Screenshot of states

**Files to Create**:
- `views/components/loading-skeleton.php`
- `views/components/empty-state.php`

**Files to Modify**:
- `public/assets/css/style.css` - Add loading animations

---

#### Task C-4.3: Transitions & Animations
**Objective**: Add subtle animations for better UX

**Details**:
- [ ] Add page transition animations
- [ ] Implement button hover effects
- [ ] Create card entrance animations
- [ ] Add form validation feedback animations
- [ ] Implement fade-in animations
- [ ] Create slide-in notifications
- [ ] Add smooth scroll behavior

**Deliverables**:
- Animated dashboard
- Smooth transitions throughout
- Animation performance tested

**Files to Modify**:
- `public/assets/css/style.css` - Add animations

---

### Sprint 5: Additional Pages UI (1-2 days)

#### Task C-5.1: Accounts Page Redesign
**Objective**: Create attractive accounts management interface

**Details**:
- [ ] Design account cards (showing balance, type, last transaction)
- [ ] Create account creation modal
- [ ] Add account edit form
- [ ] Create account type icons
- [ ] Add quick account actions
- [ ] Display account growth chart
- [ ] Create account statement link

**Deliverables**:
- Redesigned accounts page
- Working account cards
- Screenshot

**Files to Modify**:
- `views/accounts/list.php` - Redesign layout

---

#### Task C-5.2: Transactions List Redesign
**Objective**: Create attractive, sortable, filterable transactions interface

**Details**:
- [ ] Design transaction rows with category badges
- [ ] Add sorting (date, amount, category)
- [ ] Implement simple filters (category, type)
- [ ] Add transaction detail modal
- [ ] Create bulk actions toolbar
- [ ] Add transaction amount color coding (red/green)
- [ ] Create infinite scroll or pagination

**Deliverables**:
- Redesigned transactions list
- Sorting/filtering working
- Screenshot

**Files to Modify**:
- `views/transactions/list.php` - Redesign list

---

#### Task C-5.3: Budget Page Redesign
**Objective**: Create visual budget tracking interface

**Details**:
- [ ] Design budget cards with progress bars
- [ ] Create visual budget vs actual comparison
- [ ] Add over-budget warning visual
- [ ] Create budget summary header
- [ ] Add monthly budget selector
- [ ] Design budget creation button
- [ ] Add quick budget editing

**Deliverables**:
- Redesigned budgets page
- Visual progress bars
- Screenshot

**Files to Modify**:
- `views/budgets/list.php` - Redesign layout

---

#### Task C-5.4: Responsive Tables Fix
**Objective**: Make all tables mobile-friendly

**Details**:
- [ ] Add horizontal scrolling for mobile tables
- [ ] Convert tables to cards on mobile (<768px)
- [ ] Create table headers sticky
- [ ] Add column visibility toggle
- [ ] Improve table readability
- [ ] Test with actual data

**Deliverables**:
- All tables responsive
- Mobile-friendly table views
- Screenshot of mobile table

**Files to Modify**:
- `public/assets/css/style.css` - Add table media queries
- `views/transactions/list.php`, `views/accounts/list.php`, etc. - Update markup

---

### Sprint 6: Icon System & Polish (1 day)

#### Task C-6.1: Icon Set Implementation
**Objective**: Replace emoji with proper icon set

**Details**:
- [ ] Choose icon library (SVG icons.svg or Font Awesome)
- [ ] Replace all emoji in templates
- [ ] Create icon component for consistency
- [ ] Document icon usage
- [ ] Ensure icons are accessible (aria-label)
- [ ] Add icon colors matching design

**Deliverables**:
- Consistent icon set
- Icon documentation
- Updated templates

**Options**:
- Feather Icons (23KB)
- Font Awesome Free (150KB+)
- Custom SVG set
- Bootstrap Icons

**Recommendation**: Feather Icons (lightweight, clean)

**Files to Create**:
- `views/components/icon.php` - Icon component

**Files to Modify**:
- All view files - Replace emoji with icons

---

#### Task C-6.2: Color System Refinement
**Objective**: Create cohesive color palette

**Details**:
- [ ] Define primary color palette (5-7 colors)
- [ ] Create neutral colors (grays)
- [ ] Define semantic colors (success, warning, danger)
- [ ] Create color combinations documentation
- [ ] Ensure accessibility contrast
- [ ] Document color usage patterns

**Deliverables**:
- Color palette documentation
- CSS variable definitions
- Color usage guide

**Files to Modify**:
- `public/assets/css/style.css` - Define colors

---

### Sprint 7: Testing & Documentation (1 day)

#### Task C-7.1: Cross-Browser Testing
**Objective**: Ensure compatibility across browsers

**Details**:
- [ ] Test in Chrome/Chromium
- [ ] Test in Firefox
- [ ] Test in Safari
- [ ] Test in Edge
- [ ] Test on iOS Safari
- [ ] Test on Android Chrome
- [ ] Document any issues

**Tools**:
- BrowserStack (if available)
- Manual testing

**Deliverables**:
- Cross-browser test report
- Bug fixes for compatibility issues

---

#### Task C-7.2: Performance Optimization
**Objective**: Ensure fast page load times

**Details**:
- [ ] Minify CSS files
- [ ] Minify JavaScript files
- [ ] Optimize images
- [ ] Remove unused CSS (PurgeCSS)
- [ ] Test page load time (<3 seconds)
- [ ] Test lighthouse score (>90)
- [ ] Implement lazy loading for images

**Tools**:
- Google Lighthouse
- PageSpeed Insights
- WebPageTest

**Deliverables**:
- Lighthouse report >90
- Performance optimization summary

---

#### Task C-7.3: UI Documentation
**Objective**: Document UI components and patterns

**Details**:
- [ ] Create component library documentation
- [ ] Document color palette
- [ ] Document typography
- [ ] Create design system guide
- [ ] Document interaction patterns
- [ ] Create screenshot library
- [ ] Write UI guidelines

**Deliverables**:
- UI components documentation
- Design system guide
- Screenshot library

**Files to Create**:
- `docs/UI_COMPONENTS.md`
- `docs/DESIGN_SYSTEM.md`

---

### Task Priorities (Cline)

**Must Do (Critical)**:
1. C-1.1 - Responsive Layout
2. C-1.2 - Dashboard Charts
3. C-3.1 - Dark Mode
4. C-4.1 - Accessibility
5. C-4.2 - Loading States

**Should Do (Important)**:
6. C-2.1 - Form Components
7. C-4.3 - Animations
8. C-5.1 - Accounts Page
9. C-5.2 - Transactions List
10. C-6.1 - Icon System

**Nice to Have (Can be deferred)**:
11. C-7.1 - Cross-browser Testing
12. C-7.3 - UI Documentation

---

## KILO TASKS - Phase 3: Features & Backend

**Focus**: Implementing advanced features and backend functionality
**Estimated Duration**: 1-2 weeks
**Priority**: High

### Sprint 1: Transaction Management (2-3 days)

#### Task K-1.1: Advanced Transaction Filtering
**Objective**: Allow users to filter transactions by multiple criteria

**Details**:
- [ ] Create advanced filter form
- [ ] Implement date range picker
- [ ] Add category multi-select filter
- [ ] Add amount range filter
- [ ] Implement merchant search
- [ ] Add transaction type filter (income/expense)
- [ ] Save filter presets
- [ ] Display active filter count

**Deliverables**:
- Working filter interface
- Tested filtering logic
- Filter persistence (URL params or localStorage)

**Database**: No changes needed
**Controllers**: Enhance `TransactionController::list()`
**Views**: Create `views/components/transaction-filter.php`

**Code Changes**:
```php
// src/Controllers/TransactionController.php
public function list() {
    $filters = $this->buildFiltersFromRequest();
    $transactions = $this->getFilteredTransactions($filters);
    // ...
}

private function buildFiltersFromRequest() {
    return [
        'dateFrom' => $this->getQueryParam('date_from'),
        'dateTo' => $this->getQueryParam('date_to'),
        'categories' => explode(',', $this->getQueryParam('categories', '')),
        'amountMin' => $this->getQueryParam('amount_min'),
        'amountMax' => $this->getQueryParam('amount_max'),
        'merchant' => $this->getQueryParam('merchant'),
        'type' => $this->getQueryParam('type')
    ];
}
```

---

#### Task K-1.2: Bulk Transaction Operations
**Objective**: Allow bulk operations on transactions

**Details**:
- [ ] Implement select checkboxes on transaction list
- [ ] Create bulk categorize action
- [ ] Create bulk delete action
- [ ] Create bulk tag action
- [ ] Create bulk status change (reconciled/unreconciled)
- [ ] Display selected count
- [ ] Add confirmation dialogs
- [ ] Create bulk operation history

**Deliverables**:
- Working bulk select interface
- Bulk operations implemented
- Confirmation dialogs

**Database**: No schema changes
**Controllers**: Create `TransactionController::bulkUpdate()`
**Views**: Update transaction list markup

---

#### Task K-1.3: Transaction Splitting
**Objective**: Allow splitting a transaction into multiple categories

**Details**:
- [ ] Create transaction split form
- [ ] Allow dividing amount across categories
- [ ] Store split data in database (consider schema change)
- [ ] Display split transactions together
- [ ] Implement unsplit functionality
- [ ] Calculate tax per split (if applicable)

**Deliverables**:
- Transaction splitting working
- Database schema updated if needed
- UI for splitting

**Database Changes** (Optional):
```sql
ALTER TABLE transactions ADD COLUMN parent_transaction_id INTEGER;
CREATE TABLE transaction_splits (
    id INTEGER PRIMARY KEY,
    parent_id INTEGER,
    category_id INTEGER,
    amount DECIMAL(15,2),
    FOREIGN KEY(parent_id) REFERENCES transactions(id)
);
```

---

#### Task K-1.4: Recurring Transaction Detection
**Objective**: Automatically detect recurring transactions

**Details**:
- [ ] Analyze transaction history
- [ ] Detect patterns (same merchant, similar amount, monthly interval)
- [ ] Create recurring transaction records
- [ ] Suggest creation of recurring templates
- [ ] Track recurring transaction status
- [ ] Alert for missed recurring transactions

**Deliverables**:
- Recurring transaction detection working
- Recurring transaction UI
- Suggestions displayed

**Services**: Create `src/Services/RecurringTransactionDetector.php`

---

### Sprint 2: Reporting & Analytics (2-3 days)

#### Task K-2.1: Monthly Report Generation
**Objective**: Create comprehensive monthly financial reports

**Details**:
- [ ] Design monthly report layout
- [ ] Calculate all metrics (income, expenses, savings, etc.)
- [ ] Create category breakdowns
- [ ] Generate charts for report
- [ ] Compare with previous months
- [ ] Create summary statistics
- [ ] Display top merchants

**Deliverables**:
- Working monthly report
- Report data calculated correctly
- PDF export ready (for next task)

**Controllers**: Create `src/Controllers/ReportController.php`
**Services**: Create `src/Services/ReportGenerator.php`
**Views**: Create `views/reports/monthly.php`

```php
// src/Services/ReportGenerator.php
class ReportGenerator {
    public function generateMonthlyReport($userId, $month) {
        return [
            'summary' => $this->getSummary($userId, $month),
            'categories' => $this->getCategoryBreakdown($userId, $month),
            'merchants' => $this->getTopMerchants($userId, $month),
            'comparison' => $this->getMonthComparison($userId, $month),
        ];
    }
}
```

---

#### Task K-2.2: Yearly Report & Trends
**Objective**: Create yearly financial report and trend analysis

**Details**:
- [ ] Design yearly report layout
- [ ] Calculate yearly metrics
- [ ] Create year-over-year comparisons
- [ ] Show monthly trend charts
- [ ] Calculate yearly growth rates
- [ ] Create category trends
- [ ] Display spending patterns by month

**Deliverables**:
- Working yearly report
- Trend analysis displayed
- Year-over-year comparison

**Controllers**: Enhance `ReportController::yearly()`
**Views**: Create `views/reports/yearly.php`

---

#### Task K-2.3: Custom Date Range Reports
**Objective**: Allow reports for any date range

**Details**:
- [ ] Create date range selector
- [ ] Implement flexible report generation
- [ ] Support custom periods (last 30 days, last quarter, custom range)
- [ ] Create downloadable reports
- [ ] Cache report generation
- [ ] Handle date validation

**Deliverables**:
- Custom date range reports working
- Report selector UI
- Download functionality ready

**Controllers**: Enhance `ReportController`
**Views**: Create `views/components/date-range-picker.php`

---

#### Task K-2.4: Category Trend Analysis
**Objective**: Analyze spending trends by category

**Details**:
- [ ] Create category trend view
- [ ] Calculate category spending over time
- [ ] Show category growth/decline
- [ ] Create category comparison charts
- [ ] Identify seasonal patterns
- [ ] Alert on category overspending
- [ ] Suggest optimizations

**Deliverables**:
- Category trend analysis working
- Trend charts displayed
- Pattern detection working

**Services**: Enhance `FinancialAnalyzer` class
**Views**: Create `views/analytics/category-trends.php`

---

#### Task K-2.5: Cash Flow Analysis
**Objective**: Analyze money flow in/out

**Details**:
- [ ] Create cash flow statement
- [ ] Calculate inflows vs outflows
- [ ] Show net flow over time
- [ ] Create waterfall chart
- [ ] Identify cash flow patterns
- [ ] Project future cash flow
- [ ] Alert on negative cash flow

**Deliverables**:
- Cash flow analysis working
- Waterfall chart implemented
- Projections calculated

**Services**: Create `src/Services/CashFlowAnalyzer.php`
**Views**: Create `views/analytics/cash-flow.php`

---

### Sprint 3: Export Functionality (1-2 days)

#### Task K-3.1: Export to CSV
**Objective**: Allow exporting data to CSV format

**Details**:
- [ ] Create CSV export for transactions
- [ ] Create CSV export for budgets
- [ ] Create CSV export for accounts
- [ ] Create CSV export for reports
- [ ] Include date ranges
- [ ] Ensure proper formatting
- [ ] Handle large exports efficiently

**Deliverables**:
- CSV export working for all data types
- Proper file naming convention
- Download working

**Services**: Create `src/Services/CsvExporter.php`

```php
// src/Services/CsvExporter.php
class CsvExporter {
    public function exportTransactions($transactions) {
        $csv = fopen('php://memory', 'w');
        fputcsv($csv, ['Date', 'Description', 'Category', 'Amount', 'Type']);
        foreach ($transactions as $tx) {
            fputcsv($csv, [
                $tx['date'],
                $tx['description'],
                $tx['category_name'],
                $tx['amount'],
                $tx['type']
            ]);
        }
        return stream_get_contents($csv);
    }
}
```

---

#### Task K-3.2: Export to Excel (XLSX)
**Objective**: Create Excel exports with formatting

**Details**:
- [ ] Create XLSX export with PhpSpreadsheet or similar
- [ ] Add formatting (colors, fonts, borders)
- [ ] Include multiple sheets (summary, details)
- [ ] Add charts to Excel
- [ ] Create pivot tables (future)
- [ ] Handle large datasets

**Deliverables**:
- XLSX export working
- Proper formatting applied
- Multiple sheets working

**Note**: May require external library (PhpSpreadsheet)

**Alternative**: Use CSV + note that Excel can import

---

#### Task K-3.3: PDF Report Export
**Objective**: Generate professional PDF reports

**Details**:
- [ ] Create PDF export using Dompdf or MPDF
- [ ] Design professional report layout
- [ ] Include charts as images
- [ ] Add date/time generated
- [ ] Include summary page
- [ ] Add page numbers
- [ ] Create downloadable PDF

**Deliverables**:
- PDF export working
- Professional layout
- All data included

**Services**: Create `src/Services/PdfGenerator.php`

**Note**: May require external library

---

### Sprint 4: Budget Management (1-2 days)

#### Task K-4.1: Budget Alerts & Notifications
**Objective**: Alert users when budgets are exceeded

**Details**:
- [ ] Implement budget overspending detection
- [ ] Create in-app notifications
- [ ] Display alert badges in UI
- [ ] Calculate days remaining in month
- [ ] Estimate spending trajectory
- [ ] Show alert threshold (75%, 90%, 100%, 110%)
- [ ] Create dismissible alerts

**Deliverables**:
- Budget alerts working
- Notifications displayed
- Alert thresholds configurable

**Controllers**: Update `BudgetController`
**Services**: Enhance `FinancialAnalyzer`

---

#### Task K-4.2: Budget Templates
**Objective**: Create reusable budget templates

**Details**:
- [ ] Create default budget templates (basic, detailed, family)
- [ ] Allow users to create custom templates
- [ ] Apply templates to new months
- [ ] Copy previous month's budget
- [ ] Suggest budgets based on spending history
- [ ] Allow template editing

**Deliverables**:
- Budget templates working
- Template selection UI
- Copy/apply functionality

**Database Changes**:
```sql
CREATE TABLE budget_templates (
    id INTEGER PRIMARY KEY,
    user_id INTEGER,
    name TEXT,
    categories JSON,
    is_default INTEGER,
    FOREIGN KEY(user_id) REFERENCES users(id)
);
```

---

#### Task K-4.3: Budget Performance Analytics
**Objective**: Analyze budget adherence over time

**Details**:
- [ ] Calculate budget adherence percentage
- [ ] Show budget performance history
- [ ] Identify categories consistently over budget
- [ ] Suggest budget adjustments
- [ ] Create budget efficiency score
- [ ] Track budget improvement over time

**Deliverables**:
- Budget performance dashboard
- Analytics calculated correctly
- Suggestions generated

---

### Sprint 5: Investment Management (1-2 days)

#### Task K-5.1: Investment Portfolio Dashboard
**Objective**: Create comprehensive investment portfolio view

**Details**:
- [ ] Display total portfolio value
- [ ] Show individual holdings with current price
- [ ] Calculate gains/losses (absolute and percentage)
- [ ] Display asset allocation pie chart
- [ ] Show portfolio performance over time
- [ ] Calculate annualized returns
- [ ] Display dividend income

**Deliverables**:
- Investment portfolio dashboard working
- All metrics calculated
- Charts displayed

**Controllers**: Create/enhance `InvestmentController`
**Views**: Create `views/investments/portfolio.php`

---

#### Task K-5.2: Trading & Transaction History
**Objective**: Track investment trades and transactions

**Details**:
- [ ] Create trade entry form (buy/sell)
- [ ] Calculate cost basis
- [ ] Track dividend transactions
- [ ] Display trade history
- [ ] Calculate realized gains/losses
- [ ] Show transaction fees impact
- [ ] Create performance by transaction

**Deliverables**:
- Trade tracking working
- Trade history displayed
- Gains/losses calculated

**Database**: Enhanced `investments` and related tables

---

#### Task K-5.3: Asset Allocation & Rebalancing
**Objective**: Help manage asset allocation

**Details**:
- [ ] Define target asset allocation
- [ ] Calculate current allocation
- [ ] Show allocation vs target
- [ ] Create rebalancing recommendations
- [ ] Calculate rebalancing trades needed
- [ ] Show allocation drift over time
- [ ] Alert when out of tolerance

**Deliverables**:
- Asset allocation view working
- Rebalancing calculator working
- Recommendations generated

---

### Sprint 6: Goals Management (1-2 days)

#### Task K-6.1: Financial Goals Dashboard
**Objective**: Create goals tracking interface

**Details**:
- [ ] Display all financial goals
- [ ] Show progress bars toward goals
- [ ] Calculate time to goal achievement
- [ ] Display required monthly savings
- [ ] Show goal milestones
- [ ] Create goal categories
- [ ] Display goal completion status

**Deliverables**:
- Goals dashboard working
- Progress calculations correct
- UI displays goals clearly

**Controllers**: Create/enhance `GoalsController`
**Views**: Create `views/goals/list.php`

---

#### Task K-6.2: Goal Progress Tracking
**Objective**: Track progress toward goals

**Details**:
- [ ] Calculate progress percentage
- [ ] Track dedicated savings toward goals
- [ ] Auto-update goal progress
- [ ] Create goal achievement projections
- [ ] Show on-track/off-track status
- [ ] Generate achievement timeline
- [ ] Alert when off track

**Deliverables**:
- Goal progress calculations working
- Status indicators displayed
- Projections calculated

**Services**: Create `src/Services/GoalTracker.php`

---

#### Task K-6.3: Savings Calculator
**Objective**: Calculate required savings for goals

**Details**:
- [ ] Implement compound interest calculator
- [ ] Calculate required monthly savings
- [ ] Account for inflation
- [ ] Project future values
- [ ] Create multiple scenarios (conservative/moderate/aggressive)
- [ ] Show impact of rate of return
- [ ] Create interactive calculator

**Deliverables**:
- Savings calculator working
- Scenarios calculated
- Interactive UI

**Views**: Create `views/components/savings-calculator.php`

---

### Sprint 7: Settings & User Management (1 day)

#### Task K-7.1: User Settings Page
**Objective**: Allow users to configure preferences

**Details**:
- [ ] User profile editing (name, email)
- [ ] Currency preference
- [ ] Date format preference
- [ ] Number format preference
- [ ] Timezone setting
- [ ] Default account selection
- [ ] Notification preferences

**Deliverables**:
- Settings page working
- All preferences saved
- Preferences applied throughout app

**Controllers**: Create/enhance `SettingsController`
**Views**: Create `views/settings/preferences.php`

---

#### Task K-7.2: Data Management
**Objective**: Allow users to manage their data

**Details**:
- [ ] Account deletion (with confirmation)
- [ ] Data export (complete backup)
- [ ] Data import (restore backup)
- [ ] Account merging (future)
- [ ] Selective data deletion
- [ ] Privacy settings
- [ ] Download user data (GDPR)

**Deliverables**:
- Data management interface working
- Export/import functionality
- Data deletion working

---

#### Task K-7.3: Security Settings
**Objective**: Allow users to manage account security

**Details**:
- [ ] Password change functionality
- [ ] Session management
- [ ] Active sessions view
- [ ] Login history
- [ ] IP whitelist (future)
- [ ] API key management (future)
- [ ] 2FA setup (future)

**Deliverables**:
- Security settings page working
- Password change working
- Session management functional

---

### Sprint 8: API Endpoints (1-2 days)

#### Task K-8.1: Create RESTful API Endpoints
**Objective**: Create API for programmatic access

**Endpoints to Create**:

```
GET    /api/v1/accounts                 - List accounts
GET    /api/v1/accounts/:id             - Get account details
POST   /api/v1/accounts                 - Create account
PUT    /api/v1/accounts/:id             - Update account
DELETE /api/v1/accounts/:id             - Delete account

GET    /api/v1/transactions             - List transactions
GET    /api/v1/transactions/:id         - Get transaction
POST   /api/v1/transactions             - Create transaction
PUT    /api/v1/transactions/:id         - Update transaction
DELETE /api/v1/transactions/:id         - Delete transaction

GET    /api/v1/categories               - List categories
GET    /api/v1/budgets                  - List budgets
GET    /api/v1/investments              - List investments
GET    /api/v1/goals                    - List goals

GET    /api/v1/analytics/dashboard      - Dashboard data
GET    /api/v1/analytics/monthly        - Monthly analysis
GET    /api/v1/reports                  - Available reports
```

**Details**:
- [ ] Create API controller base class
- [ ] Implement all endpoints
- [ ] Add request validation
- [ ] Add response formatting (JSON)
- [ ] Add pagination support
- [ ] Add filtering support
- [ ] Add error handling

**Deliverables**:
- All API endpoints working
- Request/response validation
- Pagination working

**Files to Create**:
- `src/Controllers/Api/BaseApiController.php`
- `src/Controllers/Api/V1/AccountController.php`
- `src/Controllers/Api/V1/TransactionController.php`
- `src/Controllers/Api/V1/AnalyticsController.php`

---

#### Task K-8.2: API Authentication
**Objective**: Secure API endpoints

**Details**:
- [ ] Create API key generation
- [ ] Implement API key validation
- [ ] Create API rate limiting
- [ ] Add request signing (future)
- [ ] Implement OAuth2 (future)
- [ ] Create scope-based permissions
- [ ] Add audit logging

**Deliverables**:
- API authentication working
- Rate limiting functional
- Secure endpoints

**Database Changes**:
```sql
CREATE TABLE api_keys (
    id INTEGER PRIMARY KEY,
    user_id INTEGER,
    name TEXT,
    key_hash TEXT UNIQUE,
    scopes TEXT,
    last_used DATETIME,
    is_active INTEGER,
    FOREIGN KEY(user_id) REFERENCES users(id)
);
```

---

#### Task K-8.3: API Documentation
**Objective**: Document API for developers

**Details**:
- [ ] Create API documentation
- [ ] Document all endpoints
- [ ] Provide example requests/responses
- [ ] Document authentication
- [ ] Create rate limiting info
- [ ] Provide error code reference
- [ ] Create SDK examples (JavaScript, Python)

**Deliverables**:
- Complete API documentation
- Examples and tutorials
- SDK code samples

**Files to Create**:
- `docs/API.md`
- `docs/API_AUTHENTICATION.md`
- `docs/API_EXAMPLES.md`

---

### Task Priorities (Kilo)

**Must Do (Critical)**:
1. K-1.1 - Advanced Filtering
2. K-2.1 - Monthly Reports
3. K-4.1 - Budget Alerts
4. K-5.1 - Investment Dashboard
5. K-6.1 - Goals Dashboard

**Should Do (Important)**:
6. K-1.2 - Bulk Operations
7. K-2.2 - Yearly Reports
8. K-3.1 - CSV Export
9. K-7.1 - User Settings
10. K-8.1 - API Endpoints

**Nice to Have (Can be deferred)**:
11. K-1.3 - Transaction Splitting
12. K-1.4 - Recurring Detection
13. K-3.2 - Excel Export
14. K-3.3 - PDF Export

---

## Cross-Team Tasks

### Mutual Responsibilities

#### Design System Collaboration
- Cline: Design and implement design system
- Kilo: Follow design system in feature development
- Meeting: Weekly to discuss design decisions

#### Documentation
- Cline: UI/UX documentation
- Kilo: Backend/Feature documentation
- Both: Code comments and architecture docs

#### Testing
- Cline: UI/Usability testing
- Kilo: Feature/Integration testing
- Both: Security testing

#### Performance
- Cline: Frontend performance (Lighthouse, CSS, JS optimization)
- Kilo: Backend performance (Database, API optimization)
- Both: Full-stack performance testing

---

## Task Dependency Map

```
Phase 1 (Complete) ✓
    ↓
Phase 2 (Cline - 3-5 days)
    ├─ C-1.1 (Responsive Layout) [2 days]
    ├─ C-1.2 (Dashboard Charts) [1 day]
    ├─ C-3.1 (Dark Mode) [1 day] [depends on C-1.1]
    ├─ C-4.1 (Accessibility) [1 day]
    ├─ C-2.1 (Form Components) [1 day]
    ├─ C-5.x (Page Redesigns) [2 days] [depends on C-1.1]
    └─ C-6.1 (Icon System) [1 day]
    ↓
Phase 3 (Kilo - 1-2 weeks) [starts after C-1.1]
    ├─ K-1.1 (Advanced Filtering) [1 day]
    ├─ K-2.1 (Reports) [2 days]
    ├─ K-3.1 (CSV Export) [1 day]
    ├─ K-4.1 (Budget Alerts) [1 day]
    ├─ K-5.1 (Investment) [2 days]
    ├─ K-6.1 (Goals) [1 day]
    └─ K-8.1 (API) [2 days]
    ↓
Phase 4 (Both - 2-4 weeks)
    ├─ Bank Sync Integration
    ├─ Advanced AI Features
    ├─ Testing & QA
    └─ Deployment & Monitoring
```

---

## Communication & Collaboration

### Weekly Standups (Every Monday)
- What was completed
- What's planned for this week
- Blockers and issues
- Demo of completed features

### Code Review Process
- All PRs reviewed before merge
- Minimum 1 approval required
- Tests must pass
- No merge conflicts

### Documentation
- Update as you code
- Comments for complex logic
- README updates for new features
- API documentation for new endpoints

### Issue Tracking
- Create GitHub issues for tasks
- Assign to responsible person
- Link PRs to issues
- Close issues when complete

---

## Definition of Done

For each task to be considered complete:

**Code**:
- [ ] Feature implemented
- [ ] Code follows style guide
- [ ] Comments added where needed
- [ ] No console errors/warnings
- [ ] No broken functionality

**Testing**:
- [ ] Manual testing completed
- [ ] Edge cases considered
- [ ] Error scenarios handled
- [ ] Browser compatibility checked

**Documentation**:
- [ ] Code comments added
- [ ] README/docs updated
- [ ] Screenshots added (if UI)
- [ ] API docs updated (if API)

**Quality**:
- [ ] No performance degradation
- [ ] Accessibility maintained (for Cline)
- [ ] Security best practices followed
- [ ] No breaking changes

---

## Estimated Timeline

### Cline (UI/UX Phase - 3-5 days)
- **Week 1**: Core responsive layout, dashboard, dark mode
- **Week 1-2**: Form components, page redesigns, accessibility

### Kilo (Features Phase - 1-2 weeks)
- **Week 2**: Transaction management, reports
- **Week 2-3**: Exports, budgets, investments, goals, API
- **Week 3**: Polish, documentation, final testing

### Both (Integration & Ops - 2-4 weeks)
- **Week 4**: Integration features (bank sync, AI)
- **Week 4-5**: Testing, security audit
- **Week 5**: Deployment, documentation
- **Week 5+**: Monitoring, optimizations

---

## Success Criteria

### Cline's Success
- [ ] All pages responsive and mobile-friendly
- [ ] Dark mode working perfectly
- [ ] WCAG 2.1 AA compliance achieved
- [ ] Lighthouse score >90
- [ ] All forms intuitive and working
- [ ] No UI/UX issues in QA

### Kilo's Success
- [ ] All features working as specified
- [ ] Advanced filtering and search working
- [ ] Reports generating correctly
- [ ] API endpoints fully functional
- [ ] Database queries optimized
- [ ] No backend issues in QA

### Team Success
- [ ] v1.5 released with all planned features
- [ ] Test coverage >80%
- [ ] Documentation complete
- [ ] Zero critical security issues
- [ ] Performance targets met

---

## Notes & Reminders

1. **Keep it Simple**: Don't over-engineer solutions
2. **Communicate Early**: If something is blocked, say so immediately
3. **Test Thoroughly**: Don't skip testing to save time
4. **Document as You Go**: Don't leave documentation for the end
5. **Review Code**: Help each other write better code
6. **Have Fun**: This is a great project to work on!

---

**Version**: 1.0
**Last Updated**: November 8, 2025
**Status**: Ready for Implementation
**Next Review**: November 22, 2025

---

## Appendix: Quick Reference

### Git Workflow
```bash
# Create feature branch
git checkout -b feature/task-name

# Make changes and commit
git add .
git commit -m "[TYPE] scope: description"

# Push to remote
git push origin feature/task-name

# Create Pull Request on GitHub
# Link to issue in PR description
# Wait for review and approval
# Squash and merge
```

### Common Commands

**For Cline** (Frontend):
```bash
# Test responsive design
DevTools → Toggle device toolbar (Ctrl+Shift+M)

# Test dark mode toggle
localStorage.setItem('theme', 'dark')

# Check accessibility
# Install axe DevTools extension
```

**For Kilo** (Backend):
```bash
# Test database queries
sqlite3 database/budget.db

# Run PHP local server
php -S localhost:8000 -t public/

# Test API endpoints
curl -X GET http://localhost:8000/api/v1/accounts
```

### Resources

- **Design Inspiration**: Dribbble, Behance, Figma Community
- **Icons**: feathericons.com, tabler-icons.io
- **Colors**: colorhexa.com, coolors.co
- **Typography**: fonts.google.com, systemfontstack.com
- **Performance**: web.dev, pagespeed.web.dev
- **Documentation**: github.com/adam-p/markdown-here/wiki/Markdown-Cheatsheet

---

**Let's build something amazing together! 🚀**
