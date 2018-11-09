FROM kevinpark/nginx-php-redis
MAINTAINER Kevin Park<kevinpark@webace.co.kr>

RUN apt-get -y install openssl

RUN mkdir /var/www/cfg
RUN mkdir /var/www/lib

ADD cfg /var/www/cfg
ADD src /var/www/html
ADD lib /var/www/lib
ADD default.conf /etc/nginx/sites-available/default.conf

VOLUME ["/var/www/cfg"]
VOLUME ["/var/www/html"]
