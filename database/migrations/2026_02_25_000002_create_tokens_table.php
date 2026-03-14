<?php

return new class {
    public function up(): void
    {
        qi('CREATE TABLE IF NOT EXISTS tokens (
            id         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
            user_id    INT UNSIGNED  NOT NULL,
            token      CHAR(36)      NOT NULL UNIQUE,
            last_used_at TIMESTAMP     NULL DEFAULT NULL,
            expires_at   TIMESTAMP     NOT NULL,
            created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_tokens_user_id (user_id),
            INDEX idx_tokens_expires_at (expires_at)
        )');
    }

    public function down(): void
    {
        qi('DROP TABLE IF EXISTS tokens');
    }
};
