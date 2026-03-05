FROM ubuntu:22.04
LABEL Matheus Sesso <matheus.sesso@cfo.org.br>

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update --allow-insecure-repositories && apt-get install -y
RUN apt-get install software-properties-common -y
RUN add-apt-repository ppa:ondrej/php

RUN apt-get install -y build-essential \
    nano \
    unzip \
    curl \
	gnupg2 \
    locales \
    apache2 \
	php8.3 \
	php8.3-common \
	php8.3-mysql \
	php8.3-xml \
	php8.3-xmlrpc \
	php8.3-curl \
	php8.3-gd \
	php8.3-imagick \
	php8.3-cli \
	php8.3-dev \
	php8.3-imap \
	php8.3-mbstring \
	php8.3-opcache \
	php8.3-soap \
	php8.3-zip \
	php8.3-intl \
	php8.3-bcmath \
	php8.3-pgsql \
	php8.3-pspell \
	php8.3-redis \
	&& apt-get clean 

RUN locale-gen pt_BR.UTF-8

# Atualizar certificados SSL e instalar dependências para HTTPS
RUN apt-get install --reinstall ca-certificates -y
RUN update-ca-certificates

# Install Composer
RUN cd /usr/local/lib/ && curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

# Enable apache mod_rewrite
RUN a2enmod rewrite
RUN a2enmod actions

# Change AllowOverride from None to All (between line 170 and 174)
RUN sed -i '170,174 s/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Install pdo_sqlsrv (SQL Server Driver)
ENV ACCEPT_EULA=Y
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - 
RUN curl https://packages.microsoft.com/config/ubuntu/22.04/prod.list > /etc/apt/sources.list.d/mssql-release.list 
RUN apt-get update --allow-releaseinfo-change 
RUN ACCEPT_EULA=Y apt-get -y --no-install-recommends install msodbcsql17 unixodbc-dev 
RUN pecl install sqlsrv
RUN pecl install pdo_sqlsrv
RUN touch /etc/php/8.3/apache2/conf.d/30-pdo_sqlsrv.ini
RUN echo "\nextension=sqlsrv.so\n" >> /etc/php/8.3/apache2/conf.d/30-pdo_sqlsrv.ini
RUN echo "\nextension=pdo_sqlsrv.so\n" >> /etc/php/8.3/apache2/conf.d/30-pdo_sqlsrv.ini

ADD src/ /var/www/html/src

RUN chown $USER:www-data -R /var/www/html/src
RUN chmod u=rwX,g=srX,o=rX -R /var/www/html/src
RUN find /var/www/html/src -type d -exec chmod g=rwxs "{}" \;
RUN find /var/www/html/src -type f -exec chmod g=rws "{}" \;

# Copy config files
COPY config/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY config/php.ini /etc/php/8.3/apache2/php.ini

# Start the webserver
RUN service apache2 restart

EXPOSE 80

ENTRYPOINT ["apache2ctl", "-D", "FOREGROUND"]