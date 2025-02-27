FROM php:8.3-zts-bullseye

RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

RUN pear config-set php_ini "/usr/local/etc/php/php.ini"

RUN pecl install apcu

RUN apt-get update && apt-get install -y \
    libfreetype6-dev procps libonig-dev  \
    libgmp-dev libjpeg62-turbo-dev libmemcached-dev gcc gfortran \
    libpng-dev iputils-ping nano cron net-tools mc htop unzip \
    libopenblas-dev  liblapacke-dev re2c libmcrypt-dev build-essential libzstd-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install opcache

RUN pecl install memcached-3.1.5

RUN apt-get install libzip-dev -y
RUN pecl install zip

RUN docker-php-ext-install gd mysqli

RUN docker-php-ext-install  pdo_mysql


RUN docker-php-ext-install mbstring

RUN docker-php-ext-install bcmath gmp

# Install necessary tools for building extensions
RUN apt-get install -y git unzip libzip-dev

# Clone parallel extension repository
RUN git clone https://github.com/krakjoe/parallel.git

# Build and install parallel extension
RUN cd parallel && phpize && ./configure && make && make install

# Add parallel extension to PHP configuration
RUN echo "extension=parallel.so" >> /usr/local/etc/php/conf.d/parallel.ini

# Install pcntl extension
RUN docker-php-ext-install pcntl

RUN apt install ffmpeg -y

RUN apt-get update -y \
    && apt-get upgrade -y \
    && apt-get -y install build-essential \
        zlib1g-dev \
        libncurses5-dev \
        libgdbm-dev \
        libnss3-dev \
        libssl-dev \
        libreadline-dev \
        libffi-dev \
        libsqlite3-dev \
        libbz2-dev \
        wget \
    && export DEBIAN_FRONTEND=noninteractive \
    && apt-get purge -y imagemagick imagemagick-6-common

RUN cd /usr/src \
    && wget https://www.python.org/ftp/python/3.11.0/Python-3.11.0.tgz \
    && tar -xzf Python-3.11.0.tgz \
    && cd Python-3.11.0 \
    && ./configure --enable-optimizations \
    && make altinstall

RUN update-alternatives --install /usr/bin/python python /usr/local/bin/python3.11 1

# Install python dependencies using pip3
RUN apt-get update && \
    apt-get install -y libmariadb-dev python3-pip libfreetype6-dev libjpeg62-turbo-dev libpng-dev iputils-ping nano cron net-tools mc htop \
    libopenblas-dev  liblapacke-dev re2c libmcrypt-dev build-essential \
    && pip3 install --upgrade pip

RUN pip3 install \
    mysql-connector-python pandas numpy matplotlib tabulate argparse

RUN pip install yt-dlp

RUN apt-get install cmake -y

WORKDIR /app

#CMD [ "php", "updater.php" ]
CMD [ "bash"]