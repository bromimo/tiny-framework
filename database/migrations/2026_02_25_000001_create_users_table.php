<?php

return new class {
    public function up(): void
    {
        qi('CREATE TABLE IF NOT EXISTS users (
            id         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100)  NOT NULL,
            last_name  VARCHAR(100)  NOT NULL,
            email      VARCHAR(255)  NOT NULL UNIQUE,
            password   VARCHAR(255)  NOT NULL,
            created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )');
    }

    public function down(): void
    {
        qi('DROP TABLE IF EXISTS users');
    }
};
