<?php

namespace App\Events;

use App\SaleReturn;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class SaleReturnSaved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $saleReturn;

    /**
     * Create a new event instance.
     *
     * @param  \App\SaleReturn $saleReturn
     * @return void
     */
    public function __construct(SaleReturn $saleReturn)
    {
        $this->saleReturn = $saleReturn;
    }
}
