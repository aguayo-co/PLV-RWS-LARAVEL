Una calificación (`Rating`) se genera para cada venta (`Sale`). El campo `sale_id` es el identificador
único para cada calificación. No tiene campo `id`.

Una calificación puede tener los siguientes estados:

- **0**: No publicada
- **1**: Publicada

Una venta se puede calificar después de que la misma ha sido pagada, y se pueden modificar cuando se encuentra
en estado `No publicada`.

La calificación pasa a estar publicada cuando la venta a la que está asociada lleva más de un tiempo determinado
de haber sido completada. Este tiempo se determina en la configuración de la aplicación.

Una vez la calificación se publica, esta no puede ser modificada.

Mientras una calificación se encuentra no publicada, sólo un administrador y la persona que califica pueden ver
la calificación otorgada a la parte calificada.

##### Puntaje o calificación

Una calificación puede ser positiva (`1`), neutra (`0`) o negativa (`-1`).

##### Filtrado

Aparte de los criterios de filtrado globales, las calificaciones se pueden filtrar por los siguientes criterios:

|filtro|tipo|
|------|----|
|status|Entre|
|seller_id|ContenidoEn|
|buyer_id|ContenidoEn|
|seller_rating|ContenidoEn|
|buyer_rating|ContenidoEn|

##### Datos

- **seller_id**: Es el ID de el usuario que realizó la venta.
- **buyer_id**: Es el ID de el usuario que realizó la compra.
- **seller_rating**: Es la calificación dada por el usuario que realizó la venta. Es decir, la calificación
dada al comprador.
- **buyer_rating**: Es la calificación dada por el usuario que realizó la compra. Es decir, la calificación
dada al vendedor.
