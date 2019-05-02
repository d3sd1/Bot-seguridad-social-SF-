# Realizar una baja
___

Descripción: Realiza una baja procesada por el bot contra la seguridad social. Inscripción en el Fichero General del empleado por cuenta ajena en la Cuenta de Cotización del empleador. 
             Se puede realizar de forma previa con 60 días de antelación a la fecha de alta real (o efectiva).
             Si una baja ya existía y aún no fue procesada y es similar a la anterior, se recuperará la actual en lugar de duplicarla.

Método HTTP: POST

URL REST: **/baja**

BODY: 

    {
         "naf": "321381672738",
         "ipt": "01",
         "ipf": "50334817F",
         "frb": "2018-05-30",
         "sit": "08",
         "ffv": "2018-09-30",
         "cca": "WORKOUT",
         "callback_url": "{URL_CALLBACK}"
    }

* **naf**: Número de afiliación a la seguridad social.
* **ipt**: Tipo de identificador a utilizar. Puede ser **dni** (Código 01), **pasaporte** (Código 02) o **nie** (Código 06).
* **ipf**: Identificador de persona física. Debe coincidir con el formato indicado en el campo ipt.
* sit: Situación actual del empleado (opcional). Por defecto es el código 93 (Baja normal).
* **frb**: Fecha real de la baja.
* ffv: Fecha de fin de vacaciones (opcional). Si no se desea poner ninguna, se debe poner null.
* **cca**: Cuenta de cotización de la empresa para el alta del trabajador. Valores válidos: [ver aquí](../../data/data-cuentas-cotizacion.json).

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
        CONTRACT_PARTIAL_COE - Si el contrato es de tipo parcial, se requiere su coeficiente, y que éste sea válido.
        UNCAUGHT_EXCEPTION - Excepción no controlada.
        DATE_PASSED - La fecha introducida para la baja excede el límite de 3 días otorgado por la seguridad social.
        CONTRACT_ACCOUNT_NOT_FOUND - La cuenta introducida de cotización no existe.
	
500