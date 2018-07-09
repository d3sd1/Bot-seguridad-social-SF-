Instalación en servidor de producción
---------
````
cd /var/www && composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod --no-debug
````