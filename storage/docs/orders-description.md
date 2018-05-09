Una orden (`Order`) es el punto de entrada de una compra en el portal. Cada Orden es una compra independiente.
Las ordenes sólo pueden ser modificadas por el usuario que hace la compra o por un administrador.

Una orden puede tener los siguientes estados:

- **10**: Carro de compras
- **20**: En pago
- **30**: Pagada
- **99**: Cancelada

Una orden se crea automáticamente en el momento en que un usuario agrega productos al carro de compras.
Un carro es una orden que no ha pasado a proceso de pago.

Una vez el usuario pasa a pagar una orden, no se podrán agregar productos a la misma, y se creará una
nueva orden para recibir nuevos productos en el carro de compras.

Cuando un producto se agrega a la orden, se crea una venta (modelo Sale).
Una venta es la agrupación de productos de un mismo vendedor de una orden.

Si un usuario tiene una orden con 5 productos, que pertenecen a 3 vendedores, entonces se habrá generado
una orden y 3 ventas.

##### Seleccionar dirección de despacho.

Cuando un usuario selecciona la dirección de despacho de la compra, la información completa de la misma
se guarda en la orden, desligada del modelo original. Esto permite que si el modelo es posteriormente
modificado por el usuario, no se vea afectada la información de la orden.

En la orden NO se almacena el ID del modelo `Address` de donde provino la información.
El ID de la dirección a usar se pasa con la propiedad `address_id`.

```
# Selecciona la dirección con id 5.
{
    "address_id": 5
}
```

##### Agregar y eliminar productos del carro de compras.

Los productos se agregan y eliminan del carro con las propiedades `add_product_ids` y `remove_product_ids`.

Ejemplo:

```
# Agrega los productos 3 y 7, y elimina los productos 5 y 6.
{
    "add_product_ids": [3, 7],
    "remove_product_ids": [5, 6]
}
```

##### Modificaciones a una venta (Sale)

El modelo `Sale` almacena la información de los productos comprados por un usuario que corresponden a
un vendedor, puede ser modificado por el usuario que genera la compra, y por el usuario que hace la venta.
Si la modificación la hace el usuario comprador, esta se debe realizar a través de la ruta de la orden.

Un usuario comprador puede modificar una venta en dos momentos:
 - Cuando selecciona el método de despacho de esa venta.
 - Cuando marca la venta como recibida.

Toda la información se envía en la propiedad `sales`, con el ID de cada venta como indice, y la información
cómo propiedades. Ejemplo:

```
# Selecciona el método de envío para las ventas 10 y 11.
{
    "sales": {
        "10": {
            "shipping_method_id": 2
        }
        "11": {
            "shipping_method_id": 2
        }
    }
}

# Marca las ventas 3 y 4 como recibidas.
{
    "sales": {
        "3": {
            "status": 49
        }
        "4": {
            "status": 49
        }
    }
}
```

Información adicional sobre la estructura de el modelo Sale se encuentra en la documentación de la
ruta `/sales`.

##### Filtrado

Aparte de los criterios de filtrado globales, los órdenes se pueden filtrar por los siguientes criterios:

|filtro|tipo|
|------|----|
|status|Entre|
