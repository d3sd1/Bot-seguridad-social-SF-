# Realizar anulación alta (consolidada)
___

Descripción: Realiza la petición de alta para una solicitud consolidada, es decir, en la misma fecha
o después del inicio del contrato.

Método HTTP: POST

URL REST: **/alta/anulacion/consolidada**

BODY: 

    {
        "naf": "123456789012",
        "cca": "WORKOUT"
    }

* **naf**: Número de afiliación a la seguridad social.
* **cca**: Cuenta de cotización de la empresa para el alta del trabajador. Valores válidos: [ver aquí](../../data/data-cuentas-cotizacion.json).

Códigos message de respuesta:

200

    message: 
        CREATED - Se ha creado la petición correctamente en la base de datos. En data, se devolverá la ID de la petición asociada.
        RETRIEVED - Se ha recuperado de la base de datos. En data, se devolverá la ID de la petición asociada.
    data: ID de la petición de alta. Útil para consultar su estado.
	
	
400

	message:
	    INVALID_OBJECT - El objeto introducido por el body no se ha podido serializar.
        UNCAUGHT_EXCEPTION - Excepción no controlada.
        CONTRACT_ACCOUNT_NOT_FOUND - La cuenta introducida de cotización no existe.
	
500