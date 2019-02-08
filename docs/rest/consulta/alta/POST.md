# Realizar una consulta de consulta de ALTA
___

Descripción: Realiza una consulta de duplicado de TA.

Método HTTP: POST

URL REST: **/consulta/alta**

BODY: 

    {
         "naf": "321381672739",
         "frc": "2018-07-29",
         "cca": "WORKOUT"
    }

* **naf**: Número de afiliación.
* **frc**: Fecha real de la consulta. Esta fecha debe ser la más aproximada, sin pasarse (menor o igual). Es decir, para consultar sobre la fecha 2018-07-09, pondremos dicha fecha.
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
	    INVALID_DATE - Formato de fecha inválido.
	    UNCAUGHT_EXCEPTION - Excepción no controlada.