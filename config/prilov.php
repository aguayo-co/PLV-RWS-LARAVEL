<?php

return [
    'sales' => [
        'days_shipping_to_delivered' => env('PRILOV_DAYS_SHIPPING_TO_DELIVERED', 3),
        'days_delivered_to_completed' => env('PRILOV_DAYS_DELIVERED_TO_COMPLETED', 2),
        'days_to_publish_ratings' => env('PRILOV_DAYS_TO_PUBLISH_RATINGS', 5),
    ],
    'sale_returns' => [
        'days_created_to_canceled' => env('PRILOV_DAYS_CREATED_TO_CANCELED', 2),
    ],
    'payments' => [
        'minutes_until_canceled' => env('PRILOV_MINUTES_UNTIL_CANCELED', 45),
    ],
];
