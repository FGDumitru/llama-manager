<?php

namespace FGDumitru\LlamaManager\Utils;

class GitClonesProcessor
{

    /**
     * @throws \Exception
     */
    public static function ProcessAssets(string $entity, array $entry): void
    {

        $gitRepo = $entry['git-clone']['repo'];
        $gitBranch = $entry['git-clone']['branch'];
        $gitClonesDir = Configuration::getDir('git-clones-dir') . DIRECTORY_SEPARATOR . $entity;

        self::cloneRepository($entity, $gitRepo, $gitBranch, $gitClonesDir, $entry);


    }

    /**
     * @throws \Exception
     */
    public static function cloneRepository($entity, string $gitRepo, string $gitBranch, string $gitCloneDir, array $entry)
    {

        if (!is_dir($gitCloneDir) && !mkdir($gitCloneDir, 0777, TRUE)) {
            throw new \Exception("Failed to create directory '$gitCloneDir'.");
        }

        if (!is_dir($gitCloneDir . DIRECTORY_SEPARATOR . $entity)) {
            echo "$entity repo: Folder '$gitCloneDir' does not exist - will be cloned." . PHP_EOL;
            $envActivateCommand = 'python -m venv ' . $entity . '_venv';

            $command = "cd $gitCloneDir && git clone $gitRepo $entity && cd $entity && git checkout $gitBranch && $envActivateCommand";

            $success = exec($command, $output, $exitCode);
            if (!$success || $exitCode !== 0) {
                throw new \Exception("Failed to clone repository '$gitCloneDir'.");
            }
            $lastCommitHash = self::getCurrentRepoHash($gitCloneDir, $entity);
            echo "$entity repo: Setting current hash: $lastCommitHash." . PHP_EOL;
            file_put_contents($gitCloneDir . DIRECTORY_SEPARATOR . $entity . '.hash.txt', $lastCommitHash . PHP_EOL . time());

            $changeFolderCommand = "cd $gitCloneDir && cd $entity";
            foreach ($entry['post-clone-commands'] as $command) {
                $command = $changeFolderCommand . " && $envActivateCommand && " . $command;
                echo "$entity repo: executing command: [ $command ]" . PHP_EOL;
                $success = exec($command, $output, $exitCode);

                if (!$success || $exitCode !== 0) {
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
                $command = "cd $gitClonedDir && git pull origin " . $gitBranch;
                $success = exec($command, $output, $exitCode);
                if (!$success || $exitCode !== 0) {
                    throw new \Exception("Failed to update repository '$gitCloneDir'.");
                }
                echo "$entity repo: Update complete. Current head at $originCommitHash" . PHP_EOL;

            }
        }

    }


    private static function getCurrentRepoHash(string $gitCloneDir, string $entity): string
    {
        $gitDir = $gitCloneDir . DIRECTORY_SEPARATOR . $entity;
        $success = exec("cd $gitDir && git rev-parse --verify HEAD", $output, $exitCode);
        if (!$success || $exitCode !== 0) {
            throw new \Exception("Failed to get last commit hash for repository '$gitCloneDir'.");
        }
        return $success;
    }

    private static function getOriginRepoHash(string $gitCloneDir, string $entity): string
    {
        $gitDir = $gitCloneDir . DIRECTORY_SEPARATOR . $entity;
        $success = exec("cd $gitDir && git rev-parse --verify origin", $output, $exitCode);
        if (!$success || $exitCode !== 0) {
            throw new \Exception("Failed to get last commit hash for remote repository '$gitCloneDir'.");
        }
        return $success;
    }

}