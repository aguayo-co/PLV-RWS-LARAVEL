<?php

namespace App\Console\Commands;

use App\Rating;
use App\Sale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PublishRatings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ratings:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish ratings for order that have been completed X days ago.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $ratings = Rating::where('status', Rating::STATUS_UNPUBLISHED)->whereHas('sale', function ($query) {
            $updatedBefore = now()->subDays(config('prilov.sales.days_to_publish_ratings'));
            $query->where('status', '>=', Sale::STATUS_COMPLETED)->where('updated_at', '<', $updatedBefore);
        });

        // We want to fire events.
        foreach ($ratings as $rating) {
            $rating->status = Rating::STATUS_UNPUBLISHED;
            $rating->save();
        }
    }
}
