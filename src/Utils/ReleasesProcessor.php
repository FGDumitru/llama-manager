<?php

namespace FGDumitru\LlamaManager\Utils;

use ZipArchive;

class ReleasesProcessor
{

    public static function ProcessAssets(string $entity, array $latestRelease, array $entry): int
    {
        $matchedEntries = 0;
        $binaryType = $entry['git-releases']['type'];
        $binarySubtype = $entry['git-releases']['subtype'];
        $tagName = $latestRelease['tag_name'];

        // Sample value for $archivePattern: "llama-b*-bin-ubuntu-x64.zip"
        $archivePattern = $entry['git-releases']['archive-pattern'][$binaryType]['subtypes'][$binarySubtype];

        // Convert the wildcard pattern to a regex pattern
        // Replace * with .* and ? with .
        // Do not escape any characters that are part of the wildcard pattern
        $regexPattern = '/^' . str_replace(['*', '?'], ['.*', '.'], $archivePattern) . '$/';

        // Debugging: Output the regex pattern
        echo "Regex Pattern: $regexPattern\n";

        foreach ($latestRelease['assets'] as $asset) {
            $releaseName = $asset['name'];
            $size = $asset['size'];
            $browserDownloadUrl = $asset['browser_download_url']; //sample value: https://github.com/ggml-org/llama.cpp/releases/download/b4739/cudart-llama-bin-win-cu11.7-x64.zip

            // Extract the filename from the URL
            $filename = basename($browserDownloadUrl);

            // Check if the $archivePattern matches the $filename
            if (preg_match($regexPattern, $filename)) {
                $matchedEntries++;
                self::downloadAsset($entity, $browserDownloadUrl, $tagName);
            }
        }

        return $matchedEntries;
    }

    private static function downloadAsset($entity, $browserDownloadUrl, $tagName): void
    {

        // Ensure we have the assets directory created.
        $directoryPath = "assets/$entity";

        if (!is_dir($directoryPath) && !mkdir($directoryPath, 0777, true)) {
            throw new \Exception("Failed to create directory '$directoryPath'.");
        }

        $currentTag = '';

        if (file_exists($directoryPath . DIRECTORY_SEPARATOR . 'tag.txt')) {
            $currentTag = file_get_contents($directoryPath . DIRECTORY_SEPARATOR . 'tag.txt');
        }

        if ($currentTag !== $tagName) {

            $releaseContent = file_get_contents($browserDownloadUrl);
            $releaseBinary = 'binaries'. DIRECTORY_SEPARATOR . $entity;

            self::emptyDirectory($directoryPath);
            self::emptyDirectory($releaseBinary);

            $basename = basename($browserDownloadUrl);
            file_put_contents($directoryPath . DIRECTORY_SEPARATOR . $basename, $releaseContent);
            file_put_contents($directoryPath . DIRECTORY_SEPARATOR . 'tag.txt', $tagName);
            mkdir($releaseBinary . DIRECTORY_SEPARATOR .$tagName, 0777, true);
            self::unzipFile($directoryPath . DIRECTORY_SEPARATOR . $basename,$releaseBinary . DIRECTORY_SEPARATOR .$tagName );
        }

        $a = 1;
    }

    private static function unzipFile($zipFilePath, $extractToDirectory): bool
    {
        // Check if the ZIP file exists
        if (!file_exists($zipFilePath)) {
            echo "ZIP file does not exist: $zipFilePath";
            return false;
        }

        // Create a ZipArchive object
        $zip = new ZipArchive;

        // Open the ZIP file
        if ($zip->open($zipFilePath) === TRUE) {
            // Create the target directory if it does not exist
            if (!is_dir($extractToDirectory)) {
                mkdir($extractToDirectory, 0777, true);
            }

            // Extract the contents to the target directory
            if ($zip->extractTo($extractToDirectory)) {
                echo "Files extracted successfully to '$extractToDirectory'.";
            } else {
                echo "Failed to extract files.";
            }

            // Close the ZIP file
            $zip->close();
        } else {
            echo "Failed to open the ZIP file.";
            return false;
        }

        return true;
    }

    private static function emptyDirectory($dir) {
        // Check if the directory exists
        if (!is_dir($dir)) {
            echo "Directory does not exist: $dir";
            return false;
        }

        // Open the directory
        $scan = glob(rtrim($dir, '/') . '/*');
        foreach($scan as $index => $path) {
            // Check if it's a directory
            if (is_dir($path)) {
                // Recursively empty the directory
                self::emptyDirectory($path);
                // Remove the directory
                rmdir($path);
            } else {
                // Delete the file
                unlink($path);
            }
        }

        return true;
    }


}
