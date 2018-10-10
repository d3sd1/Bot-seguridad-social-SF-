# Ver estado del bot
___

Descripción: Detener el bot.

Método HTTP: GET

URL REST: **/close**

Códigos message de respuesta:

200

    message: 
        SERVER_CLOSED - Servidor detenido correctamente.
        SERVER_NOT_CLOSED - Servidor no detenido correctamente.
500

    message:
        SERVER_NOT_CONFIGURED - El servidor SSH no se ha configurado.