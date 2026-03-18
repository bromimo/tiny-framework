<?php

return new class {
    /** Добавить колонку role_id в таблицу users. */
    public function up(): void
    {
        qi("ALTER TABLE users ADD COLUMN role_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER password");
        qi("ALTER TABLE users ADD INDEX idx_users_role_id (role_id)");
        qi("ALTER TABLE users ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT");
    }

    /** Удалить колонку role_id из таблицы users. */
    public function down(): void
    {
        qi("ALTER TABLE users DROP FOREIGN KEY fk_users_role");
        qi("ALTER TABLE users DROP INDEX idx_users_role_id");
        qi("ALTER TABLE users DROP COLUMN role_id");
    }
};
