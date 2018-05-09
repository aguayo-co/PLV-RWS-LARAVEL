Una devolución (SaleReturn) se genera cuando un comprador quiere devolver uno o más de los productos de una venta (Sale).

- **0**: Pendiente
- **40**: Enviada
- **41**: Entregada
- **49**: Recibida
- **50**: Manejo de administrador
- **90**: Completada
- **99**: Cancelada

Se puede ingresar información sobre el despacho en la propiedad `shipment_details`.

La información de despacho debe ser un objeto. **NO tiene una estructura definida, y los datos enviados
no se validan**.

Un administrador puede marcar la orden como cancelada.

Ejemplos:

```
# Guarda información sobre el despacho.
{
    "shipment_details": {
        "note": "Una nota sobre el envío.",
        "tracking_codes" [
            {
                "code": "TRACK000000000",
                "company": "Fedex",
            },
            {
                "code": "TRACK000000001",
                "company": "UPS",
            }
        ]
    }
}

# Marca la orden como enviada.
{
    "status": 40
}

# Marca la orden como entregada.
{
    "status": 41
}

# Marca la orden como cancelada.
{
    "status": 99
}

```
