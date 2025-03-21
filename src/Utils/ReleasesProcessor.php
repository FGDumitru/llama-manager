<?php

namespace FGDumitru\LlamaManager\Utils;

use ZipArchive;

class ReleasesProcessor
{

    /**
     * @throws \Exception
     */
    public static function ProcessAssets(string $entity, array $latestRelease, array $entry, bool $updateExisting): int
    {
        $matchedEntries = 0;
        $binaryType = $entry['git-release']['type'];
        $binarySubtype = $entry['git-release']['subtype'];
        $tagName = $latestRelease['tag_name'];

        $archivePattern = $entry['git-release']['archive-pattern'][$binaryType]['subtypes'][$binarySubtype];

        $regexPattern = '/^' . str_replace(['*', '?'], ['.*', '.'], $archivePattern) . '$/';

        foreach ($latestRelease['assets'] as $asset) {

            $browserDownloadUrl = $asset['browser_download_url']; //sample value: https://github.com/ggml-org/llama.cpp/releases/download/b4739/cudart-llama-bin-win-cu11.7-x64.zip

            // Extract the filename from the URL
            $filename = basename($browserDownloadUrl);

            // Check if the $archivePattern matches the $filename
            if (preg_match($regexPattern, $filename)) {
                $matchedEntries++;
                self::downloadAsset($entity, $browserDownloadUrl, $tagName, $asset, $updateExisting);
            }
        }

        return $matchedEntries;
    }

    /**
     * @throws \Exception
     */
    private static function downloadAsset($entity, $browserDownloadUrl, $tagName, $asset, $updateExisting): bool
    {
        $size = $asset['size'];
        $created_at = $asset['created_at'];

        // Ensure we have the assets directory created.
        $directoryPath = Configuration::getDir('assets-dir') . DIRECTORY_SEPARATOR .$entity;

        if (!is_dir($directoryPath) && !mkdir($directoryPath, 0777, true)) {
            throw new \Exception("Failed to create directory '$directoryPath'.");
        }

        $currentTag = '';

        if (file_exists($directoryPath . DIRECTORY_SEPARATOR . 'tag.txt')) {
            $currentTag = file_get_contents($directoryPath . DIRECTORY_SEPARATOR . 'tag.txt');
        }

        $basename = basename($browserDownloadUrl);

        if ($currentTag !== $tagName) {

            if ('' !== $currentTag) {
                echo PHP_EOL . "INFO: Update found for [$entity]. Old tag [$currentTag] - Current tag [$tagName]." . PHP_EOL;
                if (!$updateExisting) {
                    echo "INFO: Update is disabled for [$entity]." . PHP_EOL;
                    return FALSE;
                }
            } else {
                echo PHP_EOL . "INFO: Installing [$entity]. Current tag [$tagName]." . PHP_EOL;
            }
            echo "Asset size: [$size] bytes" . PHP_EOL;
            echo "Release date: [$created_at]" . PHP_EOL;

            $releaseContent = file_get_contents($browserDownloadUrl);
            $releaseBinary = Configuration::getDir('binaries-dir') . DIRECTORY_SEPARATOR . $entity;

            self::emptyDirectory($directoryPath);
            self::emptyDirectory($releaseBinary);

            file_put_contents($directoryPath . DIRECTORY_SEPARATOR . $basename, $releaseContent);
            file_put_contents($directoryPath . DIRECTORY_SEPARATOR . 'tag.txt', $tagName);
            self::unzipFile($directoryPath . DIRECTORY_SEPARATOR . $basename, $releaseBinary . DIRECTORY_SEPARATOR . 'release');
        } else {
            echo "No update required for [$entity]. Latest version is already available [$tagName]." . PHP_EOL;
        }

        echo PHP_EOL . '----------------------------------' . PHP_EOL;

        return true;
    }

    private static function unzipFile($zipFilePath, $extractToDirectory): void
    {
        if (!file_exists($zipFilePath)) {
            throw new \Exception("File does not exist: $zipFilePath");
        }

        // Create the target directory if it doesn't exist
        if (!is_dir($extractToDirectory)) {
            mkdir($extractToDirectory, 0777, true);
        }

        // Determine the file type and prepare the appropriate command
        if (preg_match('/\.tar\.gz$/i', $zipFilePath)) {
            $command = "tar -xzf " . escapeshellarg($zipFilePath) . " -C " . escapeshellarg($extractToDirectory) . " 2>&1";
        } else {
            $command = "unzip -o " . escapeshellarg($zipFilePath) . " -d " . escapeshellarg($extractToDirectory) . " 2>&1";
        }

        // Execute the command
        exec($command, $output, $returnVar);

        // Check the return status of the command
        if ($returnVar === 0) {
            echo "Files extracted successfully to '$extractToDirectory'.";
        } else {
            echo "Failed to extract files. Command output: " . implode("\n", $output);
        }
    }


  private static function unzipFilePHP($zipFilePath, $extractToDirectory): void
    {
        if (!file_exists($zipFilePath)) {
            throw new \Exception("ZIP file does not exist: $zipFilePath");
        }

        $zip = new ZipArchive;

        if ($zip->open($zipFilePath) === TRUE) {
            if (!is_dir($extractToDirectory)) {
                mkdir($extractToDirectory, 0777, true);
            }

            if ($zip->extractTo($extractToDirectory)) {
                echo "Files extracted successfully to '$extractToDirectory'.";
            } else {
                echo "Failed to extract files.";
            }

            $zip->close();
        } else {
            echo "Failed to open the ZIP file.";
        }

    }

    private static function emptyDirectory($dir): bool
    {
        $dir = rtrim($dir, DIRECTORY_SEPARATOR);

        if (!is_dir($dir)) {
            return false;
        }

        $scan = glob($dir . DIRECTORY_SEPARATOR . '*');

        if ($scan === false) {
            return false; // Handle failure in `glob`
        }

        foreach ($scan as $path) {
            if (is_dir($path)) {
                if (!self::emptyDirectory($path)) {
                    return false;
                }

                if (!rmdir($path)) {
                    return false;
                }
            } else {
                if (!unlink($path)) {
                    return false;
                }
            }
        }

        return true;
    }

}
