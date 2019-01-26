# Realizar un alta
___

Descripción: Realiza un alta procesada por el bot contra la seguridad social. Inscripción en el Fichero General del empleado por cuenta ajena en la Cuenta de Cotización del empleador. 
             Se puede realizar de forma previa con 60 días de antelación a la fecha de alta real (o efectiva).
             Si un alta ya existía y aún no fue procesada y es similar a la anterior, se recuperará la actual en lugar de duplicarla.

Método HTTP: POST

URL REST: **/alta**

BODY: 

    {
         "naf": "321381672738",
         "ipt": "01",
         "ipf": "50334817F",
         "fra": "2018-05-30",
         "gco": "08",
         "tco": "502",
         "coe": "500",
         "cca": "WORKOUT",
         "callback_url": "{URL_CALLBACK}"
    }

* **naf**: Número de afiliación a la seguridad social.
* **ipt**: Tipo de identificador a utilizar. Puede ser **dni** (Código 01), **pasaporte** (Código 02) o **nie** (Código 06).
* **ipf**: Identificador de persona física. Debe coincidir con el formato indicado en el campo ipt.
* sit: Situación actual del empleado. Opcional. Por defecto es el código 01 (Alta normal).
* **fra**: Fecha real del alta.
* **gco**: Grupo de cotización.
* **tco**: Tipo de contrato (Código). Éstos pueden ser consultados en el siguiente [enlace](../../data/data-contratos.json).
* **coe**: Coeficiente tiempo parcial (sólo necesario para trabajo a tiempo parcial). Valores válidos: [ver aquí](../../data/data-coeficientes.json).
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
        DATE_EXPIRE_INVALID - La fecha del alta no puede superar los 60 días desde hoy.
        CONTRACT_PARTIAL_COE - Si el contrato es de tipo parcial, se requiere su coeficiente, y que éste sea válido.
        UNCAUGHT_EXCEPTION - Excepción no controlada.
        DATE_PASSED - La fecha introducida para el alta es anterior a la actual.
        CONTRACT_ACCOUNT_NOT_FOUND - La cuenta introducida de cotización no existe.
	
500