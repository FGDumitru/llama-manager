<?php

namespace FGDumitru\LlamaManager\Utils\Adapters;

interface AdapterInterface
{
    public function getRawLimits(): array;

    public function getBaseUrl(): string;

    public function overrideBaseUrl(string $url): void;

}
