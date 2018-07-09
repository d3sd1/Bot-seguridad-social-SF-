¿Cómo hacer una llamada al REST?
--------
Con PHP, Curl es una buena librería interna. Recordar que si se usa CURL, habrá que configurar
para que use o no el certificado SSL para el HTTPs si procede, es un parámetro simple.

¿Qué tengo que esperar que me devuelva el REST?
-------
Se devolverá un código de estado, con un json en el **BODY** similar al siguiente:

```
{
    "message": "",
    "data": ""
}
```

La clave "message" se refiere al código de estado de la petición.
Data, a los datos a retornar (si procede, puede incluso estar omitido).

Los códigos de estado para las respuestas de las peticiones son los siguientes:
```
200 - Todo OK
400 - Los datos introducidos no son válidos.
500 - Algo ha salido mal en el servidor, presumiblemente, un error de programación.
```

Los valores para la clave "message" varían en función de la llamada. Los valores que este
puede devolver se documentan en cada apartado (por ejemplo, [en el archivo GET/baja.md](baja/GET.md).