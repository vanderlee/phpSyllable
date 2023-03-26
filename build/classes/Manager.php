<?php

namespace Vanderlee\SyllableBuild;

abstract class Manager
{
    /**
     * @var int
     */
    protected $logLevel;

    public function __construct()
    {
        $this->logLevel = LOG_INFO;
    }

    /**
     * @param int $logLevel
     */
    public function setLogLevel($logLevel)
    {
        $this->logLevel = $logLevel;
    }

    protected function info($text)
    {
        $this->log($text, LOG_INFO);
    }

    protected function warn($text)
    {
        $this->log($text, LOG_WARNING);
    }

    protected function error($text)
    {
        $this->log($text, LOG_ERR);
    }

    protected function log($text, $logLevel)
    {
        if ($logLevel <= $this->logLevel) {
            echo "$text\n";
        }
    }

    /**
     * @param string $command      CLI command.
     * @param bool   $returnOutput Return full multiline output instead of first line result?
     *
     * @throws ManagerException
     *
     * @return array|string First line result or full multiline output.
     */
    protected function exec($command, $returnOutput = false)
    {
        $commandQuiet = str_replace(' 2>&1', '', $command).' 2>&1';

        $result = exec($commandQuiet, $output, $resultCode);

        if ($result === false) {
            throw new ManagerException(
                'PHP fails to execute external programs.'
            );
        } elseif ($resultCode !== 0) {
            throw new ManagerException(sprintf(
                "Command \"%s\" fails with:\n%s",
                $command,
                implode("\n", $output)
            ));
        }

        if ($returnOutput) {
            return $output;
        }

        return $result;
    }

    /**
     * @throws ManagerException
     *
     * @return bool
     */
    protected function hasCleanWorkingTree()
    {
        $changedFiles = $this->getAllChangedFiles();

        return empty($changedFiles);
    }

    /**
     * @throws ManagerException
     *
     * @return array
     */
    protected function getAllChangedFiles()
    {
        $filesWithStatus = $this->exec('git status --porcelain', true);

        return array_map(function ($fileWithStatus) {
            return substr($fileWithStatus, 3);
        }, $filesWithStatus);
    }

    /**
     * @throws ManagerException
     *
     * @return bool
     */
    protected function isBranchHeadTagged()
    {
        return $this->getTag() === $this->getTagLong();
    }

    /**
     * @throws ManagerException
     *
     * @return string
     */
    protected function getTag()
    {
        return $this->exec('git describe --tags --abbrev=0');
    }

    /**
     * @throws ManagerException
     *
     * @return string
     */
    protected function getTagLong()
    {
        return $this->exec('git describe --tags');
    }

    /**
     * @throws ManagerException
     *
     * @return string
     */
    protected function getBranch()
    {
        return $this->exec('git rev-parse --abbrev-ref HEAD');
    }
}
