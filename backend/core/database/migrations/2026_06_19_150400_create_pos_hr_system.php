<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Expand pos_staff to store employee/HR profiles
        Schema::table('pos_staff', function (Blueprint $table) {
            $table->string('document_number')->nullable()->after('name');
            $table->string('position')->default('mesero')->after('document_number'); // e.g. mesero, cocinero, administrador, cajero
            $table->date('hire_date')->nullable()->after('phone');
            $table->string('salary_type')->default('monthly')->after('commission_rate'); // monthly, daily, hourly
            $table->decimal('base_salary', 28, 8)->default(0.00)->after('salary_type');
        });

        // 2. Create pos_staff_attendance table
        if (!Schema::hasTable('pos_staff_attendance')) {
            Schema::create('pos_staff_attendance', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pos_staff_id')->constrained('pos_staff')->cascadeOnDelete();
                $table->date('date');
                $table->timestamp('clock_in')->nullable();
                $table->timestamp('clock_out')->nullable();
                $table->decimal('hours_worked', 5, 2)->default(0.00);
                $table->string('status')->default('present'); // present, late, absent, holiday, sick_leave
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['pos_staff_id', 'date']);
            });
        }

        // 3. Create pos_staff_payroll table
        if (!Schema::hasTable('pos_staff_payroll')) {
            Schema::create('pos_staff_payroll', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
                $table->foreignId('pos_staff_id')->constrained('pos_staff')->cascadeOnDelete();
                $table->date('period_start');
                $table->date('period_end');
                $table->decimal('base_salary_earned', 28, 8)->default(0.00);
                $table->decimal('commissions_earned', 28, 8)->default(0.00);
                $table->decimal('bonuses', 28, 8)->default(0.00);
                $table->decimal('deductions', 28, 8)->default(0.00);
                $table->decimal('net_salary', 28, 8)->default(0.00);
                $table->string('payment_status')->default('pending'); // pending, paid
                $table->date('payment_date')->nullable();
                $table->string('payment_method')->nullable(); // cash, transfer, bank_deposit
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('pos_staff_payroll');
        Schema::dropIfExists('pos_staff_attendance');

        Schema::table('pos_staff', function (Blueprint $table) {
            $table->dropColumn(['document_number', 'position', 'hire_date', 'salary_type', 'base_salary']);
        });
    }
};
