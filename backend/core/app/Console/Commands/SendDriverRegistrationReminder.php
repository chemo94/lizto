<?php

namespace App\Console\Commands;

use App\Models\Driver;
use App\Services\WhatsAppNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDriverRegistrationReminder extends Command
{
    protected $signature = 'drivers:send-registration-reminder
        {--dry-run : Lista los destinatarios y el mensaje sin enviar nada}
        {--force : Ignora la ventana de 7 dias y envia igual (solo con --dry-run no aplica)}';

    protected $description = 'Envía recordatorio semanal por WhatsApp a conductores de Lizto Taxi (service_type=ride) que no completaron su registro.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force  = (bool) $this->option('force');

        $message = $this->buildMessage();

        $cutoff = now()->subDays(7);

        $query = Driver::query()
            ->where('service_type', 'ride')
            ->where('profile_complete', 0)
            ->where('status', 1)
            ->where('is_deleted', 0)
            ->whereNotNull('mobile')
            ->where('mobile', '!=', '');

        if (!$force) {
            $query->where(function ($q) use ($cutoff) {
                $q->whereNull('registration_reminder_sent_at')
                  ->orWhere('registration_reminder_sent_at', '<', $cutoff);
            });
        }

        $drivers = $query->get();

        if ($drivers->isEmpty()) {
            $this->info('No hay conductores de Lizto Taxi con registro incompleto pendientes de recordatorio.');
            return Command::SUCCESS;
        }

        $this->info('Destinatarios (' . $drivers->count() . '):');

        foreach ($drivers as $driver) {
            $phone = WhatsAppNotificationService::normalizePhoneNumber($driver->mobile);

            $this->line(sprintf(
                '  #%d %s %s | mobile=%s -> %s | last_reminder=%s',
                $driver->id,
                $driver->firstname ?? '',
                $driver->lastname ?? '',
                $driver->mobile,
                $phone ?: '(inválido)',
                $driver->registration_reminder_sent_at ?: 'nunca'
            ));

            if ($dryRun) {
                continue;
            }

            if (!$phone) {
                $this->warn("  ↳ No se pudo normalizar el teléfono, se omite.");
                Log::warning("SendDriverRegistrationReminder: teléfono inválido para driver #{$driver->id} ({$driver->mobile}).");
                continue;
            }

            $result = WhatsAppNotificationService::sendTextMessage($phone, $message);

            if (!empty($result['success'])) {
                $driver->update(['registration_reminder_sent_at' => now()]);
                $this->info("  ↳ ✓ Enviado a {$phone}");
            } else {
                $this->error("  ↳ ✗ Falló envío a {$phone}: " . json_encode($result));
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->line('--- Mensaje que se enviaría ---');
            $this->line($message);
            $this->newLine();
            $this->warn('Modo dry-run: no se envió ningún mensaje.');
        }

        return Command::SUCCESS;
    }

    private function buildMessage(): string
    {
        return "🚕 _¡Hola! 👋_\n"
            . "¡Tenemos buenas noticias! 🎉 El registro de _Lizto Taxi_ ya está funcionando al 100% y puedes completar tu registro para empezar a trabajar.\n"
            . "👉 _Ingresa ahora a la app Lizto Conductor_, termina tu registro y activa tu cuenta.\n"
            . "¡Así de fácil! 🚕💨\n"
            . "_Lizto Taxi — Conectamos conductores con pasajeros._";
    }
}
