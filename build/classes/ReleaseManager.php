<?php

namespace Vanderlee\SyllableBuild;

class ReleaseManager extends Manager
{
    const MAJOR_RELEASE = 0;
    const MINOR_RELEASE = 1;
    const PATCH_RELEASE = 2;

    /**
     * @var int
     */
    protected $releaseType;

    /**
     * @var bool
     */
    protected $withCommit;

    protected $branch;

    protected $tag;

    protected $releaseTag;

    public function __construct()
    {
        parent::__construct();

        $this->releaseType = self::PATCH_RELEASE;
        $this->withCommit = false;
    }

    /**
     * @param int $releaseType
     */
    public function setReleaseType($releaseType)
    {
        $this->releaseType = $releaseType;
    }

    /**
     * @param bool $withCommit
     */
    public function setWithCommit($withCommit)
    {
        $this->withCommit = $withCommit;
    }

    /**
     * @return bool
     */
    public function delegate()
    {
        try {
            $this->getContext();
            $this->checkPrerequisites();
            $this->info(sprintf('Create release %s.', $this->releaseTag));
            $this->updateReadme();
            $this->checkPostConditions();
            $this->createCommit();
        } catch (ManagerException $exception) {
            $this->error($exception->getMessage());
            $this->error('Aborting.');

            return false;
        }

        return true;
    }

    /**
     * @throws ManagerException
     *
     * @return void
     */
    protected function getContext()
    {
        $this->branch = $this->getBranch();
        $this->tag = $this->getTag();
        $this->releaseTag = $this->createReleaseTag();
    }

    /**
     * @return string
     */
    protected function createReleaseTag()
    {
        $tagPrefix = substr($this->tag, 0, strcspn($this->tag, '0123456789'));
        $tagVersion = substr($this->tag, strlen($tagPrefix));
        $tagVersionParts = explode('.', $tagVersion);
        $releaseVersionParts = array_slice($tagVersionParts, 0, $this->releaseType + 1);
        $releaseVersionParts[$this->releaseType]++;
        $releaseVersion = implode('.', $releaseVersionParts);

        return $tagPrefix.$releaseVersion;
    }

    /**
     * @throws ManagerException
     *
     * @return void
     */
    protected function checkPrerequisites()
    {
        if ($this->withCommit && !$this->hasCleanWorkingTree()) {
            throw new ManagerException(
                'The project has uncommitted changes.'
            );
        }

        if ($this->isBranchHeadTagged()) {
            throw new ManagerException(sprintf(
                'Current branch (%s) is already tagged (%s).',
                $this->branch,
                $this->tag
            ));
        }
    }

    /**
     * @throws ManagerException
     *
     * @return void
     */
    protected function updateReadme()
    {
        $subjects = $this->exec(
            sprintf('git log --no-merges --pretty="format:%%s" %s..HEAD', $this->tag),
            true
        );

        $changelog = "$this->releaseTag\n";
        foreach ($subjects as $subject) {
            $changelog .= "-   $subject\n";
        }

        $readmePath = __DIR__.'/../../README.md';
        $readme = file($readmePath);
        $readmeContent = '';
        foreach ($readme as $line) {
            if (strpos($line, "Version $this->tag") === 0) {
                $readmeContent .= str_replace($this->tag, $this->releaseTag, $line);
            } elseif (strpos($line, $this->tag) === 0) {
                $readmeContent .= str_replace($this->tag, "$changelog\n$this->tag", $line);
            } else {
                $readmeContent .= $line;
            }
        }
        file_put_contents($readmePath, $readmeContent);
    }

    /**
     * @throws ManagerException
     *
     * @return void
     */
    protected function checkPostConditions()
    {
        if ($this->hasCleanWorkingTree()) {
            throw new ManagerException(
                'Could not update README.md. The format has probably changed.'
            );
        }
    }

    /**
     * @throws ManagerException
     *
     * @return void
     */
    protected function createCommit()
    {
        if ($this->withCommit === false) {
            return;
        }

        $this->exec('git add .');
        $this->exec(sprintf('git commit -m "Release %s"', $this->releaseTag));
        $this->exec(sprintf('git tag %s', $this->releaseTag));
    }
}