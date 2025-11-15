# Critical Production Fixes Task

## Objective
Implement the critical production fixes identified in the analysis:

1. **health.php endpoint** (2 hours)
   - Create /budget-app/public/health.php
   - Check database connectivity
   - Check file permissions
   - Return JSON status

2. **Session fixation vulnerability** (1 hour)
   - Implement session_regenerate_id() after login
   - Add session timeout
   - Implement CSRF tokens

3. **File upload path traversal** (1 hour)  
   - Sanitize file paths
   - Validate upload directory
   - Add whitelist validation

4. **Password requirements** (30 min)
   - Minimum 12 characters
   - Require uppercase, lowercase, number, special char
   - Add password strength indicator

5. **Run test suite** and verify all fixes

## Context
- Working directory: /var/www/budget-control/
- Branch: claude/analyze-budget-app-status-011CV2NPjJT4QmidyqZyZZ8u
- Access: Can read/write code, run tests, use git
- Cannot: Restart services, modify Apache config

## Deliverables
- Implemented code changes
- Test results
- Git commits for each fix
- Updated PRODUCTION_READINESS_REPORT.md

