# Ver estado del bot
___

Descripción: Consultar el estado del bot.

Método HTTP: GET

URL REST: **/bot/status**

Códigos message de respuesta:

200

    message: 
        BOOTING - El servidor se está iniciando.
        CRASHED - El servidor ha tenido un error fatal.
        CRASHED_RELOADING - El servidor tuvo un error pero supo recuperarse, y está volviendo a cargar.
        OFFLINE - El servidor está desconectado.
        RUNNING - El servidor está procesando una petición.
        RUNNING_WITH_WARNINGS - El servidor está procesando una petición con posibles errores.
        UNKNOWN - El servidor tiene un estado desconocido.
	