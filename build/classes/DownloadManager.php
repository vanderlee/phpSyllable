<?php

namespace Vanderlee\SyllableBuild;

class DownloadManager extends Manager
{
    /**
     * @var string
     */
    protected $configurationFile;

    /**
     * @var int
     */
    protected $maxRedirects;

    /**
     * @var bool
     */
    protected $withCommit;

    /**
     * @var array{'files': <int, array{'_comment': string, 'fromUrl': string, 'toPath': string, 'disabled': boolean}>}
     */
    protected $configuration;

    protected $numProcessed;

    protected $numTotal;

    protected $numChanged;

    protected $numUnchanged;

    protected $numFailed;

    protected $filesChanged;

    public function __construct()
    {
        parent::__construct();

        $this->configurationFile = 'to-be-set';
        $this->maxRedirects = 1;
        $this->withCommit = false;
    }

    /**
     * @param string $configurationFile
     */
    public function setConfigurationFile($configurationFile)
    {
        $this->configurationFile = $configurationFile;
    }

    /**
     * @param int $maxRedirects
     */
    public function setMaxRedirects($maxRedirects)
    {
        $this->maxRedirects = $maxRedirects;
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
            $this->checkPrerequisites();
            $this->readConfiguration();
            $this->download();
            $this->createCommitIfFilesChanged();
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
    protected function checkPrerequisites()
    {
        if ($this->withCommit && !$this->hasCleanWorkingTree()) {
            throw new ManagerException(
                'The project has uncommitted changes.'
            );
        }
    }

    /**
     * @throws ManagerException
     *
     * @return void
     */
    protected function readConfiguration()
    {
        try {
            $configurationContent = $this->readLocalFile($this->configurationFile, true);
            $configurationDir = dirname($this->configurationFile);
            $configuration = json_decode($configurationContent, true);
            $configuration['files'] = array_filter($configuration['files'], function ($file) {
                return !(isset($file['disabled']) && $file['disabled']);
            });
            foreach ($configuration['files'] as &$file) {
                $file['toPath'] = $this->getAbsoluteFilePath($configurationDir, $file['toPath']);
            }
            $this->configuration = $configuration;
        } catch (ManagerException $exception) {
            throw new ManagerException(sprintf(
                "Reading configuration has failed with:\n%s",
                $exception->getMessage()
            ));
        }
    }

    protected function download()
    {
        $files = $this->configuration['files'];

        $numTotal = count($files);
        $numChanged = 0;
        $numUnchanged = 0;
        $numFailed = 0;
        $filesChanged = [];

        $this->info(sprintf(
            'Updating %s files on %s.',
            $numTotal,
            date('Y-m-d H:i:s T')
        ));

        foreach ($files as $file) {
            $fileUrl = $file['fromUrl'];
            $filePath = $file['toPath'];
            $fileName = basename($filePath);

            try {
                $remoteFileContent = $this->readRemoteFile($fileUrl);
                $localFileContent = $this->readLocalFile($filePath, false);
                if ($remoteFileContent != $localFileContent) {
                    $this->writeLocalFile($filePath, $remoteFileContent);
                    $this->info(sprintf('File %s has CHANGED.', $fileName));
                    $numChanged++;
                    $filesChanged[] = $filePath;
                } else {
                    $this->info(sprintf('File %s has not changed.', $fileName));
                    $numUnchanged++;
                }
            } catch (ManagerException $exception) {
                $this->warn(sprintf('Update of file %s has failed with:', $fileName));
                $this->warn($exception->getMessage());
                $numFailed++;
            }
        }

        $numProcessed = $numChanged + $numUnchanged + $numFailed;

        $this->info(sprintf(
            'Result: %s/%s files processed, %s changed, %s unchanged and %s failed.',
            $numProcessed,
            $numTotal,
            $numChanged,
            $numUnchanged,
            $numFailed
        ));

        $this->numProcessed = $numProcessed;
        $this->numTotal = $numTotal;
        $this->numChanged = $numChanged;
        $this->numUnchanged = $numUnchanged;
        $this->numFailed = $numFailed;
        $this->filesChanged = $filesChanged;
    }

    /**
     * @param $filePath
     * @param $throwException
     *
     * @throws ManagerException
     *
     * @return false|string
     */
    protected function readLocalFile($filePath, $throwException)
    {
        $fileContent = @file_get_contents($filePath);

        if ($fileContent === false && $throwException) {
            $error = error_get_last();

            throw new ManagerException(sprintf(
                "Reading from path %s failed with\n%s",
                $filePath,
                json_encode([
                    'message'   => $error['message'],
                ], JSON_PRETTY_PRINT)
            ));
        }

        return $fileContent;
    }

    /**
     * @param $rootPath
     * @param $filePath
     *
     * @return string
     */
    protected function getAbsoluteFilePath($rootPath, $filePath)
    {
        if (strpos($filePath, $rootPath) === 0) {
            $absoluteFilePath = $filePath;
        } elseif (substr($filePath, 0, 1) === '/') {
            $absoluteFilePath = $rootPath.$filePath;
        } else {
            $absoluteFilePath = $rootPath.'/'.$filePath;
        }

        return $absoluteFilePath;
    }

    /**
     * @param $filePath
     * @param $fileContent
     *
     * @throws ManagerException
     *
     * @return void
     */
    protected function writeLocalFile($filePath, $fileContent)
    {
        $result = @file_put_contents($filePath, $fileContent);

        if ($result === false) {
            $error = error_get_last();

            throw new ManagerException(sprintf(
                "Writing to path %s failed with\n%s",
                $filePath,
                json_encode([
                    'message'   => $error['message'],
                ], JSON_PRETTY_PRINT)
            ));
        }
    }

    /**
     * @param $fileUrl
     *
     * @throws ManagerException
     *
     * @return string
     */
    protected function readRemoteFile($fileUrl)
    {
        $curl = curl_init($fileUrl);

        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, $this->maxRedirects);

        $fileContent = curl_exec($curl);

        if ($fileContent === false) {
            throw new ManagerException(sprintf(
                "Call to URL %s failed with\n%s",
                $fileUrl,
                json_encode([
                    'cURL error'        => curl_error($curl),
                    'cURL error number' => curl_errno($curl),
                ], JSON_PRETTY_PRINT)
            ));
        }

        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($fileContent === '' || $status < 200 || $status >= 300) {
            throw new ManagerException(sprintf(
                "Call to URL %s failed with\n%s",
                $fileUrl,
                json_encode([
                    'status'            => $status,
                    'response'          => substr($fileContent, 0, 500).' ..',
                ], JSON_PRETTY_PRINT)
            ));
        }

        curl_close($curl);

        return $fileContent;
    }

    protected function createCommitIfFilesChanged()
    {
        if ($this->withCommit === false || $this->numChanged === 0) {
            return;
        }

        $message = sprintf('Automatic update of %s files', $this->numChanged);
        if ($this->numChanged === 1) {
            $message = sprintf('Automatic update of %s', basename($this->filesChanged[0]));
        }

        $this->exec('git add .');
        $this->exec(sprintf('git commit -m "%s"', $message));
    }
}
