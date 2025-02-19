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
            echo('WARNING: [config.yml] file does not exist. Using the default [config.example.yml] file.' . PHP_EOL);
            if (!file_exists('config.example.yml')) {
                throw new Exception('ERROR: Default config.example.yml not found.');
            }
            self::$configuration = Yaml::parseFile("config.example.yml");
        } else {
            // Load and parse the config.yml file
            self::$configuration = Yaml::parseFile("config.yml");
        }

        return self::$configuration;
    }

    static public function getDir($type) {
        return self::$configuration['paths'][$type] ?? null;
    }

}