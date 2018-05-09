##### Filtrado

Aparte de los criterios de filtrado globales, las conversaciones se pueden filtrar por los siguientes criterios:

|filtro|tipo|
|------|----|
|unread|Boolean|
|product_id|ContenidoEn|

El filtro `unread` trae las conversaciones para las que el usuario actual tiene mensajes sin leer.

Los mensajes de las conversaciones son marcados cómo leídos cada vez que se se hace un llamado GET
a una conversación específica:
Ejemplo:
- Un llamado a `GET:/api/threads/4` marca los mensajes de la conversación 4 cómo leídos
para el usuario que hace el llamado.

##### Conversaciones privadas

Cuando una conversación es marcada como privada, sólo los participantes de esta pueden verla o crear
mensajes nuevos en la misma.

Un usuario se vuelve participante de una conversación pública en dos momentos:
 - Al momento de ser creada, si es incluida en esta.
 - Cuando un usuario crea un nuevo mensaje y lo agrega en el mismo.

##### Conversaciones públicas

Cuando una conversación es marcada como pública, cualquier usuario del portal puede verla y crear mensajes
nuevos en la misma.

Un usuario se vuelve participante de una conversación pública en tres momentos:
 - Al momento de ser creada, si es incluida en esta.
 - Cuando un usuario crea un nuevo mensaje y lo agrega en el mismo.
 - Cuando comenta en una conversación en la que no era participante.

##### Consultas sin autenticación

Es posible consultar conversaciones para usuarios sin autenticación, que pertenezcan a un producto y que sean
públicas. Se debe usar el filtro de `product_id` para tal caso.
