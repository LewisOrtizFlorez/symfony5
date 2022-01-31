#!/bin/bash
wget https://get.symfony.com/cli/installer -O - | bash
mv /root/.symfony/bin/symfony /usr/local/bin/symfony
cd /var/www/app/
symfony composer install
php-fpm