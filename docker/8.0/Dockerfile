FROM php:8.0.0alpha2-fpm

RUN apt-get update -y

RUN apt-get install -y wget gnupg && \
    wget -q -O - https://packages.blackfire.io/gpg.key | apt-key add - && \
    echo "deb http://packages.blackfire.io/debian any main" | tee /etc/apt/sources.list.d/blackfire.list

RUN apt-get update -y

RUN apt-get install -y blackfire-agent && \
    apt-get install -y blackfire-php

