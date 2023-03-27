<?php

namespace Vanderlee\SyllableBuild;

class ReleaseManager extends Manager
{
    /**
     * @var int
     */
    protected $releaseType;

    /**
     * @var bool
     */
    protected $withCommit;

    /**
     * @var string
     */
    protected $readmeFile;

    /**
     * @var Git
     */
    protected $git;

    /**
     * @var Console
     */
    protected $console;

    /**
     * @var SemanticVersioning
     */
    protected $semanticVersioning;

    protected $branch;

    protected $tag;

    protected $releaseTag;

    public function __construct()
    {
        parent::__construct();

        $this->releaseType = SemanticVersioning::PATCH_RELEASE;
        $this->withCommit = false;
        $this->readmeFile = __DIR__.'/../../README.md';

        $this->git = new Git();
        $this->console = new Console();
        $this->semanticVersioning = new SemanticVersioning();
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
     * @param string $readmeFile
     */
    public function setReadmeFile($readmeFile)
    {
        $this->readmeFile = $readmeFile;
    }

    /**
     * @param Git $git
     */
    public function setGit($git)
    {
        $this->git = $git;
    }

    /**
     * @param Console $console
     */
    public function setConsole($console)
    {
        $this->console = $console;
    }

    /**
     * @param SemanticVersioning $semanticVersioning
     */
    public function setSemanticVersioning($semanticVersioning)
    {
        $this->semanticVersioning = $semanticVersioning;
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
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
            $this->error('Aborting.');

            return false;
        }

        return true;
    }

    /**
     * @throws Exception
     *
     * @return void
     */
    protected function getContext()
    {
        $this->branch = $this->git->getBranch();
        $this->tag = $this->git->getTag();
        $this->releaseTag = $this->semanticVersioning->getNextReleaseTag($this->tag, $this->releaseType);
    }

    /**
     * @throws Exception
     *
     * @return void
     */
    protected function checkPrerequisites()
    {
        if ($this->withCommit && !$this->git->hasCleanWorkingTree()) {
            throw new Exception(
                'The project has uncommitted changes.'
            );
        }

        if ($this->git->isBranchReleased()) {
            throw new Exception(sprintf(
                'Current branch (%s) is already released (%s).',
                $this->branch,
                $this->tag
            ));
        }
    }

    /**
     * @throws Exception
     *
     * @return void
     */
    protected function updateReadme()
    {
        $subjects = $this->git->getSubjectsSinceLastRelease();

        $changelog = "$this->releaseTag\n";
        foreach ($subjects as $subject) {
            $changelog .= "-   $subject\n";
        }

        $readme = file($this->readmeFile);
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
        file_put_contents($this->readmeFile, $readmeContent);
    }

    /**
     * @throws Exception
     *
     * @return void
     */
    protected function checkPostConditions()
    {
        if ($this->git->hasCleanWorkingTree()) {
            throw new Exception(
                'Could not update README.md. The format has probably changed.'
            );
        }
    }

    /**
     * @throws Exception
     *
     * @return void
     */
    protected function createCommit()
    {
        if ($this->withCommit === false) {
            return;
        }

        $this->console->exec('git add .');
        $this->console->exec(sprintf('git commit -m "Release %s"', $this->releaseTag));
        $this->console->exec(sprintf('git tag %s', $this->releaseTag));
    }
}
