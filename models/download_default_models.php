<?php

function downloadModels(string $inputFile, $modelsDir = '.'): array
{
    // Create models directory if needed
    if (!file_exists($modelsDir)) {
        mkdir($modelsDir, 0755, true);
    }

    // Read and process URLs
    $results = [];
    $urls = file($inputFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($urls as $url) {
        $url = trim($url);
        if (empty($url)) continue;

        // Extract filename from URL
        $filename = basename(parse_url($url, PHP_URL_PATH));
        if (empty($filename)) {
            $results[] = ['url' => $url, 'status' => 'error', 'message' => 'Invalid filename'];
            continue;
        }

        $targetPath = "$modelsDir/$filename";

        // Skip existing files
        if (file_exists($targetPath)) {
            $results[] = ['url' => $url, 'status' => 'already_downloaded', 'filename' => $filename];
            continue;
        }

        // Download with progress display
        try {
            $results[] = [
                'url' => $url,
                'status' => 'downloaded',
                'filename' => $filename,
                'message' => downloadWithProgress($url, $targetPath)
            ];
        } catch (Exception $e) {
            $results[] = [
                'url' => $url,
                'status' => 'error',
                'filename' => $filename,
                'message' => $e->getMessage()
            ];
        }
    }

    return $results;
}

function downloadWithProgress(string $url, string $destination): string
{
    $fp = fopen($destination, 'w');
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_NOPROGRESS => false,
        CURLOPT_PROGRESSFUNCTION => function (
            $resource, $downloadSize, $downloaded, $uploadSize, $uploaded
        ) {
            if ($downloadSize > 0) {
                $progress = round(($downloaded / $downloadSize) * 100);
                echo "\rProgress: $progress% ($downloaded/$downloadSize bytes)";
            }
        }
    ]);

    if (!curl_exec($ch)) {
        fclose($fp);
        unlink($destination);
        throw new Exception('Download failed: ' . curl_error($ch));
    }

    curl_close($ch);
    fclose($fp);

    return "\nDownload completed successfully";
}

$result = downloadModels('models_list.txt');
echo json_encode($result, JSON_PRETTY_PRINT);