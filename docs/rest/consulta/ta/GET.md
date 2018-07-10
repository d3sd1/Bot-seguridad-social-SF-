# Consultar el estado de una consulta de TA
___

Descripción: Consultar el estado de una consulta de duplicado de TA.

Método HTTP: GET

URL REST: **/consulta/ta/{id}**

Variables path param: 

    {id} => ID de el alta que se devolvió tras crear la petición por POST.

Códigos message de respuesta:

200

    message: [Puedes ver una lista completa de todos los estados aquí.](https://bitbucket.org/andreiwo/ss-bot/src/master/docs/data/data-estados.json).
    data: Base64 con el fichero de duplicado de TA.
	
	
400

	message:
	    NOT_FOUND - No se ha encontrado en la base de datos.
