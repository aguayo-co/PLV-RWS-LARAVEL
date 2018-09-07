Una devolución (SaleReturn) se genera cuando un comprador quiere devolver uno o más de los productos de una venta (Sale).

- **0**: Pendiente
- **40**: Enviada
- **41**: Entregada
- **49**: Recibida (Depreciado: 2018-08-17)
- **50**: Manejo de administrador (Depreciado: 2018-08-17)
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

##### Filtrado

Aparte de los criterios de filtrado globales, las ventas se pueden filtrar por los siguientes criterios:

|filtro|tipo|
|------|----|
|status|Entre|
|sale_id|ContenidoEn|
|buyer_id|ContenidoEn|
|buyer_email|ContenidoEn|
|user_id|ContenidoEn|
|user_email|ContenidoEn|
|product_id|ContenidoEn|
|product_title|ContenidoEn|

##### Ordenamiento

Aparte de los criterios globales, los sliders se pueden ordenar por los siguientes criterios:

|valor|parámetro de url|ejemplo|
|-----|----------------|-------|
|Fecha de creación|created_at|`?orderby=-created_at`|
