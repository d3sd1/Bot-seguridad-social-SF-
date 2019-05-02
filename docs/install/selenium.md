# Instalar selenium
Tarea automatizada.  Tras la instalación, se puede lanzar selenium manualmente con el comando:

````
java -Dwebdriver.firefox.marionette=false -jar /var/www/drivers/selenium-server-standalone-3.8.1.jar -enablePassThrough false

````

Es importante, manualmente (en firefox), Menú > Preferencias > Avanzado > Certificados > Seleccionar uno automáticamente.

Automatización para la instalación de selenium y geckodriver, y la instalación del navegador firefox:

````
sudo yum install https://dl.google.com/linux/direct/google-chrome-stable_current_x86_64.rpm
sudo cp /var/www/drivers/chromedriver /usr/bin/chromedriver
sudo chmod +x /usr/bin/chromedriver
sudo yum install firefox 
sudo cp /var/www/drivers/gecko/0.20.1 /usr/bin/geckodriver
sudo chmod +x /usr/bin/geckodriver
sudo cp /var/www/drivers/gecko/0.20.1 /bin/geckodriver
sudo chmod +x /bin/geckodriver
sudo yum install Xvfb -y  
sudo yum install xorg-x11-fonts* -y
sudo yum install xorg-x11-server-Xvfb
sudo echo "#!/bin/bash  
        
      _kill_procs() {  
        kill -TERM $chrome  
        wait $chrome  
        kill -TERM $xvfb  
      }  
        
      # Setup a trap to catch SIGTERM and relay it to child processes  
      trap _kill_procs SIGTERM  
        
      XVFB_WHD=${XVFB_WHD:-1280x720x16}  
        
      # Start Xvfb  
      Xvfb :99 -ac -screen 0 $XVFB_WHD -nolisten tcp &  
      xvfb=$!  
        
      export DISPLAY=:99  
        
      chrome --no-sandbox --disable-gpu$@ &  
      chrome=$!  
        
      wait $chrome  
      wait $xvfb" > /usr/bin/xvfb-chrome
sudo echo "#!/bin/bash  
        
      _kill_procs() {  
        kill -TERM $firefox  
        wait $firefox
        kill -TERM $xvfb  
      }  
        
      # Setup a trap to catch SIGTERM and relay it to child processes  
      trap _kill_procs SIGTERM  
        
      XVFB_WHD=${XVFB_WHD:-1280x720x16}  
        
      # Start Xvfb  
      Xvfb :99 -ac -screen 0 $XVFB_WHD -nolisten tcp &  
      xvfb=$!  
        
      export DISPLAY=:99  
        
      firefox --no-sandbox --disable-gpu$@ &  
      firefox=$!  
        
      wait $firefox 
      wait $xvfb" > /usr/bin/xvfb-firefox
sudo chmod +x /usr/bin/xvfb-firefox
sudo chmod +x /usr/bin/xvfb-chrome
sudo ll /usr/bin/ | grep chrom
sudo ln -s /etc/alternatives/google-chrome /usr/bin/chrome  
sudo rm -rf /usr/bin/google-chrome 
sudo ln -s /usr/bin/xvfb-chrome /usr/bin/google-chrome
sudo ll /usr/bin/ | grep chrom

````