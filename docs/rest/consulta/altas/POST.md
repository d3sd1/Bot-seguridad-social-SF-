# Realizar una consulta de altas en un CCC
___

Descripción: Realiza una consulta de altas en un CCC interno. 

Método HTTP: POST

URL REST: **/consulta/altas**

BODY: 

    {
         "cca": "WORKOUT",
         "callback_url": "{URL_CALLBACK}"
    }

* **cca**: Cuenta de cotización de la empresa para el alta del trabajador. Valores válidos: [ver aquí](../../data/data-cuentas-cotizacion.json).

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