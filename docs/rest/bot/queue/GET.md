# Ver estado del bot
___

Descripción: Consultar la cola del bot.

Método HTTP: GET

URL REST: **/bot/queue**

Códigos message de respuesta:

200

    message: 
        LOADED - Cola cargada.
        NO_QUEUE - No hay cola actualmente.
    data: Array<QueeTask>
	