Una transacción de créditos (`CreditsTransaction`) registra los movimientos de créditos para un usuario.
Estos pueden ser ingresos o egresos.

Cuando la transacción es una solicitud de transferencia del usuario, se debe usar el campo `transfer_status`.
Este tiene dos posibles valores:

- **0**: Pendiente
- **1**: Completada

Sin importar el estado de la transferencia, los créditos de la misma son descontados del total disponible para el usuario.

##### Créditos de carro de compras

Cuando un usuario agrega créditos a el pago de una orden en el carro de compras, pero no procede con el pago de
la orden, la transacción generada para dicho pago queda en el sistema pero estos créditos no son descontados del
total disponible del usuario.

##### Filtrado

Aparte de los criterios de filtrado globales, las transacciones de créditos se pueden filtrar por los
siguientes criterios:

|filtro|tipo|
|------|----|
|transfer_status|Entre|
