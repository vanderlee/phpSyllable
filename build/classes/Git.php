<?php

namespace Vanderlee\SyllableBuild;

class Git
{
    /**
     * @var Console
     */
    protected $console;

    public function __construct()
    {
        $this->console = new Console();
    }

    /**
     * @param Console $console
     */
    public function setConsole($console)
    {
        $this->console = $console;
    }

    /**
     * @throws Exception
     *
     * @return bool
     */
    public function hasCleanWorkingTree()
    {
        $changedFiles = $this->getAllChangedFiles();

        return empty($changedFiles);
    }

    /**
     * @throws Exception
     *
     * @return array
     */
    public function getAllChangedFiles()
    {
        $filesWithStatus = $this->console->exec('git status --porcelain', true);

        return array_map(function ($fileWithStatus) {
            return substr($fileWithStatus, 3);
        }, $filesWithStatus);
    }

    /**
     * @throws Exception
     *
     * @return bool
     */
    public function isBranchReleased()
    {
        return $this->getTag() === $this->getTagLong();
    }

    /**
     * @throws Exception
     *
     * @return array
     */
    public function getSubjectsSinceLastRelease()
    {
        return $this->console->exec(
            sprintf('git log --no-merges --pretty="format:%%s" %s..HEAD', $this->getTag()),
            true
        );
    }

    /**
     * @throws Exception
     *
     * @return string
     */
    public function getTag()
    {
        return $this->console->exec('git describe --tags --abbrev=0');
    }

    /**
     * @throws Exception
     *
     * @return string
     */
    public function getTagLong()
    {
        return $this->console->exec('git describe --tags');
    }

    /**
     * @throws Exception
     *
     * @return string
     */
    public function getBranch()
    {
        return $this->console->exec('git rev-parse --abbrev-ref HEAD');
    }
}
