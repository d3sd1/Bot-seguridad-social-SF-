![Logo](https://www.aspec.es/cms/files/ASPEC_WORKOUT_EVENTS_Logo_460x300.jpg)

Bot de la seguridad social
===========================
Desarrollado por Andrei García Cuadra para Workout.
___

Workout realiza un número ingente de movimientos y consultas en la Seguridad Social (S.S.) para la gestión de los periodos de alta de sus empleados. Estos movimientos y consultas pueden comunicarse a la S.S. por portal y por la aplicación Winsuite. 

Por lo general los movimientos se llevan a cabo en el portal por la inmediatez de la confirmación del movimiento, teniendo un retraso de varias horas en el caso de Winsuite. Esta comunicación se realiza de forma manual, rellenando un formulario por cada movimiento o consulta y confirmando visualmente el resultado. 

Workout requiere de una herramienta de automatización que permita realizar esta labor mecánica de comunicación y consulta de movimientos, a partir de los movimientos y necesidad de información de la herramienta de gestión. 

___

El bot procesa peticiones mediante peticiones web (PHP WEB).
Estas peticiones son, mediante concurrencia, procesadas internamente por el bot (PHP CLI).
Para procesar dichas peticiones, se hace uso de Selenium Webdriver con un navegador Chrome headless.

Códigos de respuesta a peticiones HTTP:
```
200 - Petición satisfactoria
400 - Error de entrada (cliente)
500 - Error interno
```
> Todas las respuestas llevan asociado un mensaje en el body.
> Este mensaje es compartido para cada operación y delimita su estado.

# Documentación completa: [/docs](https://bitbucket.org/andreiwo/ss-bot/src/master/docs)

Ejemplo de petición HTTP:
```
POST /alta
	{
		"naf": "701111111111",
		"ipf": "01234567891",
		"reg": "0111",
		"ccc": "2811149794464",
		"nac": "14/11/1998",
		"sexo": "V",
		"tel": "685848171"
	}

RESPUESTA:
400 - MISSING_FIELDS
```
Otro ejemplo de petición HTTP, pero esta vez si el servidor va mal:
```
POST /alta
	{
		"naf": "701111111111",
		"ipf": "01234567891",
		"reg": "0111",
		"ccc": "2811149794464",
		"nac": "14/11/1998",
		"sexo": "V",
		"tel": "685848171"
	}

RESPUESTA:
500 - SELENIUM_RELOADING_CRASHED
```