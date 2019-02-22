Debugging bot
-------
Pantallazos:
====
Se pueden ver los pantallazos post-relleno de formulario en todas las operaciones en la ruta 
**/var/www/debug/{operacion}/{id}**, sustituyendo {operacion} por el nombre de la operación (alta, baja, etc.).

Sesiones del bot (DEPRECEATED)
=======
Se puede ver un log detallado de Selenium server para la sesión actual en 
**/var/www/debug/Selenium/{sessionId}**, o también mediante el [REST](/rest/bot/logs/selenium/GET.md).

Logs del bot
======
Se pueden consultar desde la base de datos o desde el rest.

Manager 
====
Permite reiniciar el servidor y consultar el estado del mismo en todo momento,
así como hacer debugging del mismo.

Versiones
===
La versión de Firefox debe ser: firefox-52.8.0-1.el7.centos.x86_64
La versión de Selenium (WebDriver) debe ser: 3.8.1.

Iniciar en modo debug:
===
Abrir 3 terminales, en cada una poner:

java -Dwebdriver.firefox.marionette=false -Dwebdriver.server.session.timeout=99999 -Dwebdriver.gecko.driver="/usr/bin/geckodriver" -jar /var/www/drivers/selenium-server-standalone-3.14.0.jar -timeout 99999 -port 50902 --debug


geckodriver -v --jsdebugger

php bin/console start-bot

Cosas raras del certificado, si está instalado
==========
Hay que desbloquear el key store. Para ello, abre chrome, entra al sistema red, y te pedirá contraseña root.