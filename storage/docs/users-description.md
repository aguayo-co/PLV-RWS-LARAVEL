##### Búsqueda

Los usuarios aceptan búsquedas de texto `?q=`, y se realizan en los campos `first_name` y `last_name`.

##### Seguimiento de usuarios

Los campos `followers*` tienen información sobre los usuarios que siguen al consultado.
Los campos `following*` tienen información sobre los usuarios a los que el consultado sigue.

Sobre un usuario sólo se puede modificar la información de a cuales usuarios sigue, no se puede
modificar información relacionada con quienes siguen a dicho usuario.

Para seguir usuarios, se debe enviar un arreglo con los ids de los usuarios a seguir en el campo
`following_add`. Para dejar de seguir usuarios, se debe enviar un arreglo con los ids de estos en
en el campo `following_remove`.

Ejemplo:

```
# Seguidores actuales:
{
  "following_ids": [1, 3, 5]
}
# Datos enviados en la petición:
{
  "following_add": [4, 6],
  "following_remove": [1]
}
# Seguidores finales:
{
  "following_ids": [3, 4, 5, 6]
}
```

##### Filtrado

Aparte de los criterios de filtrado globales, los usuarios se pueden filtrar por los siguientes criterios:

|filtro|tipo|
|------|----|
|email|ContenidoEn|
|group_ids|ContenidoEn|

##### Revisión de existencia de cuenta por email

Para revisar si un correo se encuentra registrado en el portal, se puede enviar una consulta a `/api/users`
con el parámetro email a buscar: `/api/users?email=correo@buscado.com`. Esta consulta no retorna contenido,
sólo 200 o 404 según sea el caso.

Sólo en este caso la ruta es pública, para consultar listados de usuarios, se requiere permiso `admin`.


##### Productos favoritos

Los usuarios pueden tener productos marcados como favoritos. Para esto se usan los siguientes campos:

En las peticiones:
```
# Agrega a favoritos los productos 4 y 6.
# Elimina de favoritos el producto 1.
{
  "favorites_add": [4, 6],
  "favorites_remove": [1]
}
```
```
En las respuestas:
# Favoritos actuales:
{
  "favorites_ids": [4, 6]
}
```
