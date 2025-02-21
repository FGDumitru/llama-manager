<?php

namespace FGDumitru\LlamaManager\Utils\Adapters;

use FGDumitru\LlamaManager\Utils\Configuration;

class GitHubAdapter implements AdapterInterface
{

    private $baseUrl = 'https://api.github.com/';
    private $config = null;


    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->config = Configuration::readConfiguration()['GitHub'] ?? [];
    }

    public function getRawLimits(): array
    {
        $limits = file_get_contents($this->getBaseUrl());
        return json_decode($limits, true);
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function overrideBaseUrl(string $url): void
    {
        $this->baseUrl = $url;
    }
}