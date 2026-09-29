<?php

namespace App\Console\Commands;

use App\WebSocket\RealtimeServer;
use Illuminate\Console\Command;

class RealtimeServe extends Command
{
    protected $signature = 'realtime:serve
                            {--host=0.0.0.0 : Host de escucha}
                            {--port=8085 : Puerto de escucha}';

    protected $description = 'Arranca el servidor WebSocket nativo de tiempo real (taxis/repartidores)';

    public function handle(): int
    {
        $host = $this->option('host');
        $port = (int) $this->option('port');

        $this->info("Arrancando WebSocket nativo en {$host}:{$port} ...");

        $server = new RealtimeServer($host, $port);
        $server->run();

        return self::SUCCESS;
    }
}
