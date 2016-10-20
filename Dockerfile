FROM php:7.0-cli
RUN apt update
RUN apt install -y git unzip

RUN mkdir /code
WORKDIR /code

COPY composer.json composer.json

RUN curl -sS https://getcomposer.org/installer | php
RUN php composer.phar install
