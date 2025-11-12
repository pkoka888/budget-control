<?php

namespace App\Services;

use PDO;

/**
 * Invitation Service
 *
 * Manages household member invitations
 */
class InvitationService
{
    private PDO $db;
    private HouseholdService $householdService;
    private NotificationService $notificationService;
    private ?EmailService $emailService = null;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->householdService = new HouseholdService($db);
        $this->notificationService = new NotificationService($db);
    }

    public function setEmailService(EmailService $emailService): void
    {
        $this->emailService = $emailService;
    }

    /**
     * Create invitation
     */
    public function createInvitation(
        int $householdId,
        int $invitedBy,
        string $inviteeEmail,
        string $role,
        ?string $message = null,
        int $expiryDays = 7
    ): array {
        // Validate role
        if (!in_array($role, ['partner', 'viewer', 'child'])) {
            throw new \Exception("Invalid role. Cannot invite as 'owner'.");
        }

        // Check if already member
        $stmt = $this->db->prepare("
            SELECT hm.* FROM household_members hm
            JOIN users u ON hm.user_id = u.id
            WHERE hm.household_id = ? AND u.email = ? AND hm.is_active = 1
        ");
        $stmt->execute([$householdId, $inviteeEmail]);
        if ($stmt->fetch()) {
            throw new \Exception("User is already a member of this household");
        }

        // Check for pending invitation
        $stmt = $this->db->prepare("
            SELECT * FROM household_invitations
            WHERE household_id = ? AND invitee_email = ? AND status = 'pending'
        ");
        $stmt->execute([$householdId, $inviteeEmail]);
        if ($stmt->fetch()) {
            throw new \Exception("Pending invitation already exists for this email");
        }

        // Generate secure token
        $token = bin2hex(random_bytes(32));

        // Calculate expiry
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryDays} days"));

        // Get permission level for role
        $permissionService = new PermissionService($this->db);
        $permissionLevel = $permissionService->getPermissionLevelForRole($role);

        // Create invitation
        $stmt = $this->db->prepare("
            INSERT INTO household_invitations
            (household_id, invited_by, invitee_email, role, permission_level, invitation_token, message, expires_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $householdId,
            $invitedBy,
            $inviteeEmail,
            $role,
            $permissionLevel,
            $token,
            $message,
            $expiresAt
        ]);

        $invitationId = (int)$this->db->lastInsertId();

        // Send invitation email
        $this->sendInvitationEmail($householdId, $invitedBy, $inviteeEmail, $token, $role, $message);

        return [
            'id' => $invitationId,
            'token' => $token,
            'expires_at' => $expiresAt
        ];
    }

    /**
     * Accept invitation
     */
    public function acceptInvitation(string $token, int $userId): bool
    {
        // Get invitation
        $stmt = $this->db->prepare("
            SELECT * FROM household_invitations
            WHERE invitation_token = ? AND status = 'pending'
        ");
        $stmt->execute([$token]);
        $invitation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invitation) {
            throw new \Exception("Invalid or expired invitation");
        }

        // Check expiry
        if (strtotime($invitation['expires_at']) < time()) {
            $this->updateInvitationStatus($invitation['id'], 'expired');
            throw new \Exception("Invitation has expired");
        }

        // Check if user email matches
        $stmt = $this->db->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user['email'] !== $invitation['invitee_email']) {
            throw new \Exception("Invitation email does not match your account");
        }

        // Add user to household
        $this->householdService->addMember(
            $invitation['household_id'],
            $userId,
            $invitation['role'],
            $invitation['permission_level']
        );

        // Update invitation status
        $stmt = $this->db->prepare("
            UPDATE household_invitations
            SET status = 'accepted', invitee_user_id = ?, accepted_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$userId, $invitation['id']]);

        // Notify inviter
        $household = $this->householdService->getHousehold($invitation['household_id']);
        $this->notificationService->create(
            $invitation['household_id'],
            $invitation['invited_by'],
            'invitation',
            'Invitation Accepted',
            "{$user['email']} has joined {$household['name']}",
            'normal',
            ['action_url' => '/household/' . $invitation['household_id']]
        );

        return true;
    }

    /**
     * Decline invitation
     */
    public function declineInvitation(string $token): bool
    {
        return $this->updateInvitationStatusByToken($token, 'declined');
    }

    /**
     * Cancel invitation
     */
    public function cancelInvitation(int $invitationId, int $userId): bool
    {
        $stmt = $this->db->prepare("
            SELECT * FROM household_invitations
            WHERE id = ? AND invited_by = ?
        ");
        $stmt->execute([$invitationId, $userId]);
        $invitation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invitation) {
            throw new \Exception("Invitation not found or you don't have permission to cancel it");
        }

        return $this->updateInvitationStatus($invitationId, 'cancelled');
    }

    /**
     * Get household invitations
     */
    public function getHouseholdInvitations(int $householdId, ?string $status = 'pending'): array
    {
        $where = "household_id = ?";
        $params = [$householdId];

        if ($status) {
            $where .= " AND status = ?";
            $params[] = $status;
        }

        $stmt = $this->db->prepare("
            SELECT hi.*, u.username as invited_by_username
            FROM household_invitations hi
            JOIN users u ON hi.invited_by = u.id
            WHERE {$where}
            ORDER BY hi.created_at DESC
        ");

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user invitations
     */
    public function getUserInvitations(string $email, string $status = 'pending'): array
    {
        $stmt = $this->db->prepare("
            SELECT hi.*, h.name as household_name, u.username as invited_by_username
            FROM household_invitations hi
            JOIN households h ON hi.household_id = h.id
            JOIN users u ON hi.invited_by = u.id
            WHERE hi.invitee_email = ? AND hi.status = ?
            ORDER BY hi.created_at DESC
        ");

        $stmt->execute([$email, $status]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update invitation status
     */
    private function updateInvitationStatus(int $invitationId, string $status): bool
    {
        $stmt = $this->db->prepare("
            UPDATE household_invitations
            SET status = ?
            WHERE id = ?
        ");

        return $stmt->execute([$status, $invitationId]);
    }

    /**
     * Update invitation status by token
     */
    private function updateInvitationStatusByToken(string $token, string $status): bool
    {
        $stmt = $this->db->prepare("
            UPDATE household_invitations
            SET status = ?
            WHERE invitation_token = ?
        ");

        return $stmt->execute([$status, $token]);
    }

    /**
     * Send invitation email
     */
    private function sendInvitationEmail(int $householdId, int $invitedBy, string $inviteeEmail, string $token, string $role, ?string $message): void
    {
        if (!$this->emailService) {
            return;
        }

        $household = $this->householdService->getHousehold($householdId);

        $stmt = $this->db->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$invitedBy]);
        $inviter = $stmt->fetch(PDO::FETCH_ASSOC);

        $acceptUrl = $_ENV['APP_URL'] . "/invitation/accept?token={$token}";

        $body = "{$inviter['username']} has invited you to join the household '{$household['name']}' as a {$role}.\n\n";

        if ($message) {
            $body .= "Message: {$message}\n\n";
        }

        $body .= "Click the link below to accept:\n{$acceptUrl}\n\n";
        $body .= "This invitation will expire in 7 days.";

        try {
            $this->emailService->send(
                $inviteeEmail,
                "You've been invited to join {$household['name']}",
                $body
            );
        } catch (\Exception $e) {
            error_log("Failed to send invitation email: " . $e->getMessage());
        }
    }

    /**
     * Expire old invitations
     */
    public function expireOldInvitations(): int
    {
        $stmt = $this->db->prepare("
            UPDATE household_invitations
            SET status = 'expired'
            WHERE status = 'pending' AND expires_at < CURRENT_TIMESTAMP
        ");

        $stmt->execute();
        return $stmt->rowCount();
    }
}
