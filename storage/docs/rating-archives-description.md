Las calificaciones archivadas (`Rating Archive`) son calificaciones generadas en una versión anterior
de la plataforma, que no se asociaban a ninguna venta en específico.

No se pueden generar nuevas. Sólo se pueden modificar las existentes y eliminar.

##### Puntaje o calificación

Una calificación archivada puede ser positiva (`1`), neutra (`0`) o negativa (`-1`).

##### Filtrado

Aparte de los criterios de filtrado globales, las calificaciones archivadas se pueden filtrar por
los siguientes criterios:

|filtro|tipo|
|------|----|
|seller_id|ContenidoEn|
|buyer_id|ContenidoEn|
|buyer_rating|ContenidoEn|

##### Datos

Esta es la explicación de algunos de los campos que se usan en este modelo.

- **seller_id**: Es el ID de el usuario que realizó la venta.
- **buyer_id**: Es el ID de el usuario que realizó la compra.
- **buyer_rating**: Es la calificación dada por el usuario que realizó la compra. Es decir, la calificación
dada al vendedor.
- **buyer_comment**: Es el comentario dado por el usuario que realizó la compra. Es decir, el comentario
dado al vendedor.
