<?php

return new class {
    public function up(): void
    {
        qi('CREATE TABLE IF NOT EXISTS jobs (
            id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            queue        VARCHAR(255)    NOT NULL,
            payload      LONGTEXT        NOT NULL,
            attempts     TINYINT UNSIGNED NOT NULL DEFAULT 0,
            available_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reserved_at  TIMESTAMP       NULL     DEFAULT NULL,
            worker_id    VARCHAR(255)    NULL     DEFAULT NULL,
            created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_jobs_queue (queue),
            INDEX idx_jobs_available_at (available_at)
        )');
    }

    public function down(): void
    {
        qi('DROP TABLE IF EXISTS jobs');
    }
};
