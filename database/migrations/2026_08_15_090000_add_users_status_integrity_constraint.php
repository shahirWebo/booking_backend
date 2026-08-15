<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        match (DB::connection()->getDriverName()) {
            'sqlite' => $this->createSqliteStatusTriggers(),
            default => null,
        };
    }

    public function down(): void
    {
        match (DB::connection()->getDriverName()) {
            'sqlite' => $this->dropSqliteStatusTriggers(),
            default => null,
        };
    }

    private function createSqliteStatusTriggers(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER users_status_check_insert
            BEFORE INSERT ON users
            FOR EACH ROW
            WHEN NEW.status NOT IN ('active', 'blocked', 'suspended', 'deleted')
            BEGIN
                SELECT RAISE(ABORT, 'users_status_check');
            END
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER users_status_check_update
            BEFORE UPDATE OF status ON users
            FOR EACH ROW
            WHEN NEW.status NOT IN ('active', 'blocked', 'suspended', 'deleted')
            BEGIN
                SELECT RAISE(ABORT, 'users_status_check');
            END
            SQL);
    }

    private function dropSqliteStatusTriggers(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS users_status_check_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS users_status_check_update');
    }
};
