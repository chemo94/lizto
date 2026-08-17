<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // ── Favors ──
        Schema::create('favors', function (Blueprint $table) {
            $table->id();
            $table->string('order_no');
            $table->foreignId('user_id')->constrained();
            $table->enum('type', ['buy', 'send'])->default('buy');
            $table->text('description');
            $table->string('store_name')->nullable();
            $table->text('store_address')->nullable();
            $table->decimal('estimated_amount', 10, 2)->nullable();
            $table->text('pickup_address');
            $table->decimal('pickup_lat', 10, 7)->nullable();
            $table->decimal('pickup_lng', 10, 7)->nullable();
            $table->text('delivery_address');
            $table->decimal('delivery_lat', 10, 7)->nullable();
            $table->decimal('delivery_lng', 10, 7)->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('status', [
                'pending', 'searching_courier', 'accepted', 'on_way_to_pickup',
                'at_pickup', 'on_way_to_delivery', 'delivered', 'cancelled'
            ])->default('searching_courier');
            $table->foreignId('courier_id')->nullable()->constrained('drivers');
            $table->foreignId('accepted_bid_id')->nullable();
            $table->string('payment_method_code')->nullable();
            $table->string('payment_method_name')->nullable();
            $table->integer('payment_status')->default(0);
            $table->string('pin_code', 4)->nullable();
            $table->timestamp('courier_assigned_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['courier_id', 'status']);
        });

        // ── Favor Bids ──
        Schema::create('favor_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('favor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('courier_id')->constrained('drivers');
            $table->decimal('bid_amount', 10, 2);
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();
        });

        // ── Favor Messages (Chat) ──
        Schema::create('favor_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('favor_id')->constrained()->cascadeOnDelete();
            $table->morphs('sender'); // user or driver
            $table->string('sender_name')->nullable();
            $table->string('sender_role', 20); // customer, courier, admin
            $table->text('message')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });

        // ── Delivery Reviews ──
        Schema::create('delivery_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->nullableMorphs('reviewable'); // DeliveryOrder or Favor
            $table->foreignId('courier_id')->nullable()->constrained('drivers');
            $table->decimal('rating', 2, 1)->default(5);
            $table->text('review')->nullable();
            $table->timestamps();
        });

        // ── Refunds ──
        Schema::create('delivery_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('order_id')->nullable();
            $table->foreignId('favor_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected', 'processed'])->default('pending');
            $table->text('admin_remark')->nullable();
            $table->timestamps();
        });

        // ── User Saved Addresses ──
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('label')->default('Otro');
            $table->text('address');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // ── Delivery PIN Verification ──
        Schema::create('delivery_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('delivery_orders');
            $table->foreignId('favor_id')->nullable();
            $table->string('pin_code', 4);
            $table->enum('status', ['active', 'verified', 'expired'])->default('active');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        // ── Courier Proof Images ──
        Schema::create('courier_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->nullable();
            $table->string('job_type', 30); // delivery, favor
            $table->foreignId('courier_id')->constrained('drivers');
            $table->string('image');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // ── Courier Earnings ──
        Schema::create('courier_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_id')->constrained('drivers');
            $table->nullableMorphs('job'); // DeliveryOrder or Favor
            $table->decimal('amount', 10, 2);
            $table->decimal('commission', 10, 2)->default(0);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // ── Delivery Coupon Usage ──
        Schema::create('delivery_coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons');
            $table->foreignId('user_id')->constrained();
            $table->nullableMorphs('orderable'); // DeliveryOrder or Favor
            $table->decimal('discount_amount', 10, 2);
            $table->timestamps();
        });

        // ── Add courier/coords columns to delivery_orders ──
        Schema::table('delivery_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_orders', 'courier_id')) {
                $table->foreignId('courier_id')->nullable()->after('driver_id');
            }
            if (!Schema::hasColumn('delivery_orders', 'pickup_lat')) {
                $table->decimal('pickup_lat', 10, 7)->nullable()->after('delivery_lng');
            }
            if (!Schema::hasColumn('delivery_orders', 'pickup_lng')) {
                $table->decimal('pickup_lng', 10, 7)->nullable()->after('pickup_lat');
            }
            if (!Schema::hasColumn('delivery_orders', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('delivery_orders', 'pin_code')) {
                $table->string('pin_code', 4)->nullable()->after('payment_method_code');
            }
        });

        // ── Courier location tracking ──
        Schema::create('courier_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_id')->constrained('drivers');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('bearing', 5, 1)->nullable();
            $table->timestamps();
        });

        // ── Admin notifications ──
        Schema::create('delivery_admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30); // new_order, new_favor, refund_request, low_rating
            $table->text('message');
            $table->nullableMorphs('reference'); // DeliveryOrder, Favor, DeliveryRefund
            $table->boolean('read')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_admin_notifications');
        Schema::dropIfExists('courier_locations');
        Schema::dropIfExists('delivery_coupon_usages');
        Schema::dropIfExists('courier_earnings');
        Schema::dropIfExists('courier_proofs');
        Schema::dropIfExists('delivery_pins');
        Schema::dropIfExists('user_addresses');
        Schema::dropIfExists('delivery_refunds');
        Schema::dropIfExists('delivery_reviews');
        Schema::dropIfExists('favor_messages');
        Schema::dropIfExists('favor_bids');
        Schema::dropIfExists('favors');
    }
};
