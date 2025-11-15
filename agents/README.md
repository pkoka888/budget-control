# AI Agent Definitions for Budget Control

**Version:** 1.0
**Last Updated:** 2025-11-11

---

## Overview

This directory contains **11 specialized AI agent definitions** designed to work together to complete the Budget Control application. Each agent is an expert in a specific domain and can be invoked by AI coding assistants (Claude Code, Cursor, Windsurf, etc.) to perform specialized tasks.

---

## Available Agents

### Core Development Agents (Existing)

| Agent | File | Role | Primary Focus |
|-------|------|------|---------------|
| **Developer** | `developer.md` | Full-stack developer | PHP/JS development, bug fixes, feature implementation |
| **Database** | `database.md` | Database specialist | Schema design, query optimization, migrations |
| **Testing** | `testing.md` | QA engineer | E2E tests, unit tests, test coverage |
| **Documentation** | `documentation.md` | Technical writer | Docs, guides, API specs |
| **Finance Expert** | `finance-expert.md` | Financial advisor | Domain expertise, validation |

### New Specialized Agents

| Agent | File | Role | Primary Focus |
|-------|------|------|---------------|
| **Security** | `security.md` | Security specialist | CSRF, auth, rate limiting, vulnerabilities |
| **Frontend/UI** | `frontend-ui.md` | UI/UX designer | Accessibility, responsive design, Tailwind CSS |
| **DevOps** | `devops.md` | Infrastructure engineer | CI/CD, deployment, monitoring, backups |
| **LLM Integration** | `llm-integration.md` | AI integration specialist | Connect LLM providers, prompt engineering |
| **Project Manager** | `project-manager.md` | Coordinator | Multi-agent orchestration, planning |
| **Debugger** | `debugger.md` | Hotfix specialist | Emergency fixes, root cause analysis, production issues |

---

## How to Use These Agents

### Option 1: Manual Agent Invocation

When using an AI coding assistant (Claude Code, Cursor, etc.), you can reference an agent's instructions:

```
"Act as the Security Agent defined in agents/security.md and implement CSRF protection for all forms in the application."
```

### Option 2: Context Injection

Add agent definitions to your AI assistant's context:

1. Open the relevant agent file (e.g., `agents/security.md`)
2. Copy the entire content
3. Paste into your AI assistant's context window
4. Ask the assistant to follow those instructions

### Option 3: Automated Agent Orchestration

Use the **Parallel Execution Plan** (`/PARALLEL_EXECUTION_PLAN.md`) to run multiple agents simultaneously:

```
"Project Manager Agent: Execute Phase 1 of the Parallel Execution Plan.
Coordinate Security Agent, DevOps Agent, and Testing Agent to work in parallel."
```

---

## Agent Coordination

The **Project Manager Agent** (`project-manager.md`) orchestrates all other agents. It:

- Assigns tasks to specialized agents
- Manages dependencies between agents
- Tracks progress across all work streams
- Resolves conflicts and blockers
- Ensures quality gates are met

**Always start with the Project Manager Agent when beginning a new phase.**

---

## Parallel Execution Strategy

Agents are designed to work **in parallel** to maximize efficiency:

### Week 1 (Phase 1) - Security & CI/CD
- **Security Agent:** CSRF, password reset, rate limiting
- **DevOps Agent:** CI/CD pipeline, deployment automation
- **Testing Agent:** Fix accessibility tests

### Week 2 (Phase 2) - Missing Features
- **Frontend/UI Agent:** Build missing UIs (automation, splits, recurring, templates)
- **Security Agent:** 2FA, email verification
- **Developer Agent:** Connect UIs to backend

### Week 3 (Phase 3) - AI Features
- **LLM Integration Agent:** Connect LLM provider, build AI APIs
- **Frontend/UI Agent:** AI chat widget, insights panel
- **Developer Agent:** Integration work

### Week 4 (Phase 4) - Production
- **DevOps Agent:** Logging, monitoring, backups, deployment
- **Performance Agent:** Optimization, caching, load testing
- **Security Agent:** Headers, audit logging, pen testing
- **Documentation Agent:** Update all docs

See `/PARALLEL_EXECUTION_PLAN.md` for the complete execution strategy.

---

## Agent Capabilities

### What Each Agent Can Do

#### Security Agent (`security.md`)
- ✅ Implement CSRF protection
- ✅ Add password reset with secure tokens
- ✅ Implement rate limiting
- ✅ Add two-factor authentication (2FA)
- ✅ Configure security headers
- ✅ Perform security audits
- ✅ Implement audit logging

#### Frontend/UI Agent (`frontend-ui.md`)
- ✅ Build UI components with Tailwind CSS
- ✅ Ensure WCAG 2.1 AA accessibility
- ✅ Fix ARIA labels and keyboard navigation
- ✅ Create responsive layouts (mobile-first)
- ✅ Implement loading and error states
- ✅ Build interactive components with vanilla JS

#### DevOps Agent (`devops.md`)
- ✅ Set up GitHub Actions CI/CD
- ✅ Configure automated testing pipelines
- ✅ Implement deployment automation
- ✅ Set up monitoring and alerting
- ✅ Configure automated backups
- ✅ Manage SSL/TLS certificates
- ✅ Create health check endpoints

#### LLM Integration Agent (`llm-integration.md`)
- ✅ Connect to OpenAI, Anthropic, or local LLMs
- ✅ Design effective prompts for financial insights
- ✅ Implement spending analysis AI
- ✅ Build natural language query interface
- ✅ Create AI budget recommendations
- ✅ Implement response caching
- ✅ Optimize token usage and costs

#### Developer Agent (`developer.md`)
- ✅ Implement PHP backend features
- ✅ Build RESTful API endpoints
- ✅ Fix bugs and refactor code
- ✅ Integrate frontend with backend
- ✅ Optimize database queries
- ✅ Follow security best practices

#### Database Agent (`database.md`)
- ✅ Design database schemas
- ✅ Write optimized SQL queries
- ✅ Create and run migrations
- ✅ Add indexes for performance
- ✅ Analyze query performance
- ✅ Implement data integrity constraints

#### Testing Agent (`testing.md`)
- ✅ Write Playwright E2E tests
- ✅ Write PHPUnit unit tests
- ✅ Fix failing accessibility tests
- ✅ Perform manual QA
- ✅ Test security features
- ✅ Load testing and performance testing

#### Documentation Agent (`documentation.md`)
- ✅ Write clear, comprehensive documentation
- ✅ Update API specifications
- ✅ Create user guides and tutorials
- ✅ Document deployment procedures
- ✅ Write code comments
- ✅ Maintain README and CHANGELOG

#### Finance Expert Agent (`finance-expert.md`)
- ✅ Validate financial logic
- ✅ Design budget tracking features
- ✅ Provide financial domain expertise
- ✅ Review AI-generated financial advice
- ✅ Ensure financial calculations are accurate

#### Project Manager Agent (`project-manager.md`)
- ✅ Coordinate all agents
- ✅ Create project plans
- ✅ Track progress and dependencies
- ✅ Manage blockers and conflicts
- ✅ Ensure quality gates are met
- ✅ Communicate status to stakeholders

#### Debugger Agent (`debugger.md`)
- ✅ Emergency hotfixes for production issues
- ✅ Root cause analysis using systematic methodology
- ✅ Database API debugging (PDO vs SQLite3)
- ✅ PHP backend bug fixes
- ✅ JavaScript frontend debugging
- ✅ Security vulnerability patching
- ✅ Multi-agent collaboration for complex issues
- ✅ Regression test creation after fixes

---

## Quick Start Guide

### To Complete the Budget Control App:

1. **Read the Parallel Execution Plan**
   ```
   Open: /PARALLEL_EXECUTION_PLAN.md
   ```

2. **Invoke Project Manager Agent**
   ```
   "Project Manager Agent: Review the current status and begin Phase 1 execution."
   ```

3. **Let Agents Work in Parallel**
   The Project Manager will coordinate:
   - Security Agent → CSRF, password reset, rate limiting
   - DevOps Agent → CI/CD pipeline
   - Testing Agent → Accessibility fixes

4. **Monitor Progress**
   Each agent will report:
   - ✅ Completed tasks
   - 🚧 In-progress tasks
   - ⚠️ Blockers

5. **Proceed Through Phases**
   - **Phase 1 (Week 1):** Security & CI/CD
   - **Phase 2 (Week 2):** Missing UIs & Auth
   - **Phase 3 (Week 3):** LLM Integration
   - **Phase 4 (Week 4):** Production Launch

---

## Agent File Structure

Each agent file contains:

### 1. Agent Overview
- Role and expertise
- Core philosophy
- Key responsibilities

### 2. Technical Expertise
- Technologies and tools
- Domain knowledge
- Specific skills

### 3. Current Status
- What's already done
- What's missing
- Priority gaps

### 4. Priority Tasks
- Phased task breakdown
- Day-by-day work plan
- Dependencies

### 5. Implementation Guides
- Code examples
- Best practices
- Patterns to follow

### 6. Testing & Validation
- How to test work
- Quality criteria
- Success metrics

### 7. Collaboration
- Which agents to work with
- Communication protocols
- Handoff procedures

### 8. Resources
- Documentation links
- Tool references
- Learning materials

---

## Success Metrics

The agents collectively aim to achieve:

- **Feature Completion:** 85% → 100%
- **Test Coverage:** 80% → 90%
- **Security:** 0 critical vulnerabilities
- **Performance:** <2s page load time
- **Accessibility:** WCAG 2.1 AA compliant (95+ score)
- **Uptime:** >99.9% in production
- **Documentation:** 100% complete and accurate
- **Deployment:** Fully automated CI/CD

---

## Troubleshooting

### Agent Not Following Instructions?

1. **Check the agent file** - Ensure instructions are clear
2. **Provide more context** - Give agent access to relevant files
3. **Break down the task** - Smaller tasks are easier to execute
4. **Ask Project Manager** - Let PM coordinate complex tasks

### Dependencies Blocking Progress?

1. **Check dependency graph** in `PARALLEL_EXECUTION_PLAN.md`
2. **Coordinate with Project Manager Agent**
3. **Consider working on other tasks** while waiting
4. **Update status** so other agents know

### Agents Conflicting?

1. **Project Manager resolves conflicts**
2. **Use version control** - Git handles merge conflicts
3. **Communicate in daily standups**
4. **Follow established patterns** in codebase

---

## Contributing New Agents

To add a new specialized agent:

1. **Create agent file** - Use existing agents as templates
2. **Define role and expertise** - What does this agent do?
3. **List technical skills** - What can this agent accomplish?
4. **Identify tasks** - What work is this agent responsible for?
5. **Add to README** - Document in this file
6. **Update execution plan** - Add to parallel execution strategy

---

## Related Documents

- **`/PARALLEL_EXECUTION_PLAN.md`** - Complete 4-week execution plan
- **`/CONSTITUTION.md`** - Project principles and governance
- **`/CLAUDE.md`** - AI assistant guidelines
- **`/docs/FEATURES.md`** - Feature status tracking
- **`/docs/project/README-project.md`** - Project overview

---

## Contact

For questions about agents or coordination:
- Check the relevant agent's `.md` file
- Consult the Project Manager Agent
- Review the Parallel Execution Plan

---

**Last Updated:** 2025-11-15
**Status:** Ready for use
**Total Agents:** 11
**Estimated Project Completion:** 4 weeks with parallel execution
