FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       apache2 \
    && rm -rf /var/lib/apt/lists/* /var/www/html/index.html

COPY index.html /var/www/html/index.html

CMD ["apache2ctl", "-D", "FOREGROUND"]