FROM php:8.2-apache

RUN set -ex \
    && a2enmod rewrite \
    && apt-get update && apt-get install -y curl \
    # Install Node.js
    && curl -sL https://deb.nodesource.com/setup_24.x | bash - 

RUN printf "memory_limit = 512M\nupload_max_filesize = 16M\n" > /usr/local/etc/php/conf.d/docker-php-memlimit.ini

RUN apt-get update && apt-get install -y \
        nodejs \
        zip \
        wget \
        curl \
        unzip \
        vim \
        libxml2-dev \
        libldap2-dev \
        libzip-dev \
        libpng-dev \
        libicu-dev \
        libonig-dev \
        libpq-dev \
        libcurl4-openssl-dev

RUN corepack enable 
RUN corepack prepare yarn@4.5.1 --activate 
    # Install PHP extensions
RUN docker-php-ext-install \
        curl \
        gd \
        zip \
        xml \
        pdo \
        pdo_mysql \
        ldap \
        mysqli \
        intl \
        mbstring \
        soap \
    # Install Composer
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apt-get clean && rm -rf /var/lib/apt/lists/*


COPY . /var/www/html/

# Make everything writable by Apache (www-data)
RUN chown -R www-data:www-data /var/www/html

WORKDIR /var/www/html

RUN sed -i 's/"@apollo\/client": ".*"/"@apollo\/client": "3.8.0"/g' package.json && sed -i 's/"apollo-angular": ".*"/"apollo-angular": "6.0.0"/g' package.json


RUN node -e "const pkg=require('./package.json'); pkg.resolutions={'webpack':'5.95.0'}; require('fs').writeFileSync('package.json', JSON.stringify(pkg, null, 2));"

RUN COMPOSER_MEMORY_LIMIT=-1 composer install



RUN yarn install
RUN yarn build

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
# For security
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf


WORKDIR /var/www/html/public

EXPOSE 80
