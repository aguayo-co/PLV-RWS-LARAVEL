<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Prilov Language Lines
    |--------------------------------------------------------------------------
    */

    'credits' => [
        'reasons' => [
            'saleCanceled' => 'Venta cancelada.',
            'orderPayment' => 'Pago de orden.',
            'orderCanceled' => 'Orden cancelada. No se recibió el pago.',
            'orderCompleted' => 'Orden completada.',
            'orderPartial' => 'Orden completada con productos devueltos: ":products".',
            'returnCompleted' => 'Devolución completada.',
            'returnCanceled' => 'Devolución cancelada.',
            'purchased' => 'Compra de créditos.',
        ],
    ],
    'coupons' => [
        'firstPurchaseOnly' => 'Cupón sólo permitido en primera compra.',
        'notApplicable' => 'Este cupón no aplica para ninguno de los productos de la orden.',
    ],
    'products' => [
        'notAvailable' => 'Alguno de los productos ya no está disponible.',
        'alreadyReplicated' => 'Este producto ya fue replicado.',
    ],
    'orders' => [
        'noPendingPayment' => 'No Existe un pago pendiente en la orden.',
        'paymentIsNotTransfer' => 'El pago no es de tipo Transferencia.',
        'transferAlreadyApproved' => 'Tú orden ya se encuentra pagada y no es necesario subir un recibo.',
        'frozenOfShoppingCart' => 'No se puede modificar Orden que no está en ShoppingCart.',
        'notPayed' => 'La orden no ha sido pagada.',
    ],
    'users' => [
        'hasPendingSales' => 'La usuaria tiene ventas pendientes.',
        'hasPendingOrders' => 'La usuaria tiene compras pendientes.',
        'hasCredits' => 'La usuaria tiene saldo de créditos.',
        'hasPendingTransfers' => 'La usuaria tiene transferencias pendientes.',
    ]
];
