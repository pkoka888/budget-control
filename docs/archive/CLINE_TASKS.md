# Cline UI/UX Tasks

## Overview
Your role: Create beautiful, responsive user interfaces for all new features

## General Guidelines

### Before Starting Each Task
1. Read the task description completely
2. Review related controller/service files
3. Look at existing similar views for patterns
4. Plan the layout and components
5. Ask Claude if you have questions

### During Implementation
1. Follow existing HTML/CSS patterns
2. Use existing component styles (buttons, cards, forms)
3. Test on desktop and mobile
4. Ensure accessibility (proper labels, alt text)
5. Keep code clean and organized

### After Completing Each Task
1. Test all interactions
2. Verify responsive design (mobile, tablet, desktop)
3. Check for CSS conflicts
4. Test with dummy data
5. Submit to Claude for review

---

## Task C-1: Transaction Split Display

### Objective
Create UI components to display and manage transaction splits

### Files to Create/Modify
- `views/transactions/show.php` - Show split details
- `views/transactions/list.php` - Add split indicator
- `public/assets/css/style.css` - Add split styling

### What Transaction Splits Are
When a user splits a transaction across multiple categories, we need to show:
- Original transaction amount
- Individual split amounts per category
- Split percentages
- Category color indicators

### UI Components Needed

#### 1. Split List in Transaction Detail View
```html
<!-- In views/transactions/show.php -->
- Transaction amount
- If splits exist:
  - "Split across X categories" banner
  - List of each split:
    - Category name (with color)
    - Split amount
    - Split percentage
    - Edit/delete buttons
  - Add new split button
```

#### 2. Split Indicator in Transaction List
```html
<!-- In views/transactions/list.php -->
- Add column: "Splits"
- If splits exist: Show badge "Split (X)"
- Color-code based on number of splits
```

### CSS Classes to Create
- `.split-badge` - For split indicator
- `.split-container` - For split details section
- `.split-item` - Individual split row
- `.split-amount` - Split amount styling
- `.split-percentage` - Percentage display

### Example Data Structure
```php
$transaction = [
    'id' => 1,
    'description' => 'Grocery Store',
    'amount' => 100,
    'splits' => [
        ['category' => 'Food', 'amount' => 60, 'color' => '#FF5733'],
        ['category' => 'Household', 'amount' => 40, 'color' => '#33FF57']
    ]
];
```

### Success Criteria
- ✓ Split information displays clearly
- ✓ Category colors match theme
- ✓ Mobile responsive (stack vertically)
- ✓ Edit/delete buttons work
- ✓ Looks professional and clean

### Time Estimate: 3-4 hours
### Complexity: Medium
### Dependencies: TransactionController (already has split methods)

---

## Task C-2: Budget Alerts UI

### Objective
Create dashboard for monitoring and managing budget alerts

### Files to Create/Modify
- `views/budgets/alerts.php` - New file
- `views/budgets/list.php` - Add alert indicators
- `public/assets/css/style.css` - Alert styling

### What Budget Alerts Are
When users approach budget limits:
- 50% spent → Warning
- 75% spent → Alert
- 100% spent → Critical
- Time-based alerts (mid-month, end of month)

### UI Components Needed

#### 1. Alert Dashboard View
```html
<!-- views/budgets/alerts.php -->
- Header: "Budget Alerts"
- Filters:
  - Status: Active, Acknowledged, Dismissed
  - Category: Dropdown
  - Date range
- Alert List:
  - Alert badge (color by severity)
  - Category name
  - Current spending / Budget amount
  - Percentage bar
  - Alert message
  - Acknowledge / Dismiss buttons
  - Date triggered
```

#### 2. Alert Indicator in Budget List
```html
<!-- In views/budgets/list.php -->
- Add alert icon if alerts exist
- Show alert count
- Different colors for severity
  - Yellow: Warning (50-75%)
  - Orange: Alert (75-100%)
  - Red: Critical (100%+)
```

### CSS Classes to Create
- `.alert-badge` - Alert status indicator
- `.alert-severity-warning` - Warning color
- `.alert-severity-alert` - Alert color
- `.alert-severity-critical` - Critical color
- `.alert-message` - Message text
- `.alert-action-buttons` - Button container

### Alert Data Structure
```php
$alerts = [
    [
        'id' => 1,
        'category' => 'Food',
        'status' => 'active', // active, acknowledged, dismissed
        'severity' => 'warning', // warning, alert, critical
        'current_spent' => 75,
        'budget_amount' => 100,
        'percentage' => 75,
        'message' => 'You have spent 75% of your Food budget',
        'triggered_at' => '2025-11-08 10:30:00'
    ]
];
```

### Success Criteria
- ✓ Alerts display with proper severity colors
- ✓ Acknowledge/dismiss buttons work
- ✓ Responsive on mobile
- ✓ Filtering works
- ✓ Percentage bars display correctly

### Time Estimate: 3-4 hours
### Complexity: Medium
### Dependencies: BudgetAlertService (already implemented)

---

