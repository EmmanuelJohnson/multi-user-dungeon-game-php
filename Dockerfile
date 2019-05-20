FROM tutum/apache-php

RUN apt-get update && apt-get install -y -q apache2 git php5 php5-dev php-pear phpunit

RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer

RUN pecl install grpc
ENV GOOGLE_APPLICATION_CREDENTIALS multi-user-dungeon-firebase-adminsdk-fi2hw-4a23306b15.json

ADD . /app

EXPOSE 8000
EXPOSE 80
