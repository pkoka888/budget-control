<?php

namespace App\Services;

use PDO;

/**
 * Comment Service
 *
 * Manages comments, discussions, and mentions on financial entities
 */
class CommentService
{
    private PDO $db;
    private NotificationService $notificationService;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->notificationService = new NotificationService($db);
    }

    /**
     * Create comment
     */
    public function create(
        int $householdId,
        int $userId,
        string $entityType,
        int $entityId,
        string $content,
        ?int $parentCommentId = null
    ): int {
        // Extract mentions
        $mentions = $this->extractMentions($content);

        $stmt = $this->db->prepare("
            INSERT INTO comments (household_id, user_id, entity_type, entity_id, parent_comment_id, content, mentions)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $householdId,
            $userId,
            $entityType,
            $entityId,
            $parentCommentId,
            $content,
            !empty($mentions) ? json_encode($mentions) : null
        ]);

        $commentId = (int)$this->db->lastInsertId();

        // Create mentions and notify
        if (!empty($mentions)) {
            $this->createMentions($householdId, $userId, $commentId, $mentions, $content);
        }

        return $commentId;
    }

    /**
     * Update comment
     */
    public function update(int $commentId, int $userId, string $content): bool
    {
        // Verify ownership
        $comment = $this->getComment($commentId);
        if (!$comment || $comment['user_id'] != $userId) {
            throw new \Exception("You can only edit your own comments");
        }

        $mentions = $this->extractMentions($content);

        $stmt = $this->db->prepare("
            UPDATE comments
            SET content = ?, is_edited = 1, edited_at = CURRENT_TIMESTAMP, mentions = ?
            WHERE id = ?
        ");

        $result = $stmt->execute([
            $content,
            !empty($mentions) ? json_encode($mentions) : null,
            $commentId
        ]);

        // Create new mentions
        if ($result && !empty($mentions)) {
            $this->createMentions($comment['household_id'], $userId, $commentId, $mentions, $content);
        }

        return $result;
    }

    /**
     * Delete comment
     */
    public function delete(int $commentId, int $userId): bool
    {
        $comment = $this->getComment($commentId);
        if (!$comment || $comment['user_id'] != $userId) {
            throw new \Exception("You can only delete your own comments");
        }

        $stmt = $this->db->prepare("
            UPDATE comments
            SET is_deleted = 1, deleted_at = CURRENT_TIMESTAMP, content = '[deleted]'
            WHERE id = ?
        ");

        return $stmt->execute([$commentId]);
    }

    /**
     * Get comment
     */
    public function getComment(int $commentId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM comments WHERE id = ? AND is_deleted = 0");
        $stmt->execute([$commentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get entity comments
     */
    public function getEntityComments(string $entityType, int $entityId): array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, u.username, u.avatar
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.entity_type = ? AND c.entity_id = ? AND c.is_deleted = 0
            ORDER BY c.created_at ASC
        ");

        $stmt->execute([$entityType, $entityId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Build threaded structure
        return $this->buildCommentTree($comments);
    }

    /**
     * Add reaction to comment
     */
    public function addReaction(int $commentId, int $userId, string $reaction): bool
    {
        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO comment_reactions (comment_id, user_id, reaction)
            VALUES (?, ?, ?)
        ");

        return $stmt->execute([$commentId, $userId, $reaction]);
    }

    /**
     * Remove reaction
     */
    public function removeReaction(int $commentId, int $userId, string $reaction): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM comment_reactions
            WHERE comment_id = ? AND user_id = ? AND reaction = ?
        ");

        return $stmt->execute([$commentId, $userId, $reaction]);
    }

    /**
     * Get comment reactions
     */
    public function getReactions(int $commentId): array
    {
        $stmt = $this->db->prepare("
            SELECT reaction, COUNT(*) as count, GROUP_CONCAT(user_id) as user_ids
            FROM comment_reactions
            WHERE comment_id = ?
            GROUP BY reaction
        ");

        $stmt->execute([$commentId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Extract @mentions from text
     */
    private function extractMentions(string $content): array
    {
        preg_match_all('/@(\w+)/', $content, $matches);

        if (empty($matches[1])) {
            return [];
        }

        // Get user IDs for usernames
        $placeholders = str_repeat('?,', count($matches[1]) - 1) . '?';
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username IN ({$placeholders})");
        $stmt->execute($matches[1]);

        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');
    }

    /**
     * Create mention records and notify users
     */
    private function createMentions(int $householdId, int $mentioningUserId, int $commentId, array $userIds, string $content): void
    {
        foreach ($userIds as $mentionedUserId) {
            // Skip self-mentions
            if ($mentionedUserId == $mentioningUserId) {
                continue;
            }

            // Create mention record
            $stmt = $this->db->prepare("
                INSERT INTO mentions (household_id, mentioned_user_id, mentioning_user_id, entity_type, entity_id, context_text)
                VALUES (?, ?, ?, 'comment', ?, ?)
            ");

            $contextText = mb_substr($content, 0, 100);
            $stmt->execute([$householdId, $mentionedUserId, $mentioningUserId, $commentId, $contextText]);

            // Notify mentioned user
            $stmt = $this->db->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$mentioningUserId]);
            $mentioner = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->notificationService->create(
                $householdId,
                $mentionedUserId,
                'activity',
                'You were mentioned',
                "{$mentioner['username']} mentioned you in a comment",
                'normal',
                ['action_url' => "/comment/{$commentId}", 'icon' => '@']
            );
        }
    }

    /**
     * Build comment tree structure
     */
    private function buildCommentTree(array $comments): array
    {
        $tree = [];
        $lookup = [];

        // First pass: create lookup
        foreach ($comments as $comment) {
            $comment['replies'] = [];
            $lookup[$comment['id']] = $comment;
        }

        // Second pass: build tree
        foreach ($lookup as $id => $comment) {
            if ($comment['parent_comment_id']) {
                $lookup[$comment['parent_comment_id']]['replies'][] = &$lookup[$id];
            } else {
                $tree[] = &$lookup[$id];
            }
        }

        return $tree;
    }
}
