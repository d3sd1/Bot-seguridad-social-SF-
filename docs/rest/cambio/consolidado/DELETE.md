# Eliminar cambio de contrato consolidado
___

Descripción: Elimina un cambio de contrato consolidado de la cola de procesos.

Método HTTP: DELETE

URL REST: **/cambio/contrato/consolidado/{id}**

Variables path param: 

    {id} => ID de el alta que se devolvió tras crear la petición por POST.

Códigos message de respuesta:

200

    message: 
        DELETE_STATUS - Devuelve si se consiguió eliminar
    
    data: Bool.
	
	
400

	message:
	    NOT_FOUND - No se ha encontrado en la base de datos.
	
500