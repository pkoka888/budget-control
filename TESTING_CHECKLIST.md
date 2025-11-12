# Family Sharing: Manual Testing Checklist

Use this checklist to manually verify all family sharing features work correctly.

## ✅ Pre-Test Setup

- [ ] Database migrations applied
- [ ] Services and controllers copied to budget-app
- [ ] Views created
- [ ] JavaScript files in place
- [ ] Layout updated with notification bell

---

## 🏠 Household Management

### Create Household
- [ ] Can access household creation page
- [ ] Can fill out household form (name, description, currency)
- [ ] Form validation works (required fields)
- [ ] Household created successfully
- [ ] Redirected to household dashboard after creation
- [ ] Creator automatically added as owner
- [ ] Household appears in navigation

### View Household
- [ ] Can view household dashboard
- [ ] Member list displays correctly
- [ ] Stats cards show accurate data
- [ ] Tabs switch correctly (Members/Activity/Settings)
- [ ] Role badges display with correct icons
- [ ] Action buttons appear based on permissions

### Edit Household Settings
- [ ] Can access settings tab
- [ ] Can update household name
- [ ] Can update description
- [ ] Can change currency
- [ ] Changes save successfully
- [ ] Success message displayed

### Leave Household
- [ ] Leave button visible to non-owners
- [ ] Confirmation prompt appears
- [ ] Successfully leaves household
- [ ] Redirected to dashboard
- [ ] Household removed from navigation

---

## 📨 Invitation System

### Send Invitation
- [ ] Can click "Invite Member" button
- [ ] Modal opens with form
- [ ] Can enter email address
- [ ] Can select role (Partner, Viewer, Child)
- [ ] Can add optional message
- [ ] Invitation sends successfully
- [ ] Confirmation message displayed
- [ ] Invitation appears in pending list

### Email Delivery
- [ ] Invitation email received
- [ ] Email contains household name
- [ ] Email contains inviter name
- [ ] Email contains invitation link
- [ ] Email contains role information
- [ ] Link is valid and clickable

### Accept Invitation
- [ ] Invitation link opens acceptance page
- [ ] Page displays household information
- [ ] Page displays role information
- [ ] Page displays inviter information
- [ ] Can accept invitation
- [ ] Can decline invitation
- [ ] Proper feedback message shown
- [ ] Redirected appropriately after action

### Expired Invitations
- [ ] Expired invitation shows error message
- [ ] Cannot accept expired invitation
- [ ] Proper error feedback displayed

### Cancel/Resend Invitations
- [ ] Owner can cancel pending invitations
- [ ] Owner can resend invitations
- [ ] Cancelled invitations cannot be accepted
- [ ] Resent invitations generate new email

---

## 👥 Member Management

### View Members
- [ ] All household members listed
- [ ] Member roles displayed correctly
- [ ] Permission levels shown
- [ ] Join dates visible
- [ ] Active/inactive status shown

### Change Member Role
- [ ] Owner can change member roles
- [ ] Role change modal appears
- [ ] Available roles listed correctly
- [ ] Role changes save successfully
- [ ] Permission level updates automatically
- [ ] Activity logged in feed

### Remove Member
- [ ] Owner can remove members
- [ ] Confirmation prompt appears
- [ ] Member removed successfully
- [ ] Member loses household access
- [ ] Activity logged

### Permission Enforcement
- [ ] Owner has full access
- [ ] Partner can manage finances
- [ ] Viewer has read-only access
- [ ] Child has limited access
- [ ] Proper error messages for denied actions

---

## 👶 Child Account Features

### Child Dashboard
- [ ] Child can view their dashboard
- [ ] Balance displays correctly
- [ ] Spending limits shown with progress bars
- [ ] Chores list displays
- [ ] Pending requests shown
- [ ] Transaction history accessible

### Money Requests
- [ ] Can open request money modal
- [ ] Can enter amount and reason
- [ ] Validation works (positive amount)
- [ ] Request sends to parent
- [ ] Confirmation message shown
- [ ] Request appears in pending list

### Spending Limits
- [ ] Daily limit enforced
- [ ] Weekly limit enforced
- [ ] Monthly limit enforced
- [ ] Transaction limit enforced
- [ ] Approval threshold works
- [ ] Proper error messages when limit reached

### Allowances
- [ ] Allowance settings configured
- [ ] Allowances process automatically
- [ ] Balance updates correctly
- [ ] Activity logged
- [ ] Notification sent

---

## ⭐ Chore System

### Create Chore (Parent)
- [ ] Can open create chore modal
- [ ] Can fill out chore form
- [ ] Can assign to child
- [ ] Can set reward amount
- [ ] Can set due date
- [ ] Can mark as recurring
- [ ] Can require photo proof
- [ ] Chore saves successfully

### View Chores (Parent)
- [ ] All chores listed
- [ ] Stats cards show correct data
- [ ] Pending verification highlighted
- [ ] Can filter by status
- [ ] Can edit existing chores
- [ ] Can delete chores

### Complete Chore (Child)
- [ ] Child sees assigned chores
- [ ] Can mark chore complete
- [ ] Photo upload works (if required)
- [ ] Can add completion notes
- [ ] Submission goes to parent
- [ ] Status changes to "Pending Verification"

### Verify Chore (Parent)
- [ ] Parent sees pending verifications
- [ ] Can view completion details
- [ ] Can add quality rating (stars)
- [ ] Can add verification notes
- [ ] Can approve and pay reward
- [ ] Balance updates correctly
- [ ] Activity logged
- [ ] Notifications sent

---

## ✅ Approval Workflow

### Create Approval Request
- [ ] Child transaction over threshold requires approval
- [ ] Approval request created automatically
- [ ] Parent receives notification
- [ ] Request appears in approval list

### View Pending Approvals
- [ ] Can access approval page
- [ ] All pending requests listed
- [ ] Request details displayed
- [ ] Requester information shown
- [ ] Amount and reason visible
- [ ] Expiry date shown

### Approve Request
- [ ] Can click approve button
- [ ] Modal opens for notes
- [ ] Can approve without notes
- [ ] Approval processes successfully
- [ ] Transaction created
- [ ] Balance updated
- [ ] Notification sent
- [ ] Activity logged

### Reject Request
- [ ] Can click reject button
- [ ] Modal opens for notes
- [ ] Notes required for rejection
- [ ] Rejection processes successfully
- [ ] Requester notified
- [ ] Activity logged

### Expired Requests
- [ ] Requests expire after timeout
- [ ] Expired requests show proper status
- [ ] Cannot approve expired requests

---

## 📊 Activity Feed

### View Activity
- [ ] Can access activity feed
- [ ] Activities grouped by date
- [ ] Recent activity appears first
- [ ] Activity icons display correctly
- [ ] Activity descriptions clear
- [ ] Timestamps accurate

### Filter Activity
- [ ] Can filter by activity type
- [ ] All filter works
- [ ] Transaction filter works
- [ ] Budget filter works
- [ ] Member filter works
- [ ] Approval filter works
- [ ] Chore filter works
- [ ] Important filter works

### Activity Details
- [ ] Can click to view entity
- [ ] Links navigate correctly
- [ ] Metadata displays properly
- [ ] Important activities highlighted

---

## 🔔 Notifications

### Notification Bell
- [ ] Bell icon displays in header
- [ ] Unread count badge shows
- [ ] Badge updates in real-time
- [ ] Click opens dropdown

### Notification Dropdown
- [ ] Dropdown displays notifications
- [ ] Notifications sorted by date
- [ ] Unread notifications highlighted
- [ ] Can mark individual as read
- [ ] Can mark all as read
- [ ] Can navigate to notification source

### Notification Types
- [ ] Activity notifications work
- [ ] Approval notifications work
- [ ] Invitation notifications work
- [ ] Alert notifications work
- [ ] Achievement notifications work

### Real-time Updates
- [ ] Notifications poll in background
- [ ] New notifications appear automatically
- [ ] Badge count updates automatically
- [ ] Bell animates on new notification

---

## 🔒 Data Visibility

### Visibility Toggle
- [ ] Toggle appears on transaction form
- [ ] Toggle appears on budget form
- [ ] Toggle appears on goal form
- [ ] Can select Private
- [ ] Can select Shared
- [ ] Selection saves correctly

### Private Data
- [ ] Private transactions only visible to creator
- [ ] Private budgets only visible to creator
- [ ] Private goals only visible to creator
- [ ] Other members cannot see private data

### Shared Data
- [ ] Shared transactions visible to household
- [ ] Shared budgets visible to household
- [ ] Shared goals visible to household
- [ ] Visibility badge shows on items

### Bulk Operations
- [ ] Can change visibility of multiple items
- [ ] Changes apply correctly
- [ ] Activity logged

---

## 📱 UI/UX

### Responsive Design
- [ ] Desktop layout works
- [ ] Tablet layout works
- [ ] Mobile layout works
- [ ] Navigation collapses on mobile
- [ ] Modals work on mobile
- [ ] Touch interactions work

### Dark Mode
- [ ] Dark mode toggle works
- [ ] All views support dark mode
- [ ] Colors readable in dark mode
- [ ] Images/icons visible in dark mode

### Loading States
- [ ] Loading spinners show during API calls
- [ ] Skeleton screens where appropriate
- [ ] Disabled buttons during processing

### Error Handling
- [ ] Form validation errors display
- [ ] API errors show user-friendly messages
- [ ] Network errors handled gracefully
- [ ] 404 pages exist
- [ ] 403 permission errors clear

### Accessibility
- [ ] Keyboard navigation works
- [ ] Tab order logical
- [ ] Focus indicators visible
- [ ] ARIA labels present
- [ ] Screen reader friendly

---

## 🔐 Security

### Authentication
- [ ] Must be logged in to access features
- [ ] Session timeout works
- [ ] Logout works correctly

### Authorization
- [ ] Owners can perform all actions
- [ ] Partners cannot delete household
- [ ] Viewers cannot edit data
- [ ] Children have limited access
- [ ] Proper 403 errors for denied actions

### Data Isolation
- [ ] Users only see their households
- [ ] Cannot access other households by URL manipulation
- [ ] Private data not leaked in API responses
- [ ] Audit logs working

### Input Validation
- [ ] SQL injection prevented
- [ ] XSS attacks prevented
- [ ] CSRF protection active
- [ ] File upload validation (photos)

---

## ⚡ Performance

### Page Load Times
- [ ] Dashboard loads < 2s
- [ ] Household page loads < 2s
- [ ] Activity feed loads < 2s
- [ ] Approval page loads < 2s

### Database Performance
- [ ] Queries execute quickly
- [ ] No N+1 query issues
- [ ] Indexes used effectively

### JavaScript Performance
- [ ] No console errors
- [ ] No memory leaks
- [ ] Smooth animations
- [ ] Fast modal open/close

---

## 🐛 Edge Cases

### Empty States
- [ ] No households shows proper message
- [ ] No members shows proper message
- [ ] No activities shows proper message
- [ ] No approvals shows proper message
- [ ] No chores shows proper message
- [ ] No notifications shows proper message

### Boundary Conditions
- [ ] 1 member household works
- [ ] 10 member household works
- [ ] Very long household names handled
- [ ] Very large amounts handled
- [ ] Zero balances handled

### Error Recovery
- [ ] Can retry failed operations
- [ ] Can navigate away from error
- [ ] Session recovery works
- [ ] Database connection recovery

---

## ✅ Post-Test Checklist

- [ ] All critical paths tested
- [ ] No major bugs found
- [ ] Performance acceptable
- [ ] Security verified
- [ ] Documentation updated
- [ ] Ready for production

---

## 📝 Notes

Use this space to document any issues found during testing:

```
Issue 1:
- Description:
- Steps to reproduce:
- Severity:
- Status:

Issue 2:
- Description:
- Steps to reproduce:
- Severity:
- Status:
```

---

## 🎉 Sign-Off

Tester: ___________________
Date: ___________________
Version: ___________________
Status: [ ] PASS  [ ] FAIL  [ ] CONDITIONAL
