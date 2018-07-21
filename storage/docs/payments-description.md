##### Filtrado

Aparte de los criterios de filtrado globales, los pagos se pueden filtrar por los siguientes criterios:

|filtro|tipo|
|------|----|
|gateway|ContenidoEn|
|status|ContenidoEn|
|order_id|ContenidoEn|

Para cancelar un pago que se encuentra pendiente, se debe pasar el parámetro `cancel` en el cuerpo,
y el valor debe ser `cancel`.

Esto sólo se puede hacer con pagos que no están completados.

Los productos de la orden asociada son publicados nuevamente.

Ejemplo:

```
# Cancelar el pago (y la orden):
{
  "cancel": 'cancel'
}
```
