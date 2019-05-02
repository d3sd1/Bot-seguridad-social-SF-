# Realizar una consulta de NAF
___

Descripción: Realiza una consulta del NAF mediante el IPF.

Método HTTP: POST

URL REST: **/consulta/naf**

BODY: 

       {
           "ipt": "01",
           "ipf": "53457069D",
           "ap1": "GARCIA",
           "ap2": "CUADRA",
           "callback_url": "{URL_CALLBACK}"
       }

* **ipt**: Tipo de identificador a utilizar. Puede ser **dni** (Código 01), **pasaporte** (Código 02) o **nie** (Código 06).
* **ipf**: Identificador de persona física. Debe coincidir con el formato indicado en el campo ipt.
* **ap1**: Primer apellido.
* **ap2**: Segundo apellido.

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