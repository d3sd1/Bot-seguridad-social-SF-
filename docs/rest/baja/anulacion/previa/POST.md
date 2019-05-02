# Realizar anulación baja (previa)
___

Descripción: Realiza la petición de baja para una solicitud previa, es decir, antes de que se efectúe la fecha
de inicio de contrato.

Método HTTP: POST

URL REST: **/baja/anulacion/previa**

BODY: 

    {
         "naf": "461072254410",
         "fra": "2018-06-14",
         "cca": "WORKOUT"
    }

* **naf**: Número de afiliación a la seguridad social.
* **frb**: Fecha real de la baja.
* **cca**: Cuenta de cotización de la empresa para la baja del trabajador. Valores válidos: [ver aquí](../../data/data-cuentas-cotizacion.json).

Códigos message de respuesta:

200

    message: 
        CREATED - Se ha creado la petición correctamente en la base de datos. En data, se devolverá la ID de la petición asociada.
        RETRIEVED - Se ha recuperado de la base de datos. En data, se devolverá la ID de la petición asociada.
    data: ID de la petición de baja. Útil para consultar su estado.
	
	
400

	message:
	    INVALID_OBJECT - El objeto introducido por el body no se ha podido serializar.
        DATE_EXPIRE_INVALID - La fecha de la baja no puede superar los 60 días desde hoy.
        UNCAUGHT_EXCEPTION - Excepción no controlada.
        DATE_PASSED - La fecha introducida para la baja excede el límite de 3 días otorgado por la seguridad social.
        CONTRACT_ACCOUNT_NOT_FOUND - La cuenta introducida de cotización no existe.
	
500