<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderNumberGenerator
{
    /**
     * Generate a unique sequential order number
     * Format: ORD-{YearMonth}-{SequentialNumber}
     * Example: ORD-202403-0001
     */
    public function generate(): string
    {
        $prefix = 'ORD-' . date('Ym') . '-';
        $uuid = substr(Str::uuid()->toString(), 0, 8);

        return $prefix . $uuid;
    }
}
