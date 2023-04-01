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
            $this->getReleaseTag();
            $this->updateReadme();
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

    protected function getReleaseTag()
    {
        $this->releaseTag = $this->semanticVersioning->getNextReleaseTag($this->tag, $this->releaseType);
        $this->info(sprintf('Create release %s.', $this->releaseTag));
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
        $readmeState = 0;
        foreach ($readme as $line) {
            if (strpos($line, "Version $this->tag") === 0) {
                $readmeContent .= str_replace($this->tag, $this->releaseTag, $line);
                $readmeState += 1;
            } elseif (strpos($line, 'Copyright') === 0) {
                $readmeContent .= preg_replace('#2011(\s*)-(\s*)[0-9]+#', '2011${1}-${2}'.date('Y'), $line, 1, $count);
                if ($count === 1) {
                    $readmeState += 2;
                }
            } elseif (strpos($line, $this->tag) === 0) {
                $readmeContent .= str_replace($this->tag, "$changelog\n$this->tag", $line);
                $readmeState += 4;
            } else {
                $readmeContent .= $line;
            }
        }
        file_put_contents($this->readmeFile, $readmeContent);

        if ($readmeState < 7) {
            if (!($readmeState & 1)) {
                $errors[] = sprintf("Missing note 'Version %s' of the last release below the title.", $this->tag);
            }
            if (!($readmeState & 2)) {
                $errors[] = "Missing copyright claim 'Copyright &copy; 2011-{year} Martijn van der Lee.'";
            }
            if (!($readmeState & 4)) {
                $errors[] = sprintf("Missing changelog entry '%s: ..' from the last release.", $this->tag);
            }
            if (isset($errors)) {
                throw new Exception(sprintf(
                    "Could not update README.md. The format has probably changed:\n%s",
                    json_encode($errors, JSON_PRETTY_PRINT)
                ));
            }
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
