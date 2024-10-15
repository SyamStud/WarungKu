<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Discount; // Pastikan model diskon sudah ada

class UpdateDiscountStatus extends Command
{
    protected $signature = 'discount:update-status';
    protected $description = 'Update discount status to inactive if the end date is passed';

    public function handle()
    {
        $now = now();

        Discount::where('end_date', '<', $now)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $this->info('Discount statuses updated successfully!');
    }
}
