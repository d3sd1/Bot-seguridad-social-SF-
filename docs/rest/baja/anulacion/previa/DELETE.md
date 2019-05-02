# Eliminar anulación baja previa de cola
___

Descripción: Elimina una anulación de baja previa de la cola de procesos.

Método HTTP: DELETE

URL REST: **/baja/anulacion/previa/{id}**

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