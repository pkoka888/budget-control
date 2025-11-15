# UX/UI Testing & Debugging Workflow

**Version:** 1.0
**Last Updated:** 2025-11-15

---

## Overview

This document describes how the UX Tester, UI Tester, and Debugger agents collaborate to maintain excellent user experience and visual quality in the Budget Control application.

---

## Agent Roles

### UX Tester Agent
**Focus:** User flows, interaction patterns, performance
**Tests:** Complete user journeys, responsive design, Core Web Vitals
**Output:** UX issue reports, performance metrics, user flow breakdowns

### UI Tester Agent
**Focus:** Visual design, accessibility, design system
**Tests:** Visual regression, WCAG compliance, keyboard navigation
**Output:** Accessibility violations, visual diffs, design system violations

### Debugger Agent
**Focus:** Issue triage, root cause analysis, fix coordination
**Tests:** Bug reproduction, regression testing, integration validation
**Output:** Bug reports, fix recommendations, test cases

---

## Workflow Diagrams

### 1. New Feature Development Flow

```
┌─────────────────────────────────────────────────────────────────┐
│ Developer Agent: Implements new Budget Alert feature            │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│ UX Tester: Tests user flow for creating and viewing alerts     │
│                                                                 │
│ Tests:                                                          │
│  ✓ User can create alert in < 30 seconds                       │
│  ✓ Alert notification appears within 1 second                  │
│  ✓ Mobile: Alert card is touch-friendly                        │
│  ✗ ISSUE: No loading state when creating alert                 │
│  ✗ ISSUE: Alert threshold input accepts negative numbers       │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│ UI Tester: Validates visual design and accessibility           │
│                                                                 │
│ Tests:                                                          │
│  ✓ Alert card matches design system                            │
│  ✓ Typography follows scale                                    │
│  ✗ ISSUE: Missing ARIA label on threshold input                │
│  ✗ ISSUE: Alert icon has insufficient color contrast           │
│  ✗ ISSUE: Modal cannot be closed with keyboard                 │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│ Debugger Agent: Triages and assigns issues                     │
│                                                                 │
│ Issue #1: No loading state (Priority: P1)                      │
│   → Assign to: Developer Agent                                 │
│   → Files: BudgetAlertController.php, assets/js/alerts.js      │
│                                                                 │
│ Issue #2: Negative number validation (Priority: P1)            │
│   → Assign to: Developer Agent                                 │
│   → Files: views/budgets/alerts/create.php                     │
│                                                                 │
│ Issue #3: Missing ARIA label (Priority: P0 - Accessibility)    │
│   → Assign to: Frontend UI Agent                               │
│   → Files: views/budgets/alerts/create.php                     │
│                                                                 │
│ Issue #4: Color contrast (Priority: P0 - Accessibility)        │
│   → Assign to: Frontend UI Agent                               │
│   → Files: public/assets/css/alerts.css                        │
│                                                                 │
│ Issue #5: Keyboard navigation (Priority: P1 - Accessibility)   │
│   → Assign to: Frontend UI Agent                               │
│   → Files: assets/js/modal.js                                  │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│ Developer/Frontend Agents: Fix issues                          │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│ UX/UI Testers: Re-run tests to verify fixes                    │
│                                                                 │
│ All tests passing ✓                                            │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
                     Feature Merged
```

---

### 2. User-Reported Bug Flow

```
┌─────────────────────────────────────────────────────────────────┐
│ User Report: "Budget exceeded notification not visible on      │
│              mobile"                                            │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│ UX Tester: Reproduces issue with mobile viewport test          │
│                                                                 │
│ test('budget notification on mobile', async ({ page }) => {    │
│   await page.setViewportSize({ width: 375, height: 667 });     │
│   await loginAsTestUser(page);                                 │
│   await page.goto('/budgets');                                 │
│                                                                 │
│   // Trigger budget exceeded state                             │
│   await simulateBudgetExceeded();                              │
│                                                                 │
│   const notification = page.locator('.notification');          │
│   await expect(notification).toBeVisible(); // FAILS           │
│ });                                                             │
│                                                                 │
│ Result: ✗ Notification div is rendered but off-screen          │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│ UI Tester: Identifies root cause                               │
│                                                                 │
│ Visual inspection:                                              │
│   - Screenshot shows notification at x: -999px                 │
│   - CSS: .notification { position: absolute; left: -999px; }   │
│                                                                 │
│ Root cause: Notification positioned off-screen for desktop,    │
│             no mobile-specific positioning                      │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│ Debugger Agent: Creates detailed bug report                    │
│                                                                 │
│ BUG #234: Budget notification not visible on mobile            │
│                                                                 │
│ Severity: High                                                  │
│ Priority: P1                                                    │
│ Category: Responsive Design                                    │
│                                                                 │
│ Affected: Mobile viewports < 768px                             │
│                                                                 │
│ Root Cause:                                                     │
│   File: public/assets/css/notifications.css                    │
│   Line: 45                                                      │
│   Code: .notification { position: absolute; left: -999px; }    │
│                                                                 │
│ Fix:                                                            │
│   Add responsive positioning for mobile:                       │
│   @media (max-width: 768px) {                                  │
│     .notification {                                             │
│       position: fixed;                                          │
│       top: 1rem;                                                │
│       left: 1rem;                                               │
│       right: 1rem;                                              │
│       z-index: 50;                                              │
│     }                                                            │
│   }                                                             │
│                                                                 │
│ Test Case: tests/ux/responsive/notifications.spec.js           │
│                                                                 │
│ Assigned to: Frontend UI Agent                                 │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│ Frontend UI Agent: Implements fix                              │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│ UX Tester: Verifies fix across all mobile viewports            │
│                                                                 │
│ ✓ iPhone SE (375x667)                                          │
│ ✓ iPhone 14 (390x844)                                          │
│ ✓ iPad Mini (768x1024)                                         │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
                      Bug Resolved
```

