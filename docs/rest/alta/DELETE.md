# Eliminar alta de cola
___

Descripción: Elimina una petición de alta de la cola de procesos.

Método HTTP: DELETE

URL REST: **/alta/{id}**

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