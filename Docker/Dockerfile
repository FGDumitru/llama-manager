# Use CUDA base image
FROM nvidia/cuda:12.6.0-cudnn-devel-ubuntu22.04

# Set environment variables
ENV TZ=Europe/Bucharest \
    DEBIAN_FRONTEND=noninteractive \
    PHP_MAJOR_MINOR=8.1

# Configure timezone and install system packages
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone && \
    apt-get update && apt-get install -y --no-install-recommends \
    # System dependencies
    software-properties-common \
    apt-transport-https \
    ca-certificates \
    curl \
    dirmngr \
    git \
    unzip \
    zip \
    nano \
    net-tools \
    iputils-ping \
    htop \
    mc \
    cron \
    ffmpeg \
    # Build essentials
    build-essential \
    gcc \
    gfortran \
    cmake \
    ccache \
    # PHP development
    php$PHP_MAJOR_MINOR \
    php$PHP_MAJOR_MINOR-cli \
    php$PHP_MAJOR_MINOR-dev \
    php$PHP_MAJOR_MINOR-mysql \
    php$PHP_MAJOR_MINOR-mbstring \
    php$PHP_MAJOR_MINOR-gd \
    php$PHP_MAJOR_MINOR-gmp \
    php$PHP_MAJOR_MINOR-bcmath \
    php$PHP_MAJOR_MINOR-zip \
    php$PHP_MAJOR_MINOR-intl \
    php-pear \
    # Library dependencies
    libfreetype6-dev \
    libjpeg-turbo8-dev \
    libpng-dev \
    libonig-dev \
    libgmp-dev \
    libmemcached-dev \
    libzip-dev \
    libicu-dev \
    liblapacke-dev \
    libopenblas-dev \
    libzstd-dev \
    # Python
    python3-pip nvtop

# Install PECL extensions
RUN pecl install apcu memcached-3.1.5 && \
    echo "extension=apcu.so" > /etc/php/$PHP_MAJOR_MINOR/cli/conf.d/20-apcu.ini && \
    echo "extension=memcached.so" > /etc/php/$PHP_MAJOR_MINOR/cli/conf.d/20-memcached.ini

# Install Python packages
RUN pip install --no-cache-dir --upgrade pip && \
    pip install --no-cache-dir \
    mysql-connector-python \
    pandas \
    numpy \
    matplotlib \
    tabulate \
    argparse \
    yt-dlp

# Aliases python3 to python
RUN ln -s /usr/bin/python3 /usr/local/bin/python

RUN apt install python3-venv -y

RUN apt install libsdl2-dev wget -y

RUN pip install uv

RUN mkdir -p miniconda3 && wget https://repo.anaconda.com/miniconda/Miniconda3-latest-Linux-x86_64.sh && \
    bash Miniconda3-latest-Linux-x86_64.sh -b -u -p miniconda3 && rm Miniconda3-latest-Linux-x86_64.sh

RUN bash -c 'source /miniconda3/bin/activate; which conda ; conda init --all'

# Set working directory
WORKDIR /app

#CMD [ "php", "updater.php" ]

# Default command
CMD ["bash"]