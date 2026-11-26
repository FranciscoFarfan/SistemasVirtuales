FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       apache2 \
       php \
       libapache2-mod-php \
       php-mysql \
       php-mbstring \
    && rm -rf /var/lib/apt/lists/* /var/www/html/index.html

RUN sed -i 's/DirectoryIndex index.html/DirectoryIndex index.php index.html/' /etc/apache2/mods-enabled/dir.conf    

COPY index.html estilos.css /var/www/html/
COPY info.php /var/www/html/

CMD ["apache2ctl", "-D", "FOREGROUND"]
