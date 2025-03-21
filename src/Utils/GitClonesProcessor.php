<?php

namespace FGDumitru\LlamaManager\Utils;

class GitClonesProcessor
{

    /**
     * @throws \Exception
     */
    public static function ProcessAssets(string $entity, array $entry, $updateExisting): void
    {

        $gitRepo = $entry['git-sourcecode']['repo'];
        $gitBranch = $entry['git-sourcecode']['branch'];
        $gitClonesDir = Configuration::getDir('git-clones-dir') . DIRECTORY_SEPARATOR . $entity;

        self::cloneRepository($entity, $gitRepo, $gitBranch, $gitClonesDir, $entry, $updateExisting);


    }

    /**
     * @throws \Exception
     */
    public static function cloneRepository($entity, string $gitRepo, string $gitBranch, string $gitCloneDir, array $entry, $updateExisting)
    {

        $options = $entry['options'] ?? [];
        $usePythonVenv = in_array("python-venv", $options);

        if (!is_dir($gitCloneDir) && !mkdir($gitCloneDir, 0777, TRUE)) {
            throw new \Exception("Failed to create directory '$gitCloneDir'.");
        }

        if (!is_dir($gitCloneDir . DIRECTORY_SEPARATOR . $entity)) {
            echo "$entity repo: Folder '$gitCloneDir' does not exist - will be cloned." . PHP_EOL;

            if ($usePythonVenv) {
                $envCreateCommand = '&& python -m venv ' . $entity . '_venv';
                echo PHP_EOL . "Using a virtual Python environment: $envCreateCommand" . PHP_EOL;
            } else {
              $envCreateCommand = '';
            }

            $command = "pwd ; cd $gitCloneDir $envCreateCommand && git clone $gitRepo $entity && cd $entity && git checkout $gitBranch";

            echo PHP_EOL . $command . PHP_EOL;

            $success = exec($command, $output, $exitCode);
            if (!$success || $exitCode !== 0) {
                throw new \Exception("Failed to clone repository '$gitCloneDir'.");
            }
            $lastCommitHash = self::getCurrentRepoHash($gitCloneDir, $entity);
            echo "$entity repo: Setting current hash: $lastCommitHash." . PHP_EOL;
            file_put_contents($gitCloneDir . DIRECTORY_SEPARATOR . $entity . '.hash.txt', $lastCommitHash . PHP_EOL . time());

            $changeFolderCommand = "cd $gitCloneDir";

            if ($usePythonVenv) {
                $envActivateCommand = 'source ' . $entity . '_venv/bin/activate ; cd ' . $entity;
            }

            foreach ($entry['post-clone-commands'] ?? [] as $rawCommand) {

                $command = $changeFolderCommand . " && $rawCommand";

                if ($usePythonVenv) {
                    $command = $changeFolderCommand . " && bash -c '$envActivateCommand && " . $rawCommand . '\'';
                } else {
                  $command = $changeFolderCommand . " && cd $entity && " . $rawCommand;
                }

                echo "$entity repo: executing post-clone command: [ $rawCommand ]" . PHP_EOL;
                echo PHP_EOL . 'RAW: ' . $command . PHP_EOL;
                $success = exec($command, $output, $exitCode);

                if (FALSE === $success || $exitCode !== 0) {
                    throw new \Exception("Failed to execute command [ $command ].");
                }

            }

        } else {
            echo "$entity repo: Folder '$gitCloneDir' already exists and will not be cloned." . PHP_EOL;
            $lastCommitHash = self::getCurrentRepoHash($gitCloneDir, $entity);
            $originCommitHash = self::getOriginRepoHash($gitCloneDir, $entity);
            echo "$entity repo: Current hash: $lastCommitHash Origin hash: $originCommitHash" . PHP_EOL;

            if ($lastCommitHash !== $originCommitHash) {
                // We have an update.

                $gitClonedDir = $gitCloneDir . DIRECTORY_SEPARATOR . $entity;
                echo "$entity repo: Update detected." . PHP_EOL;

                if (!$updateExisting) {
                    echo "$entity repo: Updates is disabled." . PHP_EOL;
                    return;
                }

                $command = "cd $gitClonedDir && git pull origin " . $gitBranch;
                $success = exec($command, $output, $exitCode);
                if (FALSE === $success || $exitCode !== 0) {
                    throw new \Exception("Failed to update repository '$gitCloneDir'.");
                }

                if ($usePythonVenv) {
                    $envActivateCommand = 'source ' . $entity . '_venv/bin/activate';
                }

                $changeFolderCommand = "cd $gitCloneDir";
                foreach ($entry['post-update-commands'] ?? [] as $rawCommand) {

                    if ($usePythonVenv) {
                        $command = $changeFolderCommand . " && bash -c '$envActivateCommand && cd $entity && " . $rawCommand . '\'';
                    } else {
                        $command = $changeFolderCommand . " && cd $entity && $rawCommand";
                    }

                    echo "$entity repo: executing post-update command: [ $rawCommand ]" . PHP_EOL;
                    $success = exec($command, $output, $exitCode);

                    if (FALSE === $success || $exitCode !== 0) {
                        throw new \Exception("Failed to execute command [ $command ].");
                    }

                    echo "$entity repo: Update complete. Current head at $originCommitHash" . PHP_EOL;

                }
            }


        }
    }

    private
    static function getCurrentRepoHash(string $gitCloneDir, string $entity): string
    {
        $gitDir = $gitCloneDir . DIRECTORY_SEPARATOR . $entity;
        $success = exec("cd $gitDir && git rev-parse --verify HEAD", $output, $exitCode);
        if (!$success || $exitCode !== 0) {
            throw new \Exception("Failed to get last commit hash for repository '$gitCloneDir'.");
        }
        return $success;
    }

    private
    static function getOriginRepoHash(string $gitCloneDir, string $entity): string
    {
        $gitDir = $gitCloneDir . DIRECTORY_SEPARATOR . $entity;
        $success = exec("cd $gitDir && git fetch origin && git rev-parse --verify origin", $output, $exitCode);
        echo implode("\n", $output);

        if (!$success || $exitCode !== 0) {
            throw new \Exception("Failed to get last commit hash for remote repository '$gitCloneDir'.");
        }
        return $success;
    }

}