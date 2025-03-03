<?php

namespace FGDumitru\LlamaManager\Utils;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class Updater
{
    /**
     * @throws GuzzleException
     */
    public function execute(): void
    {
        $this->checkLatestVersion();
        echo  PHP_EOL . "Updater executed successfully!" . PHP_EOL;
        $this->executeOnFinishCommands();
    }

    private function executeOnFinishCommands()
    {
        $config = Configuration::readConfiguration();

        $canExecute = $config['general']['enable-on-finish'] ?? false;
        if ($canExecute) {
            foreach ($config['general']['on-finish'] ?? [] as $command) {
                echo PHP_EOL . "Running command: {$command}" . PHP_EOL;
                $result = shell_exec($command);
                echo $result . PHP_EOL;
            }
        }
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    private function checkLatestVersion(): void
    {
        $entries = Configuration::readConfiguration();

        foreach ($entries['builds'] as $key => $entry) {

            // Check if the updates have been disabled for this git project. Idem if the entry does not exist.
            if (!($entry['enabled'] ?? true)) {
                continue;
            }

            $updateExisting = $entry['check-updates'] ?? true;

            // Process releases.
            if ('git-binary' === $entry['update-type']) {
                $gitReleasesLink = $entry['git-release']['url'];
                try {
                    $entriesArray = $this->fetchGitHubReleases($gitReleasesLink, $updateExisting);
                } catch (Exception $e) {
                  echo $e->getMessage() . PHP_EOL;
                  $a = 1;
                }

                $latestRelease = reset($entriesArray);
                ReleasesProcessor::ProcessAssets($key, $latestRelease, $entry, $updateExisting);
                continue;
            }

            // Process git clone style repos.
            if ('git-sourcecode' === $entry['update-type']) {
                GitClonesProcessor::ProcessAssets($key, $entry, $updateExisting);
            }


        }


    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    function fetchGitHubReleases($url, $updateExisting)
    {
        $client = new Client();

        try {
            // Make a GET request to the GitHub API
            $response = $client->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'PHP'
                ]
            ]);

            // Get the body of the response and decode it as JSON
            $releasesJson = json_decode($response->getBody(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON Decode Error: " . json_last_error_msg());
            }

            return $releasesJson;
        } catch (RequestException $e) {
            // Handle any errors that occur during the request
            throw $e;
        }
    }

}