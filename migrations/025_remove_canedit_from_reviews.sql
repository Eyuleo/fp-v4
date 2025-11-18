ALTER TABLE reviews
ADD COLUMN can_edit_until TIMESTAMP NULL AFTER comment;