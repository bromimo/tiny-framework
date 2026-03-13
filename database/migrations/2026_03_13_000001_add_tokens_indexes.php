<?php

return new class {
    public function up(): void
    {
        qi('CREATE INDEX idx_tokens_user_id ON tokens (user_id)');
        qi('CREATE INDEX idx_tokens_expires_at ON tokens (expires_at)');
    }

    public function down(): void
    {
        qi('DROP INDEX idx_tokens_user_id ON tokens');
        qi('DROP INDEX idx_tokens_expires_at ON tokens');
    }
};
