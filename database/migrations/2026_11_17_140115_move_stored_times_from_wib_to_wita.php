<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The application moves from WIB (Asia/Jakarta) to WITA (Asia/Makassar), the time of Morowali. Dates and times are
 * stored without a zone, in the time of the application, so every stored one moves an hour later to keep meaning the
 * same moment. Dates without a time (the survey period) stay, and so does users.photo_updated_at: a copy of the
 * LastUpdate of the access card system, in that system's own time. Columns of Unix seconds (jobs, sessions, cache)
 * are integers and are left alone.
 *
 * On a database that has people already, it only runs once APP_TIMEZONE is Asia/Makassar, so that the stored times
 * and the clock of the application change together.
 */
return new class extends Migration
{
    /**
     * The new time zone of the application.
     */
    private const WITA = 'Asia/Makassar';

    /**
     * Column types that hold a date and a time (SQL Server, SQLite, MySQL, PostgreSQL).
     */
    private const DATE_TIME_TYPES = ['datetime', 'datetime2', 'smalldatetime', 'timestamp'];

    /**
     * Columns that are not in the time of the application, as "table.column".
     */
    private const KEPT = ['users.photo_updated_at'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (config('app.timezone') !== self::WITA && DB::table('users')->exists()) {
            throw new RuntimeException('Set APP_TIMEZONE='.self::WITA.' in .env (and recreate the containers) before this migration: it moves the stored times from WIB to WITA.');
        }

        $this->shift(1);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->shift(-1);
    }

    /**
     * Move every stored date and time by a number of hours.
     */
    private function shift(int $hours): void
    {
        foreach (Schema::getTableListing() as $table) {
            foreach (Schema::getColumns($table) as $column) {
                if (! in_array(strtolower((string) $column['type_name']), self::DATE_TIME_TYPES, true)
                    || in_array(Str::afterLast($table, '.').'.'.$column['name'], self::KEPT, true)) {
                    continue;
                }

                DB::table($table)->whereNotNull($column['name'])->update([$column['name'] => DB::raw($this->later($column['name'], $hours))]);
            }
        }
    }

    /**
     * The SQL of a column moved by a number of hours.
     */
    private function later(string $column, int $hours): string
    {
        $wrapped = DB::getQueryGrammar()->wrap($column);

        return match (DB::getDriverName()) {
            'sqlsrv' => "DATEADD(hour, {$hours}, {$wrapped})",
            'sqlite' => "datetime({$wrapped}, '".sprintf('%+d', $hours)." hours')",
            'mysql', 'mariadb' => "DATE_ADD({$wrapped}, INTERVAL {$hours} HOUR)",
            'pgsql' => "{$wrapped} + interval '{$hours} hours'",
        };
    }
};
