Este script automatiza la tarea de guardar el perfil actual de firefox y utilizarlo en el bot desde el momento de su uso.
**NO USAR COMO ROOT. USAR COMO SUDOER, EN EL PERFIL EN EL QUE SE HAN ESTABLECIDO LAS PREFERENCIAS (EL MISMO USUARIO DEL SISTEMA). ** 

     sudo mkdir -p /var/www/drivers/tmp && cd ~/.mozilla/firefox/*.default && sudo zip -x "lock"  -r /var/www/drivers/tmp/new-profile.zip ./ && rm /var/www/drivers/profile.zip.b64 && cat /var/www/drivers/tmp/new-profile.zip | base64 > /var/www/drivers/profile.zip.b64 && sudo rm -r -f /var/www/drivers/tmp && sudo chmod 777 -R /var/www/tmp