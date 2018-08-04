Un producto puede tener los siguientes estados:

- **0**: No publicado
- **1**: Rechazado
- **2**: Escondido
- **3**: Requiere revisión
- **10**: Aprobado
- **19**: Disponible
- **20**: No Disponible
- **29**: En vacaciones
- **30**: En pago
- **31**: Vendido
- **32**: Vendido y devuelto

##### Búsqueda

Los productos aceptan búsquedas de texto `?q=`, y se realizan en los campos:
 - Producto: `title`
 - Usuario: `first_name` y `last_name`
 - Marca: `name`
 - Categoría: `name`

##### Ordenamiento

Aparte de los criterios globales, los productos se pueden ordenar por los siguientes criterios:

|valor|parámetro de url|ejemplo|
|-----|----------------|-------|
|precio|price|`?orderby=-price`|
|comisión|prilov|`?orderby=prilov`|

##### Filtrado

Aparte de los criterios de filtrado globales, los productos se pueden filtrar por los siguientes criterios:

|filtro|tipo|
|------|----|
|price|Entre|
|status|Entre|
|brand_id|ContenidoEn|
|campaign_ids|ContenidoEn|
|category_id|ContenidoEn|
|color_ids|ContenidoEn|
|condition_id|ContenidoEn|
|size_id|ContenidoEn|
|user_id|ContenidoEn|

##### Agregar imágenes

Las imágenes que se envíen en el campo `images`, serán agregadas al listado de imágenes que ya tenga
el producto. El índice con el que sea enviada la imagen dicta nombre con el que sea guardado.
Si la imagen se envía en con el índice 3, el nombre será algo así: `3-6886876.jpg`.
Existen imágenes que no siguen este patrón de nombre, y son imágenes antiguas migradas
de la versión anterior del sitio.
Para eliminar imágenes se debe enviar la información en un campo `images_remove`.

##### Eliminación de imágenes

Para eliminar imágenes de los productos se debe pasar un arreglo con los nombres de las imágenes en le campo
**images_remove**. Cada nombre debe incluir la extension, si el archivo tiene una, pero no la ruta al mismo.

###### Ejemplo:

Para eliminar estas dos imágenes:

- `https://prilov.aguayo.co/storage/products/images/1/qwe1234567890`
- `https://prilov.aguayo.co/storage/products/images/1/asd0987654321.gif`

Se debe pasar:

````
{
    "images_remove": [
        "qwe1234567890",
        "asd0987654321.gif"
    ]
}
```
