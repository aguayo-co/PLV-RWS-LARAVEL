Ruta para aprobar o rechazar ppagos manuales (Transfer).

Debe enviarse la referencia (`reference`) del pago a modificar el estado (`status`).

`reference`: Es la referencia única de pago que se genera al momento de solicitar un pago para la orden.
Esta información se puede consultar a través de las órdenes: `order > payments > 0 > request_data > reference`.

`status`: el único valor que aprueba la transacción es `approved`. Cualquier otro valor la rechaza.
