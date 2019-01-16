¿Cómo revisar una operación?
------
1. Se crea la operación. Tras esto se le asigna un ID único para cada tipo de petición. 
Este ID puede ser el mismo para un Alta, Baja, etc. Ya que son peticiones distintas, pero nunca será similar en el mismo tipo
de operación.

2. Se encolará la petición. Si el servidor está inactivo, este se reiniciará,
aunque las peticiones encoladas no resueltas (incluida esta), se marcarán como ABORTED.

3. Se mostrará la URL utilizada para la petición en un texto formateado así:
OP SS URL: {URL}.

4. Se mandarán los formularios corresponfientes a la operación.
Se mostrará que se ha rellenado el formulario, se ha enviado, y se han comprobado sus errores.
Si el envío sale mal, saldrá un error asociado. Si no, saldrá envío satisfactorio.

5. Puede que salga un error despreciable que se da en determinados casos, narra algo así como (IGNORAR ESTE ERROR SI TODO SALIÓ BIEN).
Se debe a que el driver de Selenium a veces loggea sin sentido. Hacer caso a su texto sólo si la operación salió mal.

6. Se dirá que la operación ha finalizado y se citará el estado final de la operación.

6B. Si la operación es errónea, se mostrará el error asociado. Este error lo da la seguridad social y no es un error del bot, sino en la operación de la seguridad social.