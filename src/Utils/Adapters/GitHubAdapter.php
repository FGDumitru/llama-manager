<?php

namespace FGDumitru\LlamaManager\Utils\Adapters;

use FGDumitru\LlamaManager\Utils\Configuration;

class GitHubAdapter implements AdapterInterface
{

    private $baseUrl = 'https://api.github.com/';
    private $config = null;


    public function __construct()
    {
        $this->config = Configuration::readConfiguration()['GitHub'] ?? [];
    }

    public function getRawLimits(): array
    {
        // TODO: Implement getRawLimits() method.
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