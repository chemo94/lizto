<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\CronSchedule;
use App\Models\CronJob;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Get or create the 3 Minutes schedule (180 seconds)
        $schedule = CronSchedule::where('interval', 180)->first();
        if (!$schedule) {
            $schedule = new CronSchedule();
            $schedule->name = '3 Minutes';
            $schedule->interval = 180;
            $schedule->status = 1;
            $schedule->save();
        }

        // 2. Insert or update the SUNAT processing cron job
        $cronJob = CronJob::where('alias', 'process_sunat_invoices')->first();
        if (!$cronJob) {
            $cronJob = new CronJob();
            $cronJob->alias = 'process_sunat_invoices';
        }
        $cronJob->name = 'Process SUNAT Invoices';
        $cronJob->action = ['App\\Http\\Controllers\\CronController', 'processSunatInvoices'];
        $cronJob->url = null;
        $cronJob->cron_schedule_id = $schedule->id;
        $cronJob->next_run = now();
        $cronJob->is_running = 1;
        $cronJob->is_default = 1;
        $cronJob->save();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        CronJob::where('alias', 'process_sunat_invoices')->delete();
    }
};
