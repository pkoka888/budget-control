-- Migration 016: Comments & Communication
-- Purpose: Enable discussion threads, comments, mentions, and reactions
-- Features: Comments on transactions/budgets/goals, @mentions, reactions, attachments

-- =============================================================================
-- COMMENTS TABLE
-- =============================================================================
-- Discussion threads on financial entities
CREATE TABLE IF NOT EXISTS comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    entity_type TEXT NOT NULL, -- transaction, budget, goal, account, bill, investment
    entity_id INTEGER NOT NULL,
    parent_comment_id INTEGER, -- For nested replies
    content TEXT NOT NULL,
    is_edited INTEGER DEFAULT 0,
    edited_at DATETIME,
    is_deleted INTEGER DEFAULT 0,
    deleted_at DATETIME,
    mentions TEXT, -- JSON array of user IDs mentioned
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_comment_id) REFERENCES comments(id) ON DELETE CASCADE
);

CREATE INDEX idx_comments_household ON comments(household_id);
CREATE INDEX idx_comments_user ON comments(user_id);
CREATE INDEX idx_comments_entity ON comments(entity_type, entity_id);
CREATE INDEX idx_comments_parent ON comments(parent_comment_id);
CREATE INDEX idx_comments_created ON comments(created_at DESC);

-- =============================================================================
-- COMMENT REACTIONS TABLE
-- =============================================================================
-- Emoji reactions to comments
CREATE TABLE IF NOT EXISTS comment_reactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    comment_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    reaction TEXT NOT NULL, -- emoji: 👍, ❤️, 😂, 😮, 😢, 🎉
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(comment_id, user_id, reaction)
);

CREATE INDEX idx_reactions_comment ON comment_reactions(comment_id);
CREATE INDEX idx_reactions_user ON comment_reactions(user_id);

-- =============================================================================
-- COMMENT ATTACHMENTS TABLE
-- =============================================================================
-- File attachments for comments
CREATE TABLE IF NOT EXISTS comment_attachments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    comment_id INTEGER NOT NULL,
    file_name TEXT NOT NULL,
    file_path TEXT NOT NULL,
    file_type TEXT NOT NULL, -- image, pdf, document
    file_size INTEGER NOT NULL,
    uploaded_by INTEGER NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_attachments_comment ON comment_attachments(comment_id);

-- =============================================================================
-- MENTIONS TABLE
-- =============================================================================
-- Track @mentions for notifications
CREATE TABLE IF NOT EXISTS mentions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER NOT NULL,
    mentioned_user_id INTEGER NOT NULL,
    mentioning_user_id INTEGER NOT NULL,
    entity_type TEXT NOT NULL, -- comment, activity, note
    entity_id INTEGER NOT NULL,
    context_text TEXT, -- Snippet of text containing the mention
    is_read INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (mentioned_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mentioning_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_mentions_household ON mentions(household_id);
CREATE INDEX idx_mentions_mentioned_user ON mentions(mentioned_user_id);
CREATE INDEX idx_mentions_is_read ON mentions(is_read);
CREATE INDEX idx_mentions_created ON mentions(created_at DESC);

-- =============================================================================
-- NOTES TABLE
-- =============================================================================
-- Personal or shared notes on entities
CREATE TABLE IF NOT EXISTS notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    entity_type TEXT NOT NULL,
    entity_id INTEGER NOT NULL,
    title TEXT,
    content TEXT NOT NULL,
    visibility TEXT DEFAULT 'private' CHECK(visibility IN ('private', 'shared')),
    is_pinned INTEGER DEFAULT 0,
    color TEXT, -- For visual organization
    tags TEXT, -- JSON array of tags
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_notes_household ON notes(household_id);
CREATE INDEX idx_notes_user ON notes(user_id);
CREATE INDEX idx_notes_entity ON notes(entity_type, entity_id);
CREATE INDEX idx_notes_visibility ON notes(visibility);
CREATE INDEX idx_notes_pinned ON notes(is_pinned);

-- =============================================================================
-- TAGS TABLE
-- =============================================================================
-- Tagging system for organizing financial data
CREATE TABLE IF NOT EXISTS tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    color TEXT DEFAULT '#3b82f6',
    icon TEXT,
    description TEXT,
    created_by INTEGER NOT NULL,
    usage_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(household_id, name)
);

CREATE INDEX idx_tags_household ON tags(household_id);
CREATE INDEX idx_tags_name ON tags(name);

-- =============================================================================
-- ENTITY TAGS TABLE
-- =============================================================================
-- Many-to-many relationship between entities and tags
CREATE TABLE IF NOT EXISTS entity_tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tag_id INTEGER NOT NULL,
    entity_type TEXT NOT NULL,
    entity_id INTEGER NOT NULL,
    tagged_by INTEGER NOT NULL,
    tagged_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
    FOREIGN KEY (tagged_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(tag_id, entity_type, entity_id)
);

CREATE INDEX idx_entity_tags_tag ON entity_tags(tag_id);
CREATE INDEX idx_entity_tags_entity ON entity_tags(entity_type, entity_id);

-- =============================================================================
-- CONVERSATION THREADS TABLE
-- =============================================================================
-- Group conversations between household members
CREATE TABLE IF NOT EXISTS conversation_threads (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER NOT NULL,
    title TEXT,
    created_by INTEGER NOT NULL,
    is_archived INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_message_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_threads_household ON conversation_threads(household_id);
CREATE INDEX idx_threads_archived ON conversation_threads(is_archived);
CREATE INDEX idx_threads_last_message ON conversation_threads(last_message_at DESC);

-- =============================================================================
-- CONVERSATION PARTICIPANTS TABLE
-- =============================================================================
-- Track who's in each conversation
CREATE TABLE IF NOT EXISTS conversation_participants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    thread_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    last_read_at DATETIME,
    notifications_enabled INTEGER DEFAULT 1,
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES conversation_threads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(thread_id, user_id)
);

CREATE INDEX idx_participants_thread ON conversation_participants(thread_id);
CREATE INDEX idx_participants_user ON conversation_participants(user_id);

-- =============================================================================
-- CONVERSATION MESSAGES TABLE
-- =============================================================================
-- Messages within conversations
CREATE TABLE IF NOT EXISTS conversation_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    thread_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    message TEXT NOT NULL,
    is_edited INTEGER DEFAULT 0,
    edited_at DATETIME,
    is_deleted INTEGER DEFAULT 0,
    deleted_at DATETIME,
    reply_to_message_id INTEGER,
    attachments TEXT, -- JSON array
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES conversation_threads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reply_to_message_id) REFERENCES conversation_messages(id) ON DELETE SET NULL
);

CREATE INDEX idx_messages_thread ON conversation_messages(thread_id);
CREATE INDEX idx_messages_user ON conversation_messages(user_id);
CREATE INDEX idx_messages_created ON conversation_messages(created_at DESC);

-- =============================================================================
-- NOTES
-- =============================================================================
-- Communication Features:
-- 1. Comments on any financial entity with nested replies
-- 2. @mentions to notify specific household members
-- 3. Emoji reactions for quick feedback
-- 4. File attachments on comments
-- 5. Personal and shared notes
-- 6. Tagging system for organization
-- 7. Direct conversation threads between members
--
-- Privacy:
-- - Comments on private entities are private
-- - Comments on shared entities visible to all members
-- - Personal notes always private
-- - Conversations respect participant list
