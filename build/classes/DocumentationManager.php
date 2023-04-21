<?php

namespace Vanderlee\SyllableBuild;

use Vanderlee\Syllable\Syllable;

class DocumentationManager extends Manager
{
    /**
     * @var string
     */
    protected $readmeFile;

    /**
     * @var string
     */
    protected $apiClass;

    /**
     * @var bool
     */
    protected $withCommit;

    /**
     * @var Reflection
     */
    protected $reflection;

    /**
     * @var Git
     */
    protected $git;

    /**
     * @var Console
     */
    protected $console;

    protected $readmeChanged;

    public function __construct()
    {
        parent::__construct();

        $this->readmeFile = __DIR__.'/../../README.md';
        $this->apiClass = Syllable::class;
        $this->withCommit = false;

        $this->reflection = new Reflection();
        $this->git = new Git();
        $this->console = new Console();
    }

    /**
     * @param string $readmeFile
     */
    public function setReadmeFile($readmeFile)
    {
        $this->readmeFile = $readmeFile;
    }

    /**
     * @param string $apiClass
     */
    public function setApiClass($apiClass)
    {
        $this->apiClass = $apiClass;
    }

    /**
     * @param bool $withCommit
     */
    public function setWithCommit($withCommit)
    {
        $this->withCommit = $withCommit;
    }

    /**
     * @param Reflection $reflection
     */
    public function setReflection($reflection)
    {
        $this->reflection = $reflection;
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
     * @return bool
     */
    public function delegate()
    {
        try {
            $this->checkPrerequisites();
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
    protected function checkPrerequisites()
    {
        if ($this->withCommit && !$this->git->hasCleanWorkingTree()) {
            throw new Exception(
                'The project has uncommitted changes.'
            );
        }
    }

    /**
     * @throws Exception
     *
     * @return void
     */
    protected function updateReadme()
    {
        $apiMethods = $this->reflection->getPublicMethodsWithSignatureAndComment($this->apiClass);

        $apiDocumentation = '';
        foreach ($apiMethods as $method) {
            $apiDocumentation .= '#### '.$method['signature']."\n\n";
            $apiDocumentation .= $method['comment'] !== '' ? $method['comment']."\n\n" : '';
        }

        $readme = file_get_contents($this->readmeFile);
        $apiDocumentationStart = strpos($readme, '####', strpos($readme, "`Syllable` class reference\n--------------------------"));
        $apiDocumentationEnd = strpos($readme, "Development\n-----------", $apiDocumentationStart);
        $apiDocumentationLength = $apiDocumentationEnd - $apiDocumentationStart;
        $apiDocumentationOld = '';
        $readmeState = 0;

        if ($apiDocumentationStart > -1 && $apiDocumentationEnd > -1) {
            $apiDocumentationOld = substr($readme, $apiDocumentationStart, $apiDocumentationLength);
            $readme = substr_replace($readme, $apiDocumentation, $apiDocumentationStart, $apiDocumentationLength);
            $readmeState += 1;
        }

        file_put_contents($this->readmeFile, $readme);

        if ($readmeState < 1) {
            if (!($readmeState & 1)) {
                $errors[] = 'Missing headlines "`Syllable` class reference" and "Development" to locate API documentation.';
            }
            if (isset($errors)) {
                throw new Exception(sprintf(
                    "Could not update README.md. The format has probably changed:\n%s",
                    json_encode($errors, JSON_PRETTY_PRINT)
                ));
            }
        }

        $this->readmeChanged = $apiDocumentation !== $apiDocumentationOld;

        if ($this->readmeChanged) {
            $this->info('The API documentation in the README.md has CHANGED.');
        } else {
            $this->info('The API documentation in the README.md has not changed.');
        }
    }

    /**
     * @throws Exception
     *
     * @return void
     */
    protected function createCommit()
    {
        if ($this->withCommit === false || $this->readmeChanged === false) {
            return;
        }

        $this->console->exec('git add --all');
        $this->console->exec('git commit -m "Update API documentation"');
    }
}
