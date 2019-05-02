# Ver estado del bot
___

Descripción: Reinicia el servidor a nivel de sistema operativo y resetea el estado del bot a OFFLINE.

Método HTTP: POST

URL REST: **/server/restart**

Códigos message de respuesta:

200

    message: 
        SERVER_SO_RESTARTED - Petición de reinicio enviada correctamente.
500

    message:
        SERVER_NOT_CONFIGURED - El servidor SSH no se ha configurado.