<?php

namespace FGDumitru\LlamaManager\Utils;

use Exception;
use Symfony\Component\Yaml\Yaml;

class Configuration
{
    /**
     * @var mixed
     */
    private static $configuration = null;

    /**
     * @throws Exception
     */
    public static function readConfiguration() {

        if (!is_null(self::$configuration)) {
            return self::$configuration;
        }

        if (!file_exists("config.yml")) {
            throw new Exception("config.yml file does not exist.");
        } else {
            // Load and parse the config.yml file
            self::$configuration = Yaml::parseFile("config.yml");
        }

        return self::$configuration;
    }

}