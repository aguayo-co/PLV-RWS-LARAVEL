Un pago puede tener los siguientes estados:

- **0**: Esperando confirmación
- **1**: Procesando
- **10**: Pagada
- **98**: Error (Rechazada)
- **99**: Cancelada

El pago tiene la siguiente información:

- uniqid: Identificador único aleatorio.
- request: Objeto con información sobre la solicitud de pago.
- attempts: Arreglo con información sobre los posibles intentos de pago hechos. Esta información proviene de
la pasarela de pagos. Se almacena cada una de las notificaciones hechas por la pasarela.

Para enviar un usuario al proceso de pago, se debe solicitar un nuevo pago especificando qué pasarela
usar por parámetros de URL (`?gateway=pasarela_seleccionada`). Dependiendo de la pasarela, podría ser necesario enviar información adicional con la solicitud.

La respuesta incluirá, en la propiedad `request_data`, la información mínima necesaria para enviar al usuario a la pasarela de pagos.

Una vez un pago es generado, la orden deja de estar en carro de compras, los productos se marcan
como no disponibles, y no se puede generar un nuevo pago para la misma.

Las pasarelas disponibles y la información adicional necesaria para cada una se indican a continuación:

##### PayU

- **Nombre**: pay_u (`?gateway=pay_u`)
- **Parámetros adicionales**: Ninguno
- **request_data**:
    - test
    - accountId
    - merchantId
    - referenceCode
    - amount
    - currency
    - signature
    - description
    - confirmationUrl
    - buyerFullName
    - buyerEmail
    - gatewayUrl: URL a la que se debe enviar la petición POST.

Aparte de `gatewayUrl`, la información sobre las variables se encuentra en la
[documentación](http://developers.payulatam.com/es/web_checkout/variables.html)
de PayU.


##### Transferencia Bancaria

- **Nombre**: transfer (`?gateway=transfer`)
- **request_data**:
    - reference
    - amount

Este Gateway debe usarse cuando los pagos los van a hacer de forma manual las usuarias, y estos
van a ser validados por los administradores de Prilov, nuevamente, de forma manual.

Estos pagos quedan permanentemente en pendiente hasta que un administrador los apruebe o los rechace.

La ruta para aprobar o rechazar estos pagos es `/callback/gateway/transfer`.
