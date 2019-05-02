# Consultar el estado de una solicitud de alta consolidada
___

Descripción: Consultar el estado de una solicitud de alta consolidada.

Método HTTP: GET

URL REST: **/alta/anulacion/consolidada/{id}**

Variables path param: 

    {id} => ID de el alta que se devolvió tras crear la petición por POST.

Códigos message de respuesta:

200

    message: [Puedes ver una lista completa de todos los estados aquí.](https://bitbucket.org/andreiwo/ss-bot/src/master/docs/data/data-estados.json).
    data: Información adicional a modo de string.
	
	
400

	message:
	    NOT_FOUND - No se ha encontrado en la base de datos.
	
500