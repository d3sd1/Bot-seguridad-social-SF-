# Realizar anulación baja (consolidada)
___

Descripción: Realiza la petición de baja para una solicitud consolidada, es decir, en la misma fecha
o después del inicio del contrato.

Método HTTP: POST

URL REST: **/alta/anulacion/consolidada**

BODY: 

    {
        "naf": "281252179000",
        "ipt": "01",
        "ipf": "53419551G",
        "cca": "WORKOUT",
        "frb": "2018-06-12"
    }

* **naf**: Número de afiliación a la seguridad social.
* **ipt**: Tipo de identificador a utilizar. Puede ser **dni** (Código 01), **pasaporte** (Código 02) o **nie** (Código 06).
* **ipf**: Identificador de persona física. Debe coincidir con el formato indicado en el campo ipt.
* **cca**: Cuenta de cotización de la empresa para el alta del trabajador. Valores válidos: [ver aquí](../../data/data-cuentas-cotizacion.json).
* **frb**: Fecha real de la baja.

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
        DATE_PASSED - La fecha introducida para el alta es anterior a la actual.
	
500