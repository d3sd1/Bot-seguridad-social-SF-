# Realizar una consulta de IPF
___

Descripción: Realiza una consulta del NAF mediante el IPF.

Método HTTP: POST

URL REST: **/consulta/ipf**

BODY: 

       {
            "naf": "281420515224",
            "callback_url": "{URL_CALLBACK}"
        }

* **naf**: Número de afiliación.

Códigos message de respuesta:

200

    message: 
        CREATED - Se ha creado la petición correctamente en la base de datos. En data, se devolverá la ID de la petición asociada.
        RETRIEVED - Se ha recuperado de la base de datos. En data, se devolverá la ID de la petición asociada.
    data: ID de la petición de alta. Útil para consultar su estado.
	
	
400

	message:
	    INVALID_OBJECT - El objeto es inválido.
	    UNCAUGHT_EXCEPTION - Excepción no controlada.