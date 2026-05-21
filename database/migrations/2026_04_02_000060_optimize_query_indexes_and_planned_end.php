<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Trips: add planned_end_at for fast overlap checks + better indexes
        if (Schema::hasTable('trips')) {
            Schema::table('trips', function (Blueprint $table) {
                if (! Schema::hasColumn('trips', 'planned_end_at')) {
                    // Filled by DB when supported; fallback to app logic otherwise.
                    $table->dateTime('planned_end_at')->nullable()->after('arrive_by');
                }
            });

            // For MySQL we can convert planned_end_at into a generated stored column.
            // We do it as raw SQL so it remains optional for other DBs.
            if (DB::getDriverName() === 'mysql') {
                // If column exists but not generated, this is a no-op for existing installs.
                // Use a guarded try-catch style by checking information_schema.
                $exists = DB::selectOne("
                    SELECT COUNT(*) as c
                    FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'trips'
                      AND COLUMN_NAME = 'planned_end_at'
                ");
                if (($exists->c ?? 0) > 0) {
                    // Attempt to make it generated stored (safe to run only once).
                    // If already generated, MySQL will error; ignore via try/catch.
                    try {
                        DB::statement('
                            ALTER TABLE trips
                            MODIFY planned_end_at DATETIME
                            GENERATED ALWAYS AS (COALESCE(arrive_by, DATE_ADD(depart_at, INTERVAL 2 HOUR))) STORED
                        ');
                    } catch (\Throwable $e) {
                        Log::warning('Migration: could not convert planned_end_at to generated column — skipping.', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            Schema::table('trips', function (Blueprint $table) {
                $table->index(['dispatcher_id', 'depart_at'], 'trips_dispatcher_depart_idx');
                $table->index(['transport_provider_id', 'depart_at'], 'trips_provider_depart_idx');
                $table->index(['status', 'payment_status', 'depart_at'], 'trips_status_pay_depart_idx');

                // Interval overlap queries: resource_id + depart_at + planned_end_at
                $table->index(['vehicle_id', 'depart_at', 'planned_end_at'], 'trips_vehicle_interval_idx');
                $table->index(['driver_id', 'depart_at', 'planned_end_at'], 'trips_driver_interval_idx');
            });
        }

        // Dispatch requests: improve filtering/search
        if (Schema::hasTable('dispatch_requests')) {
            Schema::table('dispatch_requests', function (Blueprint $table) {
                $table->index(['trip_type', 'depart_at'], 'dr_triptype_depart_idx');
                $table->index(['source_channel', 'depart_at'], 'dr_source_depart_idx');
                $table->index(['paper_status', 'paper_received_at'], 'dr_paper_status_received_idx');
                $table->index(['is_urgent', 'depart_at'], 'dr_urgent_depart_idx');
            });
        }

        // Trip costs: reconcile/reporting
        if (Schema::hasTable('trip_costs')) {
            Schema::table('trip_costs', function (Blueprint $table) {
                $table->index(['trip_id', 'status', 'type'], 'trip_costs_trip_status_type_idx');
                $table->index(['confirmed_by', 'confirmed_at'], 'trip_costs_confirmed_by_at_idx');
            });
        }

        // Trip records: quick lookup by trip
        if (Schema::hasTable('trip_records')) {
            Schema::table('trip_records', function (Blueprint $table) {
                $table->index(['trip_id', 'created_at'], 'trip_records_trip_created_idx');
            });
        }

        // Attachments: common queries by uploader + kind
        if (Schema::hasTable('attachments')) {
            Schema::table('attachments', function (Blueprint $table) {
                $table->index(['uploaded_by', 'created_at'], 'attachments_uploader_created_idx');
                $table->index(['attachable_type', 'attachable_id', 'kind'], 'attachments_attachable_kind_idx');
            });
        }

        // Trip events: common queries
        if (Schema::hasTable('trip_events')) {
            Schema::table('trip_events', function (Blueprint $table) {
                $table->index(['trip_id', 'type', 'created_at'], 'trip_events_trip_type_created_idx');
                $table->index(['created_by', 'created_at'], 'trip_events_creator_created_idx');
            });
        }

        // Payments & reconciliation
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index(['reconciliation_period_id', 'status'], 'payments_period_status_idx');
                $table->index(['executed_by', 'executed_at'], 'payments_executed_by_at_idx');
            });
        }

        if (Schema::hasTable('reconciliation_periods')) {
            Schema::table('reconciliation_periods', function (Blueprint $table) {
                $table->index(['start_date', 'end_date', 'status'], 'reconcile_range_status_idx');
            });
        }

        // Cargo shipments: SLA dashboards
        if (Schema::hasTable('cargo_shipments')) {
            Schema::table('cargo_shipments', function (Blueprint $table) {
                $table->index(['status', 'created_at'], 'cargo_status_created_idx');
                $table->index(['trip_id'], 'cargo_trip_idx');
            });
        }

        // Door-to-door route runs: calendar queries
        if (Schema::hasTable('route_runs')) {
            Schema::table('route_runs', function (Blueprint $table) {
                $table->index(['trip_id'], 'route_runs_trip_idx');
                $table->index(['route_version_id', 'run_date'], 'route_runs_version_date_idx');
            });
        }
    }

    public function down(): void
    {
        // Best-effort rollback of indexes. Generated column change is not reverted.
        if (Schema::hasTable('trips')) {
            Schema::table('trips', function (Blueprint $table) {
                $table->dropIndex('trips_dispatcher_depart_idx');
                $table->dropIndex('trips_provider_depart_idx');
                $table->dropIndex('trips_status_pay_depart_idx');
                $table->dropIndex('trips_vehicle_interval_idx');
                $table->dropIndex('trips_driver_interval_idx');
            });
        }

        if (Schema::hasTable('dispatch_requests')) {
            Schema::table('dispatch_requests', function (Blueprint $table) {
                $table->dropIndex('dr_triptype_depart_idx');
                $table->dropIndex('dr_source_depart_idx');
                $table->dropIndex('dr_paper_status_received_idx');
                $table->dropIndex('dr_urgent_depart_idx');
            });
        }

        if (Schema::hasTable('trip_costs')) {
            Schema::table('trip_costs', function (Blueprint $table) {
                $table->dropIndex('trip_costs_trip_status_type_idx');
                $table->dropIndex('trip_costs_confirmed_by_at_idx');
            });
        }

        if (Schema::hasTable('trip_records')) {
            Schema::table('trip_records', function (Blueprint $table) {
                $table->dropIndex('trip_records_trip_created_idx');
            });
        }

        if (Schema::hasTable('attachments')) {
            Schema::table('attachments', function (Blueprint $table) {
                $table->dropIndex('attachments_uploader_created_idx');
                $table->dropIndex('attachments_attachable_kind_idx');
            });
        }

        if (Schema::hasTable('trip_events')) {
            Schema::table('trip_events', function (Blueprint $table) {
                $table->dropIndex('trip_events_trip_type_created_idx');
                $table->dropIndex('trip_events_creator_created_idx');
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex('payments_period_status_idx');
                $table->dropIndex('payments_executed_by_at_idx');
            });
        }

        if (Schema::hasTable('reconciliation_periods')) {
            Schema::table('reconciliation_periods', function (Blueprint $table) {
                $table->dropIndex('reconcile_range_status_idx');
            });
        }

        if (Schema::hasTable('cargo_shipments')) {
            Schema::table('cargo_shipments', function (Blueprint $table) {
                $table->dropIndex('cargo_status_created_idx');
                $table->dropIndex('cargo_trip_idx');
            });
        }

        if (Schema::hasTable('route_runs')) {
            Schema::table('route_runs', function (Blueprint $table) {
                $table->dropIndex('route_runs_trip_idx');
                $table->dropIndex('route_runs_version_date_idx');
            });
        }
    }
};
