# Ver estado del bot
___

Descripción: Consultar los logs del bot.

Método HTTP: GET

URL REST: **/bot/logs/internal**

Códigos message de respuesta:

200

    message: 
        LOADED - Logs cargados.
        NO_LOGS - No hay logs disponibles.
    data: Array<Log>
	