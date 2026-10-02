<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. General settings for dynamic fare & batching
        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'driver_base_fare')) {
                $table->double('driver_base_fare')->default(3.00)->after('initial_promotional_credit');
            }
            if (!Schema::hasColumn('general_settings', 'driver_rate_per_km')) {
                $table->double('driver_rate_per_km')->default(0.80)->after('driver_base_fare');
            }
            if (!Schema::hasColumn('general_settings', 'driver_rate_per_minute')) {
                $table->double('driver_rate_per_minute')->default(0.10)->after('driver_rate_per_km');
            }
            if (!Schema::hasColumn('general_settings', 'batch_double_bonus')) {
                $table->double('batch_double_bonus')->default(1.50)->after('driver_rate_per_minute');
            }
            if (!Schema::hasColumn('general_settings', 'batch_triplet_bonus')) {
                $table->double('batch_triplet_bonus')->default(3.00)->after('batch_double_bonus');
            }
            if (!Schema::hasColumn('general_settings', 'batch_quad_bonus')) {
                $table->double('batch_quad_bonus')->default(5.00)->after('batch_triplet_bonus');
            }
            if (!Schema::hasColumn('general_settings', 'max_batch_pickup_distance_km')) {
                $table->double('max_batch_pickup_distance_km')->default(1.50)->after('batch_quad_bonus');
            }
            if (!Schema::hasColumn('general_settings', 'max_batch_detour_km')) {
                $table->double('max_batch_detour_km')->default(3.00)->after('max_batch_pickup_distance_km');
            }
            if (!Schema::hasColumn('general_settings', 'max_batch_detour_minutes')) {
                $table->double('max_batch_detour_minutes')->default(15.00)->after('max_batch_detour_km');
            }
        });

        // 2. Courier Batches table
        Schema::create('courier_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_no', 40)->unique();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('batch_type', 20)->default('SINGLE'); // SINGLE, DOUBLE, TRIPLET, QUADRUPLE
            $table->string('status', 30)->default('pending'); // pending, offered, accepted, in_progress, completed, cancelled
            $table->unsignedTinyInteger('total_orders')->default(1);
            $table->double('total_distance_km')->default(0);
            $table->double('total_duration_minutes')->default(0);

            // Fare breakdown columns
            $table->double('base_earning')->default(0);
            $table->double('distance_earning')->default(0);
            $table->double('time_earning')->default(0);
            $table->double('batch_bonus')->default(0);
            $table->double('demand_incentive')->default(0);
            $table->double('driver_earning')->default(0);
            $table->double('total_tips')->default(0);
            $table->double('total_payout')->default(0);
            $table->integer('total_points')->default(0);

            // Demand state snapshot
            $table->string('demand_tier', 20)->default('NORMAL');
            $table->double('demand_multiplier')->default(1.00);

            // Structured optimization data
            $table->json('optimized_stops')->nullable();
            $table->json('fare_breakdown')->nullable();

            // Timestamps
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['driver_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        // 3. Courier Batch Orders table (pivot/detail for batch items)
        Schema::create('courier_batch_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('courier_batches')->cascadeOnDelete();
            $table->unsignedBigInteger('order_id');
            $table->string('order_type', 100); // App\Models\DeliveryOrder or App\Models\Favor
            $table->unsignedTinyInteger('sequence_order')->default(1);
            $table->unsignedTinyInteger('pickup_stop_no')->default(1);
            $table->unsignedTinyInteger('dropoff_stop_no')->default(2);
            $table->string('status', 30)->default('pending'); // pending, picked_up, delivered, cancelled

            // Individual financial allocations
            $table->double('individual_earning')->default(0);
            $table->double('tip')->default(0);
            $table->integer('points')->default(0);
            $table->json('fare_breakdown')->nullable();

            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'status']);
            $table->index(['order_type', 'order_id']);
        });

        // 4. Reference batch_id in courier_job_offers
        if (Schema::hasTable('courier_job_offers') && !Schema::hasColumn('courier_job_offers', 'batch_id')) {
            Schema::table('courier_job_offers', function (Blueprint $table) {
                $table->foreignId('batch_id')->nullable()->after('driver_id')->constrained('courier_batches')->nullOnDelete();
            });
        }

        // 5. Reference courier_batch_id in orders
        if (Schema::hasTable('delivery_orders') && !Schema::hasColumn('delivery_orders', 'courier_batch_id')) {
            Schema::table('delivery_orders', function (Blueprint $table) {
                $table->unsignedBigInteger('courier_batch_id')->nullable()->after('driver_id')->index();
            });
        }

        if (Schema::hasTable('favors') && !Schema::hasColumn('favors', 'courier_batch_id')) {
            Schema::table('favors', function (Blueprint $table) {
                $table->unsignedBigInteger('courier_batch_id')->nullable()->after('courier_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('favors') && Schema::hasColumn('favors', 'courier_batch_id')) {
            Schema::table('favors', function (Blueprint $table) {
                $table->dropColumn('courier_batch_id');
            });
        }

        if (Schema::hasTable('delivery_orders') && Schema::hasColumn('delivery_orders', 'courier_batch_id')) {
            Schema::table('delivery_orders', function (Blueprint $table) {
                $table->dropColumn('courier_batch_id');
            });
        }

        if (Schema::hasTable('courier_job_offers') && Schema::hasColumn('courier_job_offers', 'batch_id')) {
            Schema::table('courier_job_offers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('batch_id');
            });
        }

        Schema::dropIfExists('courier_batch_orders');
        Schema::dropIfExists('courier_batches');

        Schema::table('general_settings', function (Blueprint $table) {
            $columns = [
                'driver_base_fare',
                'driver_rate_per_km',
                'driver_rate_per_minute',
                'batch_double_bonus',
                'batch_triplet_bonus',
                'batch_quad_bonus',
                'max_batch_pickup_distance_km',
                'max_batch_detour_km',
                'max_batch_detour_minutes',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('general_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
