<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $job = DB::table('cron_jobs')->where('alias', 'ride-queue')->first();
        if ($job && $job->cron_schedule_id) {
            DB::table('cron_schedules')
                ->where('id', $job->cron_schedule_id)
                ->update(['interval' => 10]);
        }
    }

    public function down(): void
    {
        $job = DB::table('cron_jobs')->where('alias', 'ride-queue')->first();
        if ($job && $job->cron_schedule_id) {
            DB::table('cron_schedules')
                ->where('id', $job->cron_schedule_id)
                ->update(['interval' => 60]);
        }
    }
};
