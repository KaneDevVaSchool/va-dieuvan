<?php

namespace App\Console\Commands;

use App\Models\DispatchRequest;
use App\Support\DispatchCargoShipmentProvisioner;
use Illuminate\Console\Command;

class BackfillCargoShipmentsCommand extends Command
{
    protected $signature = 'cargo:backfill-shipments {--dry-run : Chỉ in số lượng, không ghi DB}';

    protected $description = 'Tạo cargo_shipments cho yêu cầu hàng hóa đã duyệt có trip nhưng chưa có phiếu';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $base = DispatchRequest::query()
            ->where('trip_type', 'cargo')
            ->where('status', 'approved')
            ->whereHas('trip')
            ->whereDoesntHave('cargoShipment');

        $count = (clone $base)->count();

        if ($dry) {
            $this->info("Sẽ tạo: {$count}");

            return self::SUCCESS;
        }

        $n = 0;
        $base->with('trip')->chunkById(100, function ($rows) use (&$n) {
            foreach ($rows as $dr) {
                $trip = $dr->trip;
                if (! $trip) {
                    continue;
                }
                DispatchCargoShipmentProvisioner::provision($dr, $trip);
                $n++;
            }
        });

        $this->info("Đã đồng bộ {$n} phiếu hàng.");

        return self::SUCCESS;
    }
}