---

### 3. Accessibility Audit Flow

```
┌─────────────────────────────────────────────────────────────────┐
│ Scheduled: Weekly accessibility audit                          │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│ UI Tester: Runs comprehensive accessibility scan               │
│                                                                 │
│ Pages scanned:                                                  │
│  ✓ Dashboard - 0 violations                                    │
│  ✓ Transactions list - 0 violations                            │
│  ✗ Transaction form - 3 violations                             │
│  ✗ Budget creation - 2 violations                              │
│  ✓ Reports - 0 violations                                      │
│                                                                 │
│ Total violations: 5 (3 serious, 2 moderate)                    │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│ UI Tester: Categorizes violations                              │
│                                                                 │
│ Violation #1: Missing form labels (Serious)                    │
│   Rule: WCAG 4.1.2 - label                                     │
│   Elements: 3 inputs in transaction form                       │
│   Impact: Screen reader users cannot identify fields           │
│                                                                 │
│ Violation #2: Low color contrast (Serious)                     │
│   Rule: WCAG 1.4.3 - color-contrast                            │
│   Elements: Submit button text                                 │
│   Ratio: 3.2:1 (Required: 4.5:1)                               │
│                                                                 │
│ Violation #3: Missing landmark (Moderate)                      │
│   Rule: WCAG 1.3.1 - region                                    │
│   Elements: Main content area                                  │
│   Impact: Screen reader navigation less efficient              │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│ Debugger Agent: Creates accessibility tickets                  │
│                                                                 │
│ Ticket #A1: Add labels to transaction form inputs (P0)         │
│ Ticket #A2: Fix submit button contrast (P0)                    │
│ Ticket #A3: Add main landmark to layout (P1)                   │
│ Ticket #A4: Add labels to budget form inputs (P0)              │
│ Ticket #A5: Add ARIA live region for notifications (P1)        │
│                                                                 │
│ All assigned to: Frontend UI Agent                             │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│ Frontend UI Agent: Fixes all violations                        │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│ UI Tester: Re-runs accessibility audit                         │
│                                                                 │
│ All pages: 0 violations ✓                                      │
│ WCAG 2.1 Level AA: Compliant ✓                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Communication Protocols

### UX Tester → Debugger Agent

**Format:**
```markdown
@debugger UX Issue Found

**Type:** User Flow Friction
**Severity:** Medium
**Page:** /transactions/add
**Flow:** Transaction Creation

**Issue:**
User must manually scroll to submit button on mobile viewport.

**Expected:**
Submit button should be sticky at bottom of viewport or form should be shorter.

**Evidence:**
- Viewport: 375x667 (iPhone SE)
- Form height: 850px
- Requires scroll: 183px to reach submit button
- Test: tests/ux/responsive/transaction-form.spec.js:45

**Impact:**
Increased time to complete transaction (measured: 12s vs expected 5s)

**Recommendation:**
1. Make submit button sticky on mobile
2. Or reduce form height with collapsible sections
3. Or add floating action button

**Assigned to:** Frontend UI Agent
**Priority:** P2
```

### UI Tester → Debugger Agent

**Format:**
```markdown
@debugger Accessibility Violation

**Type:** WCAG 2.1 Violation
**Rule:** 4.1.2 Name, Role, Value (Level A)
**Severity:** Critical
**Page:** /budgets/create

**Violation:**
Input field missing accessible name

**Element:**
<input type="number" name="alert_threshold" class="form-control">

**Impact:**
Screen reader announces "Edit text" without identifying purpose.
Users cannot determine what value to enter.

**Fix Required:**
```html
<label for="alert_threshold">Alert Threshold (%)</label>
<input type="number" id="alert_threshold" name="alert_threshold" class="form-control">
```

**File:** views/budgets/create.php:67
**Test:** tests/ui/accessibility/forms.spec.js:89
**Priority:** P0 (Accessibility blocker)
**Assigned to:** Frontend UI Agent
```

### Debugger Agent → UX/UI Testers

**Format:**
```markdown
@ux-tester @ui-tester Fix Implemented - Please Verify

**Issue:** #234 - Budget notification not visible on mobile
**Fix:** Added responsive positioning for mobile viewports
**PR:** #456

**Files Changed:**
- public/assets/css/notifications.css

**Verification Needed:**

@ux-tester:
- [ ] Notification visible on all mobile viewports
- [ ] No layout shift when notification appears
- [ ] Notification dismissible with touch
- [ ] Animation smooth on low-end devices

@ui-tester:
- [ ] Notification matches design system
- [ ] Color contrast meets WCAG AA
- [ ] Accessible close button (keyboard + screen reader)
- [ ] Visual regression test passes

**Test branch:** fix/mobile-notifications
**Preview:** http://staging.okamih.cz/budgets
```

---

## Test Execution Schedule

### Continuous (On every commit)
- **UX Tester:** Critical user flow smoke tests (5 min)
- **UI Tester:** Accessibility scan of changed pages (3 min)

### Daily (Nightly build)
- **UX Tester:** Full user flow suite (30 min)
- **UI Tester:** Visual regression suite (20 min)
- **UI Tester:** Component state tests (10 min)

### Weekly (Monday morning)
- **UX Tester:** Performance audit (Core Web Vitals)
- **UI Tester:** Full accessibility audit (all pages)
- **UI Tester:** Design system compliance check
- **Debugger Agent:** Issue triage and prioritization

### Monthly
- **UX Tester:** User flow optimization report
- **UI Tester:** Design system documentation update
- **All Agents:** Metrics review and test suite optimization

---

## Metrics Dashboard

### UX Metrics (Tracked by UX Tester)
```json
{
  "user_flows": {
    "onboarding": {
      "avg_completion_time": "3m 45s",
      "success_rate": "94%",
      "drop_off_points": ["email verification", "first budget"]
    },
    "transaction_creation": {
      "avg_time": "8s",
      "error_rate": "3%",
      "mobile_vs_desktop": "12s vs 8s"
    }
  },
  "performance": {
    "dashboard": {
      "lcp": "2.1s",
      "fid": "85ms",
      "cls": "0.08"
    }
  },
  "responsive": {
    "layout_issues": 2,
    "scroll_issues": 0,
    "touch_target_violations": 1
  }
}
```

### UI Metrics (Tracked by UI Tester)
```json
{
  "accessibility": {
    "wcag_aa_compliance": "98%",
    "total_violations": 3,
    "critical_violations": 0,
    "pages_tested": 15
  },
  "visual_regression": {
    "tests_run": 45,
    "failures": 2,
    "diff_threshold": "100px"
  },
  "design_system": {
    "color_violations": 0,
    "typography_violations": 1,
    "spacing_violations": 0
  }
}
```

---

## Issue Priority Matrix

| Severity | Category | Priority | SLA |
|----------|----------|----------|-----|
| Critical | Accessibility (WCAG A) | P0 | Fix immediately |
| Critical | User flow broken | P0 | Fix immediately |
| High | Accessibility (WCAG AA) | P0 | 24 hours |
| High | UX friction (> 2x time) | P1 | 48 hours |
| High | Visual regression | P1 | 48 hours |
| Medium | Performance < threshold | P2 | 1 week |
| Medium | Design system violation | P2 | 1 week |
| Low | Minor UX improvement | P3 | Backlog |

---

## Version History

**v1.0** (2025-11-15)
- Initial workflow documentation
- Agent communication protocols
- Test execution schedule
- Issue priority matrix

---

## Quick Reference

**Invoke UX Tester:**
```bash
# Test specific user flow
@ux-tester Test the transaction creation flow on mobile

# Performance audit
@ux-tester Run Core Web Vitals audit on dashboard

# Responsive test
@ux-tester Verify budget page works on all viewports
```

**Invoke UI Tester:**
```bash
# Accessibility audit
@ui-tester Run WCAG 2.1 audit on transaction form

# Visual regression
@ui-tester Take baseline screenshots for dashboard

# Design system check
@ui-tester Verify button components follow design system
```

**Invoke Debugger:**
```bash
# Issue triage
@debugger Triage UX/UI issues from last test run

# Bug investigation
@debugger Investigate why budget notifications aren't showing on mobile

# Fix verification
@debugger Verify fix for issue #234 and create regression test
```
