# Ver estado del bot
___

Descripción: Iniciar el bot.

Método HTTP: GET

URL REST: **/start**

Códigos message de respuesta:

200

    message: 
        SERVER_STARTED - Servidor iniciado correctamente.
        SERVER_NOT_STARTED - Servidor no iniciado correctamente.
500

    message:
        SERVER_NOT_CONFIGURED - El servidor SSH no se ha configurado.