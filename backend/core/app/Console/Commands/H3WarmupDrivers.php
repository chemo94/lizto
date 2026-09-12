<?php

namespace App\Console\Commands;

use App\Services\H3\DriverGeoRedisService;
use App\Services\H3\H3Grid;
use Illuminate\Console\Command;

class H3WarmupDrivers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'h3:sync {--radius=5 : Search radius in km to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize active drivers and couriers into Redis Uber H3 hexagonal spatial indexes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("==================================================");
        $this->info("Uber H3 + Redis Spatial Indexer");
        $this->info("==================================================");

        $redis = DriverGeoRedisService::getRedis();
        if ($redis) {
            $this->info("[✓] Redis connection: ACTIVE (Predis)");
        } else {
            $this->warn("[!] Redis connection: OFFLINE (Operating in MySQL fallback mode)");
        }

        $this->info("Indexing active drivers into Uber H3 cells...");
        $synced = DriverGeoRedisService::warmUpOnlineDrivers();
        $this->info("[✓] $synced active driver(s) synchronized.");

        // Benchmark / test query in Tarapoto
        $lat = -6.4863406;
        $lng = -76.3575102;
        $radius = (float) $this->option('radius');

        $originH3 = H3Grid::geoToH3($lat, $lng, 8);
        $k = H3Grid::kForRadius($radius, 8);
        $cells = H3Grid::kRing($originH3, $k);

        $this->line("");
        $this->line("Spatial Diagnostics (Tarapoto Center):");
        $this->line(" - Origin Lat/Lng: $lat, $lng");
        $this->line(" - H3 Cell (Res 8): $originH3");
        $this->line(" - Search Radius: $radius km (k=$k rings, " . count($cells) . " hexagons)");

        $start = microtime(true);
        $nearby = DriverGeoRedisService::findNearby($lat, $lng, $radius);
        $durationMs = round((microtime(true) - $start) * 1000, 2);

        $this->info("[✓] Found " . $nearby->count() . " driver(s) in {$durationMs}ms.");

        return Command::SUCCESS;
    }
}
