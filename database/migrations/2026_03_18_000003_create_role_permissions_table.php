<?php

return new class {
    /** Создать связующую таблицу ролей и пермишенов. */
    public function up(): void
    {
        qi("CREATE TABLE IF NOT EXISTS role_permissions (
            role_id INT UNSIGNED NOT NULL,
            permission_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (role_id, permission_id),
            CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /** Удалить связующую таблицу ролей и пермишенов. */
    public function down(): void
    {
        qi("DROP TABLE IF EXISTS role_permissions");
    }
};
