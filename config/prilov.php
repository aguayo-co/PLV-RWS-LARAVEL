<?php

return [
    'sales' => [
        'days_shipping_to_delivered' => env('PRILOV_DAYS_SHIPPING_TO_DELIVERED', 3),
        'days_delivered_to_completed' => env('PRILOV_DAYS_DELIVERED_TO_COMPLETED', 5),
        'days_to_publish_ratings' => env('PRILOV_DAYS_TO_PUBLISH_RATINGS', 2),
    ],
    'sale_returns' => [
        'days_created_to_canceled' => env('PRILOV_DAYS_CREATED_TO_CANCELED', 5),
        'days_of_freshness' => env('PRILOV_SALE_RETURNS_DAYS_OF_FRESHNESS', 3),
    ],
    'payments' => [
        'minutes_until_canceled' => env('PRILOV_MINUTES_UNTIL_CANCELED', 60),
        'percentage_fee' => [
            'pay_u' => 5,
            'mercado_pago' => 5,
        ]
    ],
    'chilexpress' => [
        'referencia_base' => 'PRILOV - ',
    ]
];
