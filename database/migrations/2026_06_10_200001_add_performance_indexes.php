<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // trips: composite index for driver workload queries
        Schema::table('trips', function (Blueprint $table) {
            if (! $this->indexExists('trips', 'idx_trips_driver_depart')) {
                $table->index(['driver_id', 'depart_at'], 'idx_trips_driver_depart');
            }
            if (! $this->indexExists('trips', 'idx_trips_status_depart')) {
                $table->index(['status', 'depart_at'], 'idx_trips_status_depart');
            }
        });

        // tp_trip_student_logs: lookup by execution + student
        Schema::table('tp_trip_student_logs', function (Blueprint $table) {
            if (! $this->indexExists('tp_trip_student_logs', 'idx_tp_student_logs_exec_student')) {
                $table->index(['execution_id', 'student_id'], 'idx_tp_student_logs_exec_student');
            }
        });

        // audit_logs: filter by event type + date range
        Schema::table('audit_logs', function (Blueprint $table) {
            if (! $this->indexExists('audit_logs', 'idx_audit_event_created')) {
                $table->index(['event', 'created_at'], 'idx_audit_event_created');
            }
        });

        // tp_enrollments: active enrollments query
        Schema::table('tp_enrollments', function (Blueprint $table) {
            if (! $this->indexExists('tp_enrollments', 'idx_tp_enroll_program_dates')) {
                $table->index(['program_id', 'enrolled_at', 'unenrolled_at'], 'idx_tp_enroll_program_dates');
            }
        });

        // notifications: unread count per user
        Schema::table('notifications', function (Blueprint $table) {
            if (! $this->indexExists('notifications', 'idx_notif_notifiable_read')) {
                $table->index(['notifiable_id', 'notifiable_type', 'read_at'], 'idx_notif_notifiable_read');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_trips_driver_depart');
            $table->dropIndexIfExists('idx_trips_status_depart');
        });

        Schema::table('tp_trip_student_logs', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_tp_student_logs_exec_student');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_audit_event_created');
        });

        Schema::table('tp_enrollments', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_tp_enroll_program_dates');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_notif_notifiable_read');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            foreach (DB::select("PRAGMA index_list(`{$table}`)") as $row) {
                if (($row->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        return count(DB::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        )) > 0;
    }
};
