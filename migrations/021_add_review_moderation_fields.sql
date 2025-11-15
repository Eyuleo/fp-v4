-- Add review moderation fields and remove can_edit_until column

-- Add new moderation fields
ALTER TABLE reviews
ADD COLUMN is_hidden TINYINT(1) DEFAULT 0 NOT NULL AFTER student_replied_at,
ADD COLUMN moderation_notes TEXT NULL AFTER is_hidden,
ADD COLUMN moderated_by INT UNSIGNED NULL AFTER moderation_notes,
ADD COLUMN moderated_at TIMESTAMP NULL AFTER moderated_by;

-- Add index on is_hidden for performance
ALTER TABLE reviews
ADD INDEX idx_is_hidden (is_hidden);

-- Add foreign key for moderated_by
ALTER TABLE reviews
ADD CONSTRAINT fk_reviews_moderated_by FOREIGN KEY (moderated_by) REFERENCES users(id);

-- Remove can_edit_until column
ALTER TABLE reviews
DROP COLUMN can_edit_until;
