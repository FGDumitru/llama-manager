<?php
require_once "vendor/autoload.php";

use FGDumitru\LlamaManager\Utils\Updater;

$updater = new Updater();
$updater->execute();