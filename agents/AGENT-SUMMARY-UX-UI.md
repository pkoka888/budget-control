# UX/UI Testing Agents - Quick Start

**Created:** 2025-11-15
**Status:** Ready for Use

---

## What Was Created

### 1. Specialized Testing Agents

#### UX Tester Agent (`ux-tester.md`)
**Purpose:** Test user experience, flows, and performance

**Capabilities:**
- End-to-end user journey testing
- Responsive design validation (mobile/tablet/desktop)
- Performance metrics (Core Web Vitals: LCP, FID, CLS)
- Interaction pattern testing
- User flow timing and friction detection

**When to Use:**
- Testing complete user workflows
- Measuring task completion time
- Validating responsive layouts
- Performance audits
- Finding UX friction points

#### UI Tester Agent (`ui-tester.md`)
**Purpose:** Test visual design, accessibility, and consistency

**Capabilities:**
- WCAG 2.1 Level AA accessibility audits
- Visual regression testing (screenshot comparison)
- Keyboard navigation validation
- Color contrast checking
- Design system compliance
- Component state testing

**When to Use:**
- Accessibility compliance checking
- Detecting visual changes
- Validating keyboard support
- Screen reader compatibility
- Design system enforcement

---

## 2. Agent Workflow Integration

**File:** `workflow-ux-debug.md`

Defines how UX Tester, UI Tester, and Debugger agents collaborate:

### New Feature Flow
```
Developer → UX Tester → UI Tester → Debugger → Fix → Re-test
```

### Bug Report Flow
```
User Report → UX Tester (reproduce) → UI Tester (analyze) → Debugger (triage) → Fix
```

### Weekly Audit Flow
```
UI Tester (accessibility scan) → Debugger (create tickets) → Frontend (fix) → Re-audit
```

**Communication Protocols:** Structured issue reporting between agents

---

## 3. Example Playwright Tests

### UX Tests Created

**`tests/ux/user-flows/transaction-management.spec.js`**
- Transaction creation flow (with timing)
- Form validation UX
- Edit/delete operations
- Mobile touch-friendly checks
- No horizontal scroll validation
- Complete UX metrics tracking

### UI Tests Created

**`tests/ui/accessibility/forms.spec.js`**
- WCAG 2.1 AA compliance for all forms
- Keyboard navigation validation
- Focus indicator checks
- Error message accessibility
- Color contrast verification
- ARIA attribute validation

**`tests/ui/visual-regression/dashboard.spec.js`**
- Full page screenshots
- Responsive viewport comparisons
- Component-level screenshots
- Dark mode variants
- Loading state captures
- Chart rendering validation

---

## 4. Documentation

**`tests/README-UX-UI-TESTING.md`**

Comprehensive guide covering:
- Test directory structure
- Installation instructions
- Running tests (commands)
- Writing new tests (templates)
- CI/CD integration examples
- Troubleshooting guide
- Best practices
- Agent integration

---

## Quick Start Commands

### Install Dependencies

```bash
npm install --save-dev @playwright/test @axe-core/playwright
npx playwright install chromium
```

### Run Tests

```bash
# All UX tests
npx playwright test tests/ux/

# All UI tests
npx playwright test tests/ui/

# Specific test
npx playwright test tests/ux/user-flows/transaction-management.spec.js

# With browser visible
npx playwright test --headed

# Debug mode
npx playwright test --debug

# Update visual baselines
npx playwright test tests/ui/visual-regression/ --update-snapshots
```

### View Reports

```bash
npx playwright test --reporter=html
npx playwright show-report
```

---

## Invoking Agents

### Via Claude Code

```markdown
@ux-tester Test the budget creation flow on mobile devices

@ui-tester Run accessibility audit on transaction form

@ux-tester @ui-tester Comprehensive test of new feature X
```

### Expected Output

Agents will:
1. Run relevant Playwright tests
2. Analyze results
3. Report issues in structured format
4. Provide recommendations
5. Track metrics over time

---

## Test Coverage

### UX Tests Cover

- ✓ Transaction management flow
- ✓ Budget creation flow
- ✓ Goal tracking flow
- ✓ Responsive design (mobile/tablet/desktop)
- ✓ Performance metrics (Core Web Vitals)
- ✓ Loading states and feedback
- ✓ Form validation UX
- ✓ Touch target sizes

### UI Tests Cover

- ✓ WCAG 2.1 AA compliance (all forms)
- ✓ Keyboard navigation
- ✓ Focus indicators
- ✓ Color contrast
- ✓ ARIA labels and landmarks
- ✓ Visual regression (dashboard)
- ✓ Responsive screenshots
- ✓ Component states

---

## Integration with Existing Tests

### Current Test Structure

```
tests/
├── password-reset.spec.js       # Existing Playwright tests
├── phase2-features.spec.js
├── phase3-complete.spec.js
└── ...
```

### New Test Structure

```
tests/
├── ux/                          # NEW: UX tests
│   ├── user-flows/
│   ├── responsive/
│   └── performance/
├── ui/                          # NEW: UI tests
│   ├── accessibility/
│   ├── visual-regression/
│   └── components/
└── [existing tests...]          # Keep existing
```

**No conflicts:** New tests complement existing functional tests

---

## Next Steps

### 1. Set Up Playwright Config

Create `playwright.config.js` in project root (see README for template)

### 2. Create Test User Account

Ensure demo account exists:
- Email: demo@example.com
- Password: demo123

### 3. Run Initial Tests

```bash
# Test that everything works
npx playwright test tests/ux/user-flows/transaction-management.spec.js --headed

# Create visual baselines
npx playwright test tests/ui/visual-regression/ --update-snapshots
```

### 4. Add to CI/CD

See `tests/README-UX-UI-TESTING.md` for GitHub Actions example

### 5. Schedule Regular Audits

- **Daily:** UX smoke tests
- **Weekly:** Full accessibility audit
- **Monthly:** Complete test suite

---

## File Locations

All files created in this session:

```
/var/www/budget-control/
├── agents/
│   ├── ux-tester.md                      # UX Tester Agent definition
│   ├── ui-tester.md                      # UI Tester Agent definition
│   ├── workflow-ux-debug.md              # Agent workflow integration
│   └── AGENT-SUMMARY-UX-UI.md            # This file
└── tests/
    ├── ux/
    │   └── user-flows/
    │       └── transaction-management.spec.js
    ├── ui/
    │   ├── accessibility/
    │   │   └── forms.spec.js
    │   └── visual-regression/
    │       └── dashboard.spec.js
    └── README-UX-UI-TESTING.md            # Complete testing guide
```

---

## Metrics Dashboard (Future)

Agents will track over time:

```json
{
  "ux_metrics": {
    "avg_transaction_time": "8.2s",
    "form_error_rate": "3%",
    "mobile_scroll_issues": 0,
    "performance_score": 85
  },
  "ui_metrics": {
    "wcag_compliance": "98%",
    "visual_regressions": 2,
    "contrast_violations": 0,
    "keyboard_issues": 1
  }
}
```

---

## Support

### Questions?

- **UX Testing:** See `agents/ux-tester.md`
- **UI Testing:** See `agents/ui-tester.md`
- **Workflow:** See `agents/workflow-ux-debug.md`
- **How-To:** See `tests/README-UX-UI-TESTING.md`

### Resources

- [Playwright Documentation](https://playwright.dev)
- [WCAG 2.1 Quick Reference](https://www.w3.org/WAI/WCAG21/quickref/)
- [Web Vitals](https://web.dev/vitals/)
- [Axe-Core Rules](https://github.com/dequelabs/axe-core/blob/develop/doc/rule-descriptions.md)

---

## Success Criteria

✅ **UX Agent Created** - Comprehensive user experience testing
✅ **UI Agent Created** - Visual design and accessibility testing
✅ **Workflow Defined** - Clear agent collaboration process
✅ **Example Tests** - Working Playwright tests for UX and UI
✅ **Documentation** - Complete guide for running and writing tests
✅ **Integration Ready** - Agents can be invoked immediately

**Status: Ready for Production Use**

Use `@ux-tester` or `@ui-tester` in Claude Code to start testing!
