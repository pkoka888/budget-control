# Family Sharing Routes Documentation

This document lists all new routes added for the Family Sharing feature set.

## Household Management Routes

### Household Dashboard
- `GET /household` - List all households for current user
- `GET /household/{id}` - View specific household details
- `GET /household/create` - Show create household form
- `POST /household/store` - Create new household
- `PUT /household/{id}/update` - Update household details
- `DELETE /household/{id}` - Delete household (owner only)

### Member Management
- `POST /household/{id}/invite` - Invite member to household
- `PUT /household/{id}/member/{memberId}/role` - Update member role
- `DELETE /household/{id}/member/{memberId}` - Remove member from household

### Settings
- `PUT /household/{id}/settings` - Update household settings

## Invitation Routes

### Invitation Management
- `GET /invitation/accept?token={token}` - Accept invitation (public link)
- `POST /invitation/{id}/decline` - Decline invitation
- `POST /invitation/{id}/cancel` - Cancel invitation (inviter only)
- `GET /household/{id}/invitations` - List household invitations

## Notification Routes

### User Notifications
- `GET /notifications` - Get user notifications
  - Query params: `?unread=1` (filter unread only)
- `POST /notifications/{id}/read` - Mark notification as read
- `POST /notifications/read-all` - Mark all as read
- `POST /notifications/{id}/archive` - Archive notification

## Approval Routes

### Approval Workflow
- `GET /approval/household/{householdId}` - List pending approvals for household
- `GET /approval/{id}` - View specific approval request
- `POST /approval/{id}/approve` - Approve request
  - Body: `notes` (optional)
- `POST /approval/{id}/reject` - Reject request
  - Body: `notes` (optional)
- `POST /approval/{id}/cancel` - Cancel own request
- `GET /approval/my-requests` - Get user's approval requests
  - Query params: `?status=pending|approved|rejected`

## Child Account Routes

### Child Dashboard
- `GET /child-account/{householdId}` - Child account dashboard

### Money Requests
- `POST /child-account/{householdId}/money-request` - Create money request
  - Body: `amount`, `reason`, `category` (optional)
- `GET /child-account/money-requests` - Get child's money requests
  - Query params: `?status=pending|approved|rejected`
- `GET /child-account/parent/requests` - Get parent's pending requests
  - Query params: `?status=pending`
- `POST /child-account/money-request/{id}/approve` - Approve money request (parent)
  - Body: `notes` (optional)
- `POST /child-account/money-request/{id}/reject` - Reject money request (parent)
  - Body: `notes` (optional)

### Chore Management
- `POST /child-account/chore/create` - Create chore (parent)
  - Body: `title`, `description`, `assigned_to`, `reward_amount`, etc.
- `POST /child-account/chore/{choreId}/complete` - Mark chore complete (child)
  - Body: `notes`, `time_taken_minutes`, `photo_proof` (optional)
- `POST /child-account/chore/completion/{id}/verify` - Verify chore completion (parent)
  - Body: `approved` (1/0), `quality_rating` (1-5), `notes` (optional)
- `GET /child-account/{householdId}/pending-verifications` - Get pending verifications (parent)

## Activity Routes

### Activity Feed
- `GET /activity/{householdId}` - Get household activity feed
  - Query params: `?activity_type`, `?entity_type`, `?user_id`, `?limit`, `?offset`
- `GET /activity/entity/{entityType}/{entityId}` - Get activities for specific entity

## Comment Routes

### Comments & Discussions
- `POST /comment/create` - Create comment
  - Body: `household_id`, `entity_type`, `entity_id`, `content`, `parent_comment_id` (optional)
- `PUT /comment/{id}/update` - Update comment
  - Body: `content`
- `DELETE /comment/{id}` - Delete comment
- `GET /comment/entity/{entityType}/{entityId}` - Get comments for entity
- `POST /comment/{id}/reaction` - Add reaction to comment
  - Body: `reaction` (emoji)
- `DELETE /comment/{id}/reaction` - Remove reaction

## Permission Levels

All routes respect the following permission levels:

- **Owner (100)**: Full household control, can delete household, manage all members
- **Partner (75)**: Can manage shared finances, cannot delete household
- **Viewer (50)**: Read-only access to shared data
- **Child (25)**: Limited access with spending limits and approval requirements

## Security Features

1. **Authentication Required**: All routes require authenticated user
2. **Permission Checks**: Each route verifies user has appropriate permission level
3. **Audit Logging**: Sensitive operations are logged
4. **Data Isolation**: Users can only access data in households they belong to
5. **Approval Workflows**: High-value transactions require approval

## Example API Calls

### Create Household
```bash
POST /household/store
Content-Type: application/x-www-form-urlencoded

name=Smith+Family&description=Our+household+finances&currency=CZK
```

### Invite Member
```bash
POST /household/123/invite
Content-Type: application/x-www-form-urlencoded

email=partner@example.com&role=partner&message=Join+our+household
```

### Create Money Request (Child)
```bash
POST /child-account/123/money-request
Content-Type: application/x-www-form-urlencoded

amount=50.00&reason=Need+money+for+school+supplies&category=education
```

### Approve Money Request (Parent)
```bash
POST /child-account/money-request/456/approve
Content-Type: application/x-www-form-urlencoded

notes=Approved+for+school+supplies
```

## Response Format

All API endpoints return JSON responses in the following format:

### Success Response
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": { ... }
}
```

### Error Response
```json
{
  "success": false,
  "error": "Error message description"
}
```

## Next Steps

To implement routing:

1. Add routes to your router configuration file
2. Map routes to controller methods
3. Apply authentication middleware to all routes
4. Add permission checking middleware where needed
5. Test each route with appropriate permissions

## Testing Checklist

- [ ] Owner can create/update/delete household
- [ ] Partner can manage finances but not delete household
- [ ] Viewer can only read data
- [ ] Child can create money requests and complete chores
- [ ] Parent can approve/reject child requests
- [ ] Invitations work correctly
- [ ] Notifications are sent properly
- [ ] Activity feed tracks all actions
- [ ] Comments and mentions work
- [ ] Approval workflows function correctly
