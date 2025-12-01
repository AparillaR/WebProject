<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Items;

class GenerateMissingQRCodes extends Command
{
    protected $signature = 'qr:generate';
    protected $description = 'Generate missing QR codes for items';

    public function handle()
    {
        $items = Items::all();
        $count = 0;

        foreach ($items as $item) {
            $qrPath = public_path('upload/qr_code/QR_' . $item->id . '.png');
            
            if (!file_exists($qrPath)) {
                // Call your QR generation method
                app()->call('App\Http\Controllers\ItemController@generateAndSaveQRCode', ['item' => $item]);
                $count++;
                $this->info("Generated QR for item: {$item->name}");
            }
        }

        $this->info("Generated {$count} QR codes");
    }
}