FROM php:7.2-apache

ARG BUILD_ENV=prod

RUN apt-get update && apt-get install -y \
  git \
  libgmp-dev \
  libpng-dev \
  libz-dev \
  unzip \
  wget \
  zip
RUN apt-get clean

RUN docker-php-ext-install mysqli gd gettext gmp zip
RUN a2enmod rewrite
RUN a2enmod headers

# Use the default production configuration
# RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
RUN sed -ri -e 's!expose_php = On!expose_php = Off!g' "$PHP_INI_DIR/php.ini-production"
RUN if [ "$BUILD_ENV" = "prod" ]; \
  then cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"; \
  fi

# Use production apache configuration
COPY docker/security.conf /etc/apache2/conf-available/security.conf
COPY docker/000-default.conf /etc/apache2/sites-enabled/000-default.conf

# Install Composer
WORKDIR /tmp
RUN wget https://raw.githubusercontent.com/composer/getcomposer.org/ba13e3fc70f1c66250d1ea7ea4911d593aa1dba5/web/installer -O composer-setup.php -q
RUN php composer-setup.php --install-dir=/usr/local/bin/
RUN php -r "unlink('composer-setup.php');"

# Composer install
WORKDIR /var/www
COPY composer.json /var/www
COPY composer.lock /var/www
RUN php /usr/local/bin/composer.phar install -n

# Disable registration
RUN touch /var/www/registration-disabled

# Pear installs
RUN pear install Cache_Lite

COPY public/ /var/www/html/
WORKDIR /var/www/html
