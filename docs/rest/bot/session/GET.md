# Ver estado del bot
___

Descripción: Ver id de la última sesión. Si el bot está online, la última sesión es la actual.

Método HTTP: GET

URL REST: **/session**

Códigos message de respuesta:

200

    message: 
        LAST_SESSION_ID - Cargada ID de la sesión.
    data: Id de la sesión.
	