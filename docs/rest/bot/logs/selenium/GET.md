# Ver estado del bot
___

Descripción: Consultar los logs de selenium. Puede indicarse por path param el ID de la sesión a consultar.

Método HTTP: GET

URL REST: **/bot/logs/selenium**
URL REST: **/bot/logs/selenium/{id}**

Códigos message de respuesta:

200

    message: 
        LOADED - Logs cargados.
        SELENIUM_SESSION_LOGFILE_ERROR - No hay logs disponibles para la sesión indicada.
    data: Array<Log>
	