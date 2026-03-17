<?php

return new class {
    public function up(): void
    {
        qi('CREATE TABLE IF NOT EXISTS failed_jobs (
            id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            queue      VARCHAR(255)    NOT NULL,
            payload    LONGTEXT        NOT NULL,
            exception  LONGTEXT        NOT NULL,
            created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
            failed_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_failed_jobs_queue (queue)
        )');
    }

    public function down(): void
    {
        qi('DROP TABLE IF EXISTS failed_jobs');
    }
};