## Task C-3: Investment Portfolio Enhancement

### Objective
Improve the investment portfolio view with better visualization and controls

### Files to Modify
- `views/investments/portfolio.php` - Enhance
- `public/assets/css/style.css` - Add styles
- `public/assets/js/main.js` - Add interactions

### Current Portfolio Structure
The portfolio shows:
- List of investments
- Current value
- Gain/loss
- Percentage return

### What to Enhance

#### 1. Asset Allocation Visualization
```
Asset Type Breakdown:
- Stocks: 50% ($5,000)
- Bonds: 30% ($3,000)
- ETFs: 20% ($2,000)

Visual: Pie chart or bar chart
```

#### 2. Account Allocation View
```
Organized by investment account:
- Brokerage Account: $7,000 (70%)
- Retirement Account: $3,000 (30%)
```

#### 3. Performance Summary
```
- Total Value: $10,000
- Total Cost: $9,000
- Total Gain: $1,000 (+11.1%)
- Best Performer: XYZ (+25%)
- Worst Performer: ABC (-5%)
```

#### 4. Transaction History Table
```
Enhanced display:
- Date | Type | Symbol | Quantity | Price | Total | Fees | Net Gain
- Buy/Sell/Dividend actions
- Color coded by type
- Sortable columns
```

### CSS Classes Needed
- `.portfolio-summary` - Main summary box
- `.asset-allocation` - Asset breakdown
- `.account-section` - Account grouping
- `.transaction-history` - Transaction table
- `.gain-positive` - Green text for gains
- `.gain-negative` - Red text for losses

### Success Criteria
- ✓ Asset allocation clearly visible
- ✓ Performance metrics accurate
- ✓ Transaction history comprehensive
- ✓ Mobile responsive
- ✓ Charts/visuals display well

### Time Estimate: 4-5 hours
### Complexity: Medium-High
### Dependencies: InvestmentService (already implemented)

---

## Task C-4: Goals Management UI

### Objective
Create comprehensive goal tracking and management interface

### Files to Create/Modify
- `views/goals/show.php` - Enhance detail view
- `views/goals/milestones.php` - New file for milestones
- `public/assets/css/style.css` - Goal styling

### Goal Components Needed

#### 1. Goal Progress Display
```html
Goal: Vacation Fund
Target: $5,000
Current: $3,500 (70%)

Progress Bar:
[=======--------] 70%

Timeline:
Target Date: June 1, 2026 (180 days left)
Monthly Savings Needed: $833
Projected Completion: May 15, 2026
```

#### 2. Milestone Tracking
```html
Milestones:
☑ Save $1,000 (Completed: Oct 1, 2025)
☐ Save $2,500 (In progress)
☐ Save $5,000 (Target: June 1, 2026)

Add Milestone button
```

#### 3. Savings Projection
```html
Savings Scenarios:
- If you save $500/month: Complete by July 2026
- If you save $750/month: Complete by May 2026
- If you save $1,000/month: Complete by April 2026
```

#### 4. Goal Actions
```html
- Edit Goal
- Add Deposit to Goal
- Create Milestone
- Delete Goal
- Share Goal
```

### CSS Classes Needed
- `.goal-header` - Goal title section
- `.progress-container` - Progress bar area
- `.progress-bar` - The bar itself
- `.milestone-list` - Milestones container
- `.milestone-item` - Individual milestone
- `.milestone-completed` - Completed state
- `.projection-card` - Projection scenarios
- `.goal-actions` - Action buttons

### Goal Data Structure
```php
$goal = [
    'id' => 1,
    'name' => 'Vacation Fund',
    'target_amount' => 5000,
    'current_amount' => 3500,
    'target_date' => '2026-06-01',
    'priority' => 'high',
    'progress_percentage' => 70,
    'milestones' => [
        ['name' => 'First $1,000', 'amount' => 1000, 'completed' => true],
        ['name' => 'Halfway', 'amount' => 2500, 'completed' => false]
    ]
];
```

### Success Criteria
- ✓ Progress clearly visualized
- ✓ Milestones display and update
- ✓ Projections accurate
- ✓ Mobile responsive
- ✓ Actions functional

### Time Estimate: 4-5 hours
### Complexity: Medium-High
### Dependencies: GoalService (already implemented)

---

## Task C-5: Settings Pages Organization

### Objective
Reorganize settings into separate, focused pages for better UX

### Files to Create/Modify
- `views/settings/show.php` - Main settings page with navigation
- `views/settings/profile.php` - Profile/account settings
- `views/settings/notifications.php` - Notification preferences
- `views/settings/preferences.php` - App preferences
- `views/settings/security.php` - Security settings
- `public/assets/css/style.css` - Settings styling

### Settings Pages to Create

#### 1. Profile Settings (`profile.php`)
```html
- Name
- Email
- Avatar upload
- Bio/Description
- Save button
```

#### 2. Notification Settings (`notifications.php`)
```html
- Email notifications (on/off)
- Budget alerts (on/off)
- Goal reminders (on/off)
- Weekly reports (on/off)
- Monthly reports (on/off)
- Alert frequency (immediate, daily, weekly)
```

#### 3. Preferences Settings (`preferences.php`)
```html
- Currency (dropdown)
- Date format (dropdown)
- Theme (light/dark toggle)
- Language (dropdown)
- Timezone (dropdown)
- Items per page (number)
```

#### 4. Security Settings (`security.php`)
```html
- Change password form
- Two-factor authentication toggle
- Session timeout setting
- Login notifications toggle
- Active sessions list
- Logout all other sessions button
- Data export button
- Data import button
- Delete account (dangerous zone)
```

#### 5. Settings Navigation
```html
Sidebar or tabs:
- Profile
- Notifications
- Preferences
- Security

Current page highlighted
```

### CSS Classes Needed
- `.settings-container` - Main container
- `.settings-nav` - Navigation bar/sidebar
- `.settings-nav-item` - Nav item
- `.settings-nav-item.active` - Active nav item
- `.settings-content` - Main content area
- `.settings-section` - Section within page
- `.form-group` - Form field grouping

### Success Criteria
- ✓ All settings accessible
- ✓ Navigation clear
- ✓ Forms intuitive
- ✓ Mobile responsive
- ✓ Data saves correctly

### Time Estimate: 3-4 hours
### Complexity: Medium
### Dependencies: UserSettingsService (already implemented)

---

## Task C-6: Responsive Design & Mobile Optimization

### Objective
Ensure all views work perfectly on mobile, tablet, and desktop

### What to Check

#### 1. Mobile Breakpoints (in CSS)
```css
/* Phone: < 480px */
/* Tablet: 480px - 768px */
/* Desktop: > 768px */
```

#### 2. Navigation on Mobile
- Hamburger menu (already implemented)
- Collapse long forms
- Stack content vertically
- Use mobile-friendly spacing

#### 3. Touch Targets
- Minimum 48px height/width
- Adequate spacing between buttons
- Easy to tap on small screens

#### 4. Table Display on Mobile
- Stack into cards
- Use horizontal scroll if needed
- Prioritize important columns

#### 5. Forms on Mobile
- One field per row
- Larger input fields
- Mobile keyboard optimization
- Clear submit buttons

### Files to Modify
- `public/assets/css/style.css` - Add media queries
- All view templates - Responsive classes

### Tasks

#### Task C-6.1: Mobile Navigation
```
Ensure:
- Hamburger menu works
- Menu closes when item clicked
- Navigation is touch-friendly
- Logo/branding visible on mobile
```

#### Task C-6.2: Mobile Forms
```
Ensure:
- Inputs are full width
- Labels clear
- Inputs are large enough
- Submit button is obvious
- Error messages visible
```

#### Task C-6.3: Mobile Tables
```
Ensure:
- Tables convert to cards on small screens
- Key data is visible
- Actions are accessible
- Sortable columns work
```

#### Task C-6.4: Mobile Charts/Graphs
```
Ensure:
- Charts scale down
- Legend is readable
- Tooltips work on mobile
- Sufficient spacing
```

### Mobile Testing Checklist
- [ ] Test on mobile browser (Chrome DevTools)
- [ ] Test on tablet size
- [ ] Test portrait and landscape
- [ ] Test touch interactions
- [ ] Test forms on mobile
- [ ] Test navigation
- [ ] Check loading states
- [ ] Verify accessibility

### Time Estimate: 4-5 hours
### Complexity: Medium
### Dependencies: All views and CSS

---

## Support & Questions

### When to Ask Claude
- "How should I structure this component?"
- "Is this CSS approach correct?"
- "What data will be available?"
- "Should this be a separate file?"
- "How does this relate to other features?"

### Common Patterns to Follow
1. Look at existing views for examples
2. Use existing CSS classes
3. Follow HTML structure patterns
4. Use component naming conventions
5. Keep files organized

### Testing Your Work
1. Review in browser
2. Check on mobile (DevTools)
3. Test form submissions
4. Test button clicks
5. Verify links work

---

## Submission Process

When you complete a task:

1. **Tell Claude**: Task name and completion status
2. **Show work**: Describe what you created
3. **Test results**: What you tested and verified
4. **Issues**: Any problems encountered
5. **Submit for review**: Share file changes

Claude will:
- Review code quality
- Test functionality
- Check mobile responsiveness
- Integrate with backend
- Approve or suggest changes

---

## Getting Started

**Recommended Order**:
1. C-1: Transaction Split Display (foundation)
2. C-2: Budget Alerts UI (similar patterns)
3. C-3: Investment Portfolio (chart/visualization)
4. C-4: Goals Management (comprehensive)
5. C-5: Settings Pages (organization)
6. C-6: Mobile Optimization (final polish)

**First Steps**:
1. Read this file completely
2. Look at existing views in `views/` folder
3. Review existing CSS in `public/assets/css/style.css`
4. Ask Claude any questions
5. Start Task C-1

---

**You've got this! Create amazing UIs! 🎨**
