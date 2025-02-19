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
        echo "Updater executed successfully!\n";
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    private function checkLatestVersion(): void
    {
        $entries = Configuration::readConfiguration();

        foreach ($entries as $key => $entry) {

            // Check if the updates have been disabled for this git project. Idem if the entry does not exist.
            if (!$entry['check-updates'] ?? false) {
                continue;
            }

            $gitReleasesLink = $entry['git-releases']['url'];

            try {
                $entriesArray = $this->fetchGitHubReleases($gitReleasesLink);

                $latestRelease = reset($entriesArray);

                ReleasesProcessor::ProcessAssets($key, $latestRelease, $entry);

            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
                throw $e;
            }

        }


    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    function fetchGitHubReleases($url) {
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
            throw new Exception("Guzzle HTTP Error: " . $e->getMessage());
        }
    }

}