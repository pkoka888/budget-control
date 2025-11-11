# Budget Control - Project Constitution

**Version:** 1.0
**Last Updated:** 2025-11-11
**Status:** Active

---

## 1. Project Vision & Purpose

### Primary Mission
Budget Control is a lightweight, personal finance management application designed for individual home use. The application helps users track expenses, manage budgets, and gain insights into their financial health with the simplicity and clarity of a personal finance expert.

### Core Philosophy
> "Every feature must be functional and serve a real need. No bloat, no complexity for complexity's sake."

### Long-term Vision
- **Phase 1 (Current):** Stable, functional personal finance tracker for home use
- **Phase 2 (Future):** LLM-powered financial tutor/agent that helps users understand their financial status and provides personalized guidance based on actual budget data

---

## 2. Core Principles

### 2.1 Lightweight First
- Minimal dependencies
- SQLite database for simplicity
- Docker containerization for easy deployment
- Personal use optimized (single-user friendly, scales to multi-user)

### 2.2 Functional Increments
- Every development step must deliver working functionality
- No incomplete features in release versions
- Test before merge, test before release

### 2.3 Single Source of Truth
- One authoritative documentation set
- Clear status tracking (what's done, what's pending, what's broken)
- No conflicting information across files

### 2.4 User-Centric Design
- Interface should feel like sitting with a senior finance expert
- Data presented clearly and actionably
- Czech banking integration (George Bank format)

---

## 3. Technical Standards

### 3.1 Technology Stack (Immutable)
- **Backend:** PHP 8.2+
- **Database:** SQLite 3
- **Frontend:** Tailwind CSS v4, Vanilla JavaScript
- **Server:** Apache 2.4
- **Container:** Docker with Docker Compose
- **Testing:** Playwright for E2E tests

### 3.2 Code Quality Standards
- **Security First:** No SQL injection, XSS, or command injection vulnerabilities
- **Error Handling:** All exceptions caught and logged
- **Validation:** User input validated on both client and server
- **Documentation:** Code comments for complex logic, PHPDoc for all public methods

### 3.3 API Standards
- RESTful design principles
- JSON responses for API endpoints
- HTTP status codes used correctly:
  - `200 OK` - Success with immediate result
  - `202 Accepted` - Async job started, poll for status
  - `400 Bad Request` - Invalid input
  - `401 Unauthorized` - Authentication required
  - `404 Not Found` - Resource doesn't exist
  - `500 Internal Server Error` - Server-side failure

### 3.4 Async Pattern for Long Operations
- Large dataset processing returns `202 Accepted` with `job_id`
- Client polls `/job-status?job_id=<id>` for progress
- Jobs persisted in database for crash recovery
- Background processing via CLI tool or cron

---

## 4. Development Workflow

### 4.1 Feature Development Process
1. **Plan:** Define the feature, its purpose, and acceptance criteria
2. **Implement:** Write code following standards
3. **Test:** Playwright E2E tests + manual verification
4. **Document:** Update relevant docs (API.md, FEATURES.md)
5. **Review:** Check security, performance, user experience
6. **Merge:** Only when fully functional and tested

### 4.2 Bug Fix Process
1. **Reproduce:** Verify bug exists and document steps
2. **Root Cause:** Identify why it's happening
3. **Fix:** Implement solution
4. **Test:** Verify fix works and doesn't break anything else
5. **Document:** Note in CHANGELOG if significant

### 4.3 Release Process
1. **Feature Freeze:** No new features, only bug fixes
2. **Full Test Suite:** Run all Playwright tests
3. **Manual Testing:** Critical user workflows
4. **Documentation Update:** README, FEATURES.md, CHANGELOG.md
5. **Version Tag:** Git tag with semantic versioning (v1.0.0)
6. **Deploy:** Docker image build and push

---

## 5. Documentation Structure

### 5.1 Required Documents (Root Level)
- **README.md** - Project overview, quick start, installation
- **CONSTITUTION.md** (this file) - Project governance and principles
- **CHANGELOG.md** - Version history and changes
- **CLAUDE.md** - AI assistant guidelines

### 5.2 Documentation Directory (docs/)
- **ARCHITECTURE.md** - Technical system design
- **FEATURES.md** - Complete feature list with status (✅ Done, 🚧 In Progress, ❌ Broken, 📋 Planned)
- **API.md** - API endpoint documentation
- **DEPLOYMENT.md** - Production deployment guide
- **DATABASE.md** - Schema and migrations guide
- **archive/** - Historical documents for reference only

### 5.3 Documentation Standards
- Keep it current or delete it
- One topic per document
- Use clear examples
- Mark outdated sections prominently
- Archive rather than delete historical docs

---

## 6. Project Organization

### 6.1 Root Directory Rules
**Allowed in Root:**
- Core configuration: `composer.json`, `Dockerfile`, `docker-compose.yml`
- Documentation: `README.md`, `CONSTITUTION.md`, `CHANGELOG.md`, `CLAUDE.md`
- Git: `.gitignore`, `.git/`
- Package management: `node_modules/`, `vendor/`

**NOT Allowed in Root:**
- Test scripts (→ move to `tests/` or `scripts/`)
- Data files (→ move to `data/` or `user-data/`)
- Build artifacts (→ `.gitignore` them)
- Temporary files (→ delete or `.gitignore`)

### 6.2 Directory Structure
```
budget-control/
├── budget-app/          # Main application code
│   ├── src/             # PHP source code
│   ├── public/          # Web-accessible files
│   ├── views/           # Template files
│   ├── database/        # Schema, migrations, SQLite file
│   └── cli/             # Command-line tools
├── docs/                # Consolidated documentation
│   ├── archive/         # Historical docs
│   └── *.md             # Current documentation
├── tests/               # Playwright E2E tests
├── scripts/             # Utility scripts (testing, deployment)
├── data/                # Sample/test data
│   └── bank-json/       # Bank import JSON files
└── user-data/           # Runtime user data (gitignored)
```

---

## 7. Security & Privacy

### 7.1 Security Requirements
- All user input sanitized and validated
- Passwords hashed with `password_hash()` (bcrypt)
- Session-based authentication
- CSRF protection on all forms
- SQL prepared statements (no raw queries)
- File upload validation (type, size, content)

### 7.2 Data Privacy
- User data isolated per user_id
- No data sharing between users
- Bank data stored locally only (SQLite)
- No external API calls with user data (future: optional, opt-in only)

### 7.3 Known Security Considerations
- Application designed for trusted home network use
- Not hardened for public internet exposure (yet)
- Future: Add HTTPS, rate limiting, audit logging for production use

---

## 8. Testing Standards

### 8.1 Testing Requirements
- **All new features** must have E2E tests
- **All bug fixes** must include regression test
- **Test coverage:** Critical user workflows must be tested

### 8.2 Test Types
1. **E2E Tests (Playwright):** User workflows from browser
2. **Manual Testing:** Visual verification, UX validation
3. **Security Testing:** Input validation, XSS, SQL injection attempts

### 8.3 Test Data
- Use anonymized/synthetic data for tests
- Real bank JSON files kept in `data/bank-json/` (gitignored)
- Test scripts in `tests/` or `scripts/`

---

## 9. Version Control

### 9.1 Git Workflow
- **main branch:** Stable, production-ready code only
- **feature branches:** For new features (`feature/bank-import`)
- **bugfix branches:** For bug fixes (`bugfix/csv-export-encoding`)

### 9.2 Commit Standards
- Clear, descriptive commit messages
- Reference issue/task if applicable
- One logical change per commit

### 9.3 What NOT to Commit
- Database files (`*.db`, `*.sqlite`)
- User data or real financial data
- IDE configuration (`.vscode/`, `.idea/`)
- Build artifacts
- Large binary files without good reason
- Secrets, API keys, passwords

---

## 10. Future Roadmap

### 10.1 Phase 1: Stable Release (Current Priority)
- ✅ Fix all known bugs
- ✅ Complete core features
- ✅ Full documentation
- ✅ E2E test coverage
- ✅ Clean codebase
- 🚧 First stable release (v1.0.0)

### 10.2 Phase 2: LLM Financial Tutor/Agent
- Research kilo code approach
- Design conversational interface
- Integrate LLM for financial insights
- Personal finance coaching based on user's actual budget data
- Query interface: "Where did I spend most last month?" → Agent analyzes and responds

### 10.3 Future Enhancements (Maybe)
- Mobile app (if needed)
- Multi-currency support (if needed)
- Investment tracking enhancements
- Budget forecasting with AI
- Export to tax software formats

---

## 11. Decision-Making Authority

### 11.1 Primary Decision Maker
The project owner makes final decisions on:
- Feature priorities
- Technology choices
- Release timing
- Architectural changes

### 11.2 AI Assistant Role
AI assistants (Claude, etc.) should:
- Suggest solutions and alternatives
- Follow this constitution strictly
- Ask for clarification when requirements unclear
- Never make breaking changes without explicit approval
- Prioritize stability and simplicity

### 11.3 When in Doubt
1. Check this CONSTITUTION.md
2. Check docs/FEATURES.md for current status
3. Check CLAUDE.md for AI-specific guidelines
4. Ask the user for clarification

---

## 12. Maintenance & Sustainability

### 12.1 Regular Maintenance Tasks
- Update dependencies quarterly (security patches immediately)
- Review and clean up old documentation
- Archive completed feature branches
- Backup production database regularly

### 12.2 Code Review Checklist
Before any merge:
- [ ] Code follows standards in this document
- [ ] Security vulnerabilities checked
- [ ] Tests pass
- [ ] Documentation updated
- [ ] No debug code or console.logs left in
- [ ] Backwards compatible (or migration provided)

### 12.3 Deprecation Policy
- Mark deprecated features clearly
- Provide migration path
- Keep deprecated features for at least one version
- Document in CHANGELOG.md

---

## 13. Success Metrics

### 13.1 Phase 1 Success Criteria
- Application handles 10,000+ transactions without performance issues
- Zero data loss during imports
- All core features functional and tested
- Documentation complete and accurate
- User can run application with just `docker-compose up`

### 13.2 Long-term Success Criteria
- User actively uses application for personal finance management
- LLM tutor provides valuable insights
- Application requires minimal maintenance
- Clear understanding of financial status after using app

---

## 14. Amendments

### 14.1 How to Amend This Constitution
1. Propose change with rationale
2. Discuss implications
3. Update document
4. Increment version number
5. Note change in CHANGELOG.md

### 14.2 Constitution Supremacy
In case of conflict between this constitution and other documentation:
1. **CONSTITUTION.md** takes precedence
2. Then current code behavior
3. Then other documentation

---

**End of Constitution**

*This document is the foundation of the Budget Control project. All contributors, human and AI, should read and follow these principles.*
