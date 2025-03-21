#!/bin/bash

# Start Open-WebUI in the background
source /miniconda3/bin/activate
conda activate open-web_venv
open-webui serve --port 8081 &

# Start PHP script
cd /app
git config --global --add safe.directory /app
composer install -n

cd models && php download_default_models.php

cd /app
exec php updater.php
