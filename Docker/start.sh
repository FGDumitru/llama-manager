#!/bin/bash

# Start Open-WebUI in the background
source /miniconda3/bin/activate
conda activate open-web_venv
open-webui serve --port 8081 &

# Start PHP script
cd /app
exec php updater.php
