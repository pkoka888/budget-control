# Research: Agent Best Practices & Playwright Testing

**Research Date:** 2025-11-15
**Researcher:** Research Agent
**Sources:** Official Claude Code documentation, Playwright docs, Community best practices

---

## Executive Summary

This research document consolidates best practices for building effective Claude Code agents and implementing comprehensive Playwright testing strategies. Key findings include:

- **Agent Architecture:** Subagents use isolated context windows with custom prompts and scoped tools
- **Communication Patterns:** Automatic delegation via description matching, explicit invocation, and resumable agents
- **Playwright Testing:** Visual regression, accessibility testing with axe-core, and async job polling
- **Multi-Agent Coordination:** Progressive expansion, security-first tool allocation, and model selection economics

---

## 1. Claude Code Agent Best Practices

### 1.1 Core Architecture

**Subagent Fundamentals:**
- Specialized AI assistants with dedicated context windows
- Custom system prompts guide behavior and expertise
- Independent operation with configurable tool access
- Project-level agents override user-level agents

**File Structure:**
```
Project-level:  .claude/agents/{agent-name}.md
User-level:     ~/.claude/agents/{agent-name}.md
```

**YAML Frontmatter Format:**
```markdown
---
name: agent-identifier              # Lowercase letters and hyphens
description: Natural language description of agent's purpose and when to invoke
tools: Tool1, Tool2, Tool3         # Optional: Comma-separated list
model: sonnet                       # Optional: 'sonnet', 'opus', 'haiku', or 'inherit'
---

Your detailed system prompt here...
Include specific instructions, examples, and constraints.
```

### 1.2 Agent Prompt Engineering

**Best Practices:**

1. **Start with Claude Generation**
   - Generate initial agent with Claude, then iterate
   - Provides solid foundation for customization

2. **Include Concrete Examples**
   - LLMs excel at pattern recognition
   - Provide distinct positive and negative examples
   - Show expected input/output pairs

3. **Define Clear Role and Constraints**
   - Establish expertise area explicitly
   - Define capabilities and operational boundaries
   - Use structured checklists for review processes

4. **Write Detailed Prompts**
   - Specific instructions over vague guidance
   - Step-by-step processes where applicable
   - Focus areas and success criteria
   - Evidence-based reasoning requirements

5. **Organize Feedback by Priority**
   - Critical issues first
   - Warnings and suggestions separately
   - Clear action items

**Example Structure:**
```markdown
---
name: code-reviewer
description: Expert reviews code for quality/security after modifications
tools: Read, Grep, Glob, Bash
model: opus
---

You are a Code Reviewer Agent specializing in...

## Review Checklist

### Critical (Must Fix)
- [ ] Security vulnerabilities
- [ ] Data loss risks
- [ ] Authentication bypasses

### Warnings (Should Fix)
- [ ] Performance bottlenecks
- [ ] Error handling gaps
- [ ] Code duplication

### Suggestions (Consider)
- [ ] Readability improvements
- [ ] Naming conventions
- [ ] Documentation additions
```

### 1.3 Tool Selection Strategy

**Progressive Expansion Approach:**
1. Start with carefully scoped toolkit
2. Validate performance at basic tasks
3. Gradually expand as needs are identified
4. Monitor for tool misuse or confusion

**Security-First Principle:**
- "Only grant tools that are necessary for the subagent's purpose"
- Specific agents can be tested and evaluated for reliability
- Restrict sensitive operations to trusted agents
- Leave tools field blank to inherit all available tools

**Tool Management:**
- Use `/agents` command for interactive tool selector
- Lists all Claude Code tools + MCP server tools
- MCP tools inherited when tools field is omitted
- MCP tools excluded if specific tools are listed

### 1.4 Agent Communication Patterns

**Automatic Delegation:**
- Claude Code proactively selects agents based on:
  - Task description matching agent description
  - Available context and capabilities
  - Workload distribution

**Explicit Invocation:**
- User requests: "Use the [name] subagent to [task]"
- Direct control over agent selection
- Useful for specialized or edge case scenarios

**Resumable Agents:**
- Use unique `agentId` values for continued conversations
- Resume via `resume` parameter with agent ID
- Maintains context across multiple invocations
- Ideal for iterative tasks requiring state

**Handoff Protocol:**
When agent encounters tasks outside expertise:
1. Recognize limitation explicitly
2. Recommend appropriate specialist agent
3. Summarize findings/context for handoff
4. Suggest next steps for receiving agent

### 1.5 Model Selection Economics

**Haiku 4.5 for Lightweight Tasks:**
- 90% of Sonnet's agentic performance
- 2x speed improvement
- 3x cost savings
- Ideal for frequently-invoked specialists

**Sonnet for Orchestration:**
- Coordination and quality validation
- Tasks where capability gap matters
- Complex reasoning requirements
- Multi-step planning workflows

**Opus for Expert Review:**
- Deep technical analysis
- Security audits
- Architecture decisions
- Final quality gates

---

## 2. Extended Thinking & Planning Workflows

### 2.1 Extended Thinking Modes

**Trigger Patterns:**
- "think" - Basic extended computation
- "think hard" - Moderate additional processing
- "think harder" - Significant computation budget
- "ultrathink" - Maximum computational resources

**When to Use:**
- Complex architectural decisions
- Security vulnerability analysis
- Multi-constraint optimization problems
- Evaluating multiple solution alternatives

### 2.2 Planning Workflow Pattern

**Critical Principle:**
> "Steps for research and planning are crucial—without them, Claude tends to jump straight to coding."

**Recommended Workflow:**
1. **Research Phase**
   - Understand current state
   - Identify constraints and requirements
   - Survey existing solutions
   - Map dependencies

2. **Planning Phase**
   - Design solution approach
   - Break into actionable steps
   - Identify risks and mitigations
   - Define success criteria

3. **Implementation Phase**
   - Execute with clear targets
   - Iterate against test cases
   - Verify against requirements
   - Document decisions

4. **Verification Phase**
   - Independent agent review
   - Automated testing
   - Manual validation
   - Performance checks

---

## 3. Test-Driven Development with Agents

### 3.1 TDD Workflow

**Effective Pattern:**
1. Write tests for expected behaviors first
2. Confirm tests fail (red phase)
3. Commit the failing tests
4. Implement code to pass tests (green phase)
5. Use independent subagent to verify implementation isn't overfitting
6. Refactor with confidence

**Key Benefit:**
> "This gives Claude a clear target to iterate against—a visual mock, a test case, or another kind of output."

### 3.2 Test Instruction Clarity

**Vague (Less Effective):**
```
"Add tests for foo.py"
```

**Specific (More Effective):**
```
"Write a new test case for foo.py, covering the edge case where the user
is logged out. Avoid mocks. Test should verify the 401 response and
ensure no data leakage."
```

---

## 4. Playwright Testing Best Practices

### 4.1 Visual Regression Testing

**Starting Out:**
1. Target key pages first (login, dashboard, checkout)
2. Establish clean verified baselines
3. Apply masking and noise reduction before finalizing
4. Start small with individual components or Storybook stories
5. Focus on static areas (navigation, headings, logos, buttons)

**Handling Common Challenges:**

**Layout Shifts:**
- Snapshot individual components instead of entire pages
- Reduces failures from unrelated page changes
- Isolate test to specific UI regions

**Dynamic Content:**
- Mask dynamic areas (dates, balances, ads)
- Use mock data via Playwright Mock API
- Focus regression on transformations, not data
- Example masks: timestamps, user-specific data, random IDs

**Test Flakiness:**
```javascript
// Experiment with threshold settings
await expect(page).toHaveScreenshot({
  maxDiffPixels: 100,           // Too high = overlooks changes
  maxDiffPixelRatio: 0.01,      // Too low = test flake
  threshold: 0.2                // Adjust per test if necessary
});
```

**Cross-Machine Consistency:**
- Ensure team runs same Playwright browser version
- Only run visual tests on same hardware (CI only)
- Avoid frequent Playwright upgrades mid-sprint
- Use Docker containers for consistent rendering environment

### 4.2 Accessibility Testing with axe-core

**Installation:**
```bash
npm install @axe-core/playwright
```

**Basic Pattern:**
```javascript
const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

test('homepage accessibility', async ({ page }) => {
  await page.goto('https://your-site.com/');

  // Wait for page to be in desired state
  await page.waitForLoadState('networkidle');

  const results = await new AxeBuilder({ page }).analyze();
  expect(results.violations).toEqual([]);
});
```

**WCAG Compliance Testing:**
```javascript
test('WCAG 2.1 AA compliance', async ({ page }) => {
  await page.goto('/dashboard');

  const results = await new AxeBuilder({ page })
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
    .analyze();

  expect(results.violations).toEqual([]);
});
```

**Targeting Specific Regions:**
```javascript
test('navigation menu accessibility', async ({ page }) => {
  await page.goto('/');

  // Open menu
  await page.getByRole('button', { name: 'Menu' }).click();

  // Wait for dynamic content
  await page.locator('#nav-menu').waitFor();

  const results = await new AxeBuilder({ page })
    .include('#nav-menu')  // Only scan menu
    .analyze();

  expect(results.violations).toEqual([]);
});
```

**Managing Known Issues:**
```javascript
test('with documented exceptions', async ({ page }) => {
  await page.goto('/legacy-feature');

  const results = await new AxeBuilder({ page })
    .exclude('#legacy-widget')           // Exclude entire element
    .disableRules(['duplicate-id'])      // Disable specific rule
    .analyze();

  expect(results.violations).toEqual([]);
});
```

**Test Fixtures for Reusability:**
```javascript
// test-fixtures.js
const base = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

exports.test = base.test.extend({
  makeAxeBuilder: async ({ page }, use) => {
    const builder = () => new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
      .exclude('#third-party-widget')
      .disableRules(['color-contrast']);  // Temporary exception
    await use(builder);
  }
});

exports.expect = base.expect;
```

**Violation Fingerprinting:**
```javascript
function getFingerprints(results) {
  return results.violations.map(v => ({
    rule: v.id,
    targets: v.nodes.map(n => n.target)
  }));
}

test('regression check', async ({ page }) => {
  const results = await new AxeBuilder({ page }).analyze();
  expect(getFingerprints(results)).toMatchSnapshot();
});
```

**Debugging and Reporting:**
```javascript
test('with diagnostics', async ({ page }, testInfo) => {
  const results = await new AxeBuilder({ page }).analyze();

  // Attach full results for investigation
  await testInfo.attach('a11y-results', {
    body: JSON.stringify(results, null, 2),
    contentType: 'application/json'
  });

  expect(results.violations).toEqual([]);
});
```

**Key Limitations:**
> "Axe doesn't intend for its engine to replace human testing for accessibility, as automated testing isn't able to tell you that you have zero accessibility issues on your page."

**Recommended Approach:**
- Combine automated testing with manual accessibility assessments
- Use tools like Accessibility Insights for Web
- Include users with disabilities in testing
- Automated tests catch ~30-50% of accessibility issues

### 4.3 Async Job Polling Pattern

**HTTP 202 Accepted Pattern:**
```javascript
test('bank import with async job polling', async ({ page }) => {
  await page.goto('/bank-import');

  // Trigger async operation
  await page.click('button:has-text("Auto Import All")');

  // Capture 202 Accepted response
  const response = await page.waitForResponse(
    resp => resp.url().includes('/bank-import/auto-import')
         && resp.status() === 202
  );

  const body = await response.json();
  expect(body).toHaveProperty('job_id');
  expect(body.status).toBe('accepted');

  // Poll job status until completed
  let jobStatus = 'pending';
  let attempts = 0;
  const maxAttempts = 30;

  while (jobStatus !== 'completed' && attempts < maxAttempts) {
    await page.waitForTimeout(1000);  // 1 second between polls

    const statusResponse = await page.request.get(
      `/bank-import/job-status?job_id=${body.job_id}`
    );

    const statusData = await statusResponse.json();
    jobStatus = statusData.status;
    attempts++;

    console.log(`Poll attempt ${attempts}: ${jobStatus}`);
  }

  expect(jobStatus).toBe('completed');
  expect(attempts).toBeLessThan(maxAttempts);
});
```

### 4.4 CI/CD Integration

**GitHub Actions Example:**
```yaml
name: E2E Tests
on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3

      - name: Install dependencies
        run: npm install

      - name: Install Playwright browsers
        run: npx playwright install --with-deps

      - name: Start Docker containers
        run: docker-compose up -d

      - name: Wait for app readiness
        run: |
          timeout 60 bash -c 'until curl -f http://localhost:8080/health; do sleep 2; done'

      - name: Run Playwright tests
        run: npm test

      - name: Upload test results
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: playwright-report
          path: playwright-report/

      - name: Upload screenshots on failure
        if: failure()
        uses: actions/upload-artifact@v3
        with:
          name: screenshots
          path: test-results/
```

**Performance Optimization:**
- Use parallel execution and sharding
- Divide test suites into smaller groups
- Run independent tests concurrently
- Cache Playwright browsers between runs

**Scaling Considerations:**
- Manual baseline maintenance becomes messy at scale
- Consider cloud platforms (Percy, Chromatic) for:
  - Automatic baseline management
  - Cross-browser coverage on real devices
  - Better collaboration features
  - Reduced CI/CD maintenance

---

## 5. CLAUDE.md Best Practices

### 5.1 What to Include

**Essential Content:**
- Bash commands (npm scripts, Docker commands)
- Code style guidelines
- Testing instructions
- Repository conventions
- Environment setup
- Unexpected behaviors or quirks

**Location Priority:**
```
1. Repo root:       /var/www/budget-control/CLAUDE.md
2. Parent dirs:     /var/www/CLAUDE.md
3. User home:       ~/.claude/CLAUDE.md
```

### 5.2 Writing Effective CLAUDE.md

**Key Principle:**
> "Your CLAUDE.md files become part of Claude's prompts, so they should be refined like any frequently used prompt."

**Best Practices:**
- Iterate on effectiveness, don't just accumulate content
- Be specific about workflows and patterns
- Include examples of common tasks
- Document non-obvious conventions
- Update as project evolves

**What NOT to Include:**
- Extensive documentation (link to docs instead)
- Detailed API references (summarize key points)
- Redundant information (DRY principle applies)
- Historical context unless relevant to current work

---

## 6. Multi-Agent Coordination

### 6.1 Deployment Strategies

**Parallel Claude Instances:**
- Use separate git checkouts or worktrees
- Run in different terminal tabs
- Independent verification agents review code
- Reduces context switching overhead

**Headless Mode:**
```bash
claude -p "Your prompt here"
```
- Programmatic integration
- Scripting agent workflows
- Batch processing tasks
- CI/CD integration

### 6.2 Independent Verification Pattern

**Workflow:**
1. Developer Agent implements feature
2. Commit implementation to separate branch
3. Testing Agent writes comprehensive tests
4. Reviewer Agent audits code quality
5. Security Agent scans for vulnerabilities
6. All agents report findings independently
7. Coordinator Agent synthesizes feedback

**Benefits:**
- Prevents overfitting to single agent's biases
- Multiple perspectives improve quality
- Catches issues one agent might miss
- Clear separation of concerns

### 6.3 Agent Specialization Matrix

**Recommended Specializations for Budget Control:**

| Agent | Primary Role | Model | Key Tools |
|-------|-------------|-------|-----------|
| Developer | Feature implementation | Sonnet | Read, Write, Edit, Bash |
| Testing | E2E test creation | Haiku 4.5 | Read, Write, Bash |
| Database | Schema & query optimization | Sonnet | Read, Write, Bash |
| Security | Vulnerability scanning | Opus | Read, Grep, Glob |
| Frontend UI | CSS/JS/UX improvements | Haiku 4.5 | Read, Write, Edit |
| DevOps | Deployment & infrastructure | Sonnet | Bash, Read, Write |
| Reviewer | Code quality gates | Opus | Read, Grep, Glob |
| Documentation | Guides & API docs | Haiku 4.5 | Read, Write |

---

## 7. Integration Guidelines for Budget Control

### 7.1 Recommended Agent Updates

**Current Agents to Enhance:**

1. **Testing Agent (`testing.md`)**
   - Add visual regression testing section
   - Include axe-core accessibility patterns
   - Document async job polling for bank imports
   - Add CI/CD integration examples
   - Create test fixtures library

2. **All Agents**
   - Add handoff protocols section
   - Define clear expertise boundaries
   - Specify when to delegate to other agents
   - Include resumable agent patterns for long tasks

### 7.2 New Agent Opportunities

**Visual Testing Specialist:**
```markdown
---
name: visual-testing
description: Specialist in Playwright visual regression and screenshot comparison
tools: Read, Write, Bash
model: haiku
---

You are a Visual Testing Specialist focused on maintaining UI consistency...

## Responsibilities
- Create and maintain visual regression tests
- Configure screenshot comparison thresholds
- Handle dynamic content masking
- Manage baseline image updates
- Investigate visual regressions
```

**Accessibility Specialist:**
```markdown
---
name: accessibility
description: Expert in WCAG compliance and accessibility testing with axe-core
tools: Read, Write, Bash, Grep
model: haiku
---

You are an Accessibility Specialist ensuring WCAG 2.1 AA compliance...

## Responsibilities
- Run axe-core scans on all pages
- Interpret and prioritize violations
- Recommend remediation strategies
- Create accessibility test suites
- Validate keyboard navigation
- Test screen reader compatibility
```

### 7.3 Testing Strategy Enhancements

**Add to Budget Control Test Suite:**

1. **Visual Regression Tests**
   ```javascript
   // tests/visual-regression.spec.js
   test('dashboard visual regression', async ({ page }) => {
     await login(page);
     await page.goto('/dashboard');
     await page.waitForLoadState('networkidle');

     // Mask dynamic content
     await page.addStyleTag({
       content: `
         .current-date, .account-balance, .user-avatar {
           visibility: hidden;
         }
       `
     });

     await expect(page).toHaveScreenshot('dashboard.png', {
       maxDiffPixelRatio: 0.01
     });
   });
   ```

2. **Accessibility Tests**
   ```javascript
   // tests/accessibility.spec.js
   const { test } = require('./fixtures/a11y-fixtures');

   test('all main pages meet WCAG 2.1 AA', async ({ page, makeAxeBuilder }) => {
     const pages = [
       '/dashboard',
       '/transactions',
       '/budgets',
       '/goals',
       '/settings'
     ];

     for (const url of pages) {
       await page.goto(url);
       const results = await makeAxeBuilder().analyze();
       expect(results.violations).toEqual([]);
     }
   });
   ```

3. **Async Job Tests**
   ```javascript
   // tests/bank-import-async.spec.js
   test('auto-import handles large batch correctly', async ({ page }) => {
     await login(page);
     await page.goto('/bank-import');

     // Trigger async import
     const [response] = await Promise.all([
       page.waitForResponse(r => r.url().includes('/auto-import')),
       page.click('button:has-text("Auto Import All")')
     ]);

     expect(response.status()).toBe(202);
     const { job_id } = await response.json();

     // Poll with exponential backoff
     const pollInterval = [1000, 2000, 3000, 5000, 5000];
     let status = 'pending';

     for (const delay of pollInterval) {
       await page.waitForTimeout(delay);
       const statusResp = await page.request.get(`/job-status?id=${job_id}`);
       ({ status } = await statusResp.json());
       if (status === 'completed') break;
     }

     expect(status).toBe('completed');
   });
   ```

---

## 8. Common Pitfalls to Avoid

### 8.1 Agent Design Pitfalls

**Overly Broad Tool Access:**
- ❌ Giving all tools to all agents
- ✅ Progressive expansion with security-first approach

**Vague Agent Descriptions:**
- ❌ "Helps with code stuff"
- ✅ "Expert reviews PHP backend code for security vulnerabilities, SQL injection, and authentication bypasses"

**Insufficient Examples:**
- ❌ Only describing what to do
- ✅ Including positive and negative examples with explanations

**Model Misallocation:**
- ❌ Using Opus for all agents (expensive, slow)
- ✅ Haiku for specialists, Sonnet for coordination, Opus for critical review

### 8.2 Testing Pitfalls

**Visual Regression:**
- ❌ Snapshotting entire pages with dynamic content
- ✅ Component-level snapshots with masked dynamic areas

**Accessibility:**
- ❌ Believing automated tests catch all issues
- ✅ Combining automated + manual + user testing

**Async Jobs:**
- ❌ Using fixed timeouts (brittle)
- ✅ Polling with max attempts and backoff

**Test Organization:**
- ❌ Monolithic test files
- ✅ Grouped by feature with shared fixtures

### 8.3 CLAUDE.md Pitfalls

**Information Overload:**
- ❌ Copying entire documentation into CLAUDE.md
- ✅ Key commands and non-obvious patterns only

**Stale Content:**
- ❌ Never updating CLAUDE.md as project evolves
- ✅ Regular reviews and refinements

**Missing Context:**
- ❌ Assuming Claude knows your project quirks
- ✅ Documenting unexpected behaviors explicitly

---

## 9. Recommended Actions

### 9.1 Immediate (This Sprint)

1. **Enhance Testing Agent**
   - Add visual regression patterns
   - Include axe-core accessibility testing
   - Document async job polling
   - Create test fixtures

2. **Add Handoff Protocols**
   - Define clear expertise boundaries for all agents
   - Add "When to delegate" sections
   - Create agent capability matrix

3. **Create Accessibility Tests**
   - Install `@axe-core/playwright`
   - Create a11y test fixtures
   - Add WCAG 2.1 AA tests for main pages

### 9.2 Short-term (Next 2 Sprints)

1. **Create Specialized Agents**
   - Visual Testing Specialist
   - Accessibility Specialist
   - API Testing Specialist

2. **Implement Visual Regression**
   - Baseline screenshots for key pages
   - Configure masking for dynamic content
   - Add to CI/CD pipeline

3. **Enhance CI/CD**
   - Parallel test execution
   - Automatic baseline updates on approval
   - Visual regression reports in PRs

### 9.3 Long-term (Future Roadmap)

1. **Advanced Testing Infrastructure**
   - Consider Percy or Chromatic for visual testing at scale
   - Cross-browser visual regression
   - Mobile viewport testing

2. **Agent Ecosystem Maturity**
   - Resumable agents for long-running tasks
   - Agent performance metrics
   - Automated agent selection optimization

3. **Comprehensive Test Coverage**
   - Performance testing with Playwright
   - Load testing for async jobs
   - Mobile accessibility testing

---

## 10. Key Takeaways

### For Agent Development

1. **Start with Claude, iterate with humans** - Generate initial agents with Claude, refine based on real usage
2. **Security through scoping** - Only grant necessary tools, expand progressively
3. **Examples drive behavior** - Include concrete positive and negative examples in prompts
4. **Model economics matter** - Use Haiku 4.5 for specialists, save Opus for critical review

### For Playwright Testing

1. **Combine automation with manual testing** - Automated tests catch 30-50% of issues
2. **Wait for state, not time** - Use `waitForLoadState()`, not `waitForTimeout()`
3. **Mask dynamic content** - Visual regression requires stable baselines
4. **Component-level snapshots** - More reliable than full-page screenshots

### For Multi-Agent Systems

1. **Clear specialization** - Each agent owns a distinct domain
2. **Explicit handoffs** - Agents should recommend specialists when needed
3. **Independent verification** - Multiple agents review from different angles
4. **Context management** - Use resumable agents for long-running tasks

---

## 11. Resources

### Official Documentation
- Claude Code Subagents: https://code.claude.com/docs/en/sub-agents
- Claude Code Best Practices: https://www.anthropic.com/engineering/claude-code-best-practices
- Playwright Accessibility Testing: https://playwright.dev/docs/accessibility-testing
- Axe-core Playwright: https://www.npmjs.com/package/@axe-core/playwright

### Community Resources
- ClaudeLog Community Docs: https://claudelog.com/mechanics/custom-agents/
- r/ClaudeAI - Agent sharing and discussion
- Playwright Discord - Testing community support
- GitHub agent collections: Search "claude code agents"

### Tools
- Accessibility Insights for Web: https://accessibilityinsights.io/
- WCAG Guidelines: https://www.w3.org/WAI/WCAG21/quickref/
- Percy (Visual Testing): https://percy.io/
- Chromatic (Storybook Visual Testing): https://www.chromatic.com/

---

**Document Version:** 1.0
**Last Updated:** 2025-11-15
**Next Review:** After implementing initial recommendations
