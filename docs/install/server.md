# 1. MYSql
Seguir el siguiente tutorial  (versi�n 5.7)
https://www.tecmint.com/install-latest-mysql-on-rhel-centos-and-fedora/
# 2. Apache
Instalar apache de forma normal.
Tutorial: https://www.liquidweb.com/kb/how-to-install-apache-on-centos-7/

IMPORTANTE:

Se ha de utilizar el siguiente comando para que el panel del bot funcione correctamente:

``
setsebool -P httpd_can_network_connect=1
``
# 3.PHP
Para la instalaci�n de PHP necesitamos sus complementos PTrheads y ZMQ pusher,
para que funcione la concurrencia y los sockets internos.

Esto se puede lograr simplificar con el siguiente script (CentOS 6.9 - PHP 7.2.5):

```
sudo -s
cd /tmp
rpm -Uvh https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
rpm -Uvh https://mirror.webtatic.com/yum/el7/webtatic-release.rpm
yum --nogp install -y --enablerepo=webtatic-testing \
php72w php72w-cli php72w-common php72w-devel \
php72w-gd php72w-intl php72w-mbstring php72w-mcrypt \
php72w-mysqlnd php72w-odbc php72w-opcache php72w-pdo \
php72w-pdo_dblib php72w-pear php72w-pgsql php72w-pspell \
php72w-soap php72w-xml php72w-xmlrpc php72w-bcmath
git clone https://github.com/krakjoe/pthreads.git
cd pthreads
zts-phpize
./configure --with-php-config=/usr/bin/zts-php-config
make
cp modules/pthreads.so /usr/lib64/php-zts/modules/.
echo extension=pthreads.so > /etc/php-zts.d/pthreads.ini

#zmq en CLI
git clone https://github.com/mkoppanen/php-zmq.git
cd php-zmq
zts-phpize
./configure --with-php-config=/usr/bin/zts-php-config
make

cp modules/zmq.so /usr/lib64/php-zts/modules/.
echo extension=zmq > /etc/php-zts.d/zmq.ini
```