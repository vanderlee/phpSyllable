<?php

namespace Vanderlee\SyllableBuild;

class DownloadManager
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
     * @var int
     */
    protected $logLevel;

    /**
     * @var array
     */
    protected $configuration;

    public function __construct()
    {
        $this->configurationFile = 'to-be-set';
        $this->maxRedirects = 1;
        $this->logLevel = LOG_INFO;
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
     * @param int $logLevel
     */
    public function setLogLevel($logLevel)
    {
        $this->logLevel = $logLevel;
    }

    /**
     * @return bool
     */
    public function download()
    {
        try {
            $configuration = $this->getConfiguration();
        } catch (DownloadManagerException $exception) {
            $this->error('Reading configuration has failed with:');
            $this->error($exception->getMessage());
            $this->error('Aborting.');

            return false;
        }

        $files = $configuration['files'];

        $numTotal = count($files);
        $numChanged = 0;
        $numUnchanged = 0;
        $numFailed = 0;

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
                $localFileContent = $this->readLocalFile($filePath);
                if ($remoteFileContent != $localFileContent) {
                    $this->writeLocalFile($filePath, $remoteFileContent);
                    $this->info(sprintf('File %s has CHANGED.', $fileName));
                    $numChanged++;
                } else {
                    $this->info(sprintf('File %s has not changed.', $fileName));
                    $numUnchanged++;
                }
            } catch (DownloadManagerException $exception) {
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

        return $numFailed === 0;
    }

    /**
     * @throws DownloadManagerException
     *
     * @return array{'files': <int, array{'_comment': string, 'fromUrl': string, 'toPath': string, 'disabled': boolean}>}
     */
    protected function getConfiguration()
    {
        if (empty($this->configuration)) {
            $this->readConfiguration();
        }

        return $this->configuration;
    }

    /**
     * @throws DownloadManagerException
     *
     * @return void
     */
    protected function readConfiguration()
    {
        $configurationContent = $this->readLocalFile($this->configurationFile);
        $configurationDir = dirname($this->configurationFile);
        $configuration = json_decode($configurationContent, true);
        $configuration['files'] = array_filter($configuration['files'], function ($file) {
            return !(isset($file['disabled']) && $file['disabled']);
        });
        foreach ($configuration['files'] as &$file) {
            $file['toPath'] = $this->getAbsoluteFilePath($configurationDir, $file['toPath']);
        }
        $this->configuration = $configuration;
    }

    /**
     * @param $filePath
     *
     * @throws DownloadManagerException
     *
     * @return string
     */
    protected function readLocalFile($filePath)
    {
        $fileContent = @file_get_contents($filePath);

        if ($fileContent === false) {
            $error = error_get_last();

            throw new DownloadManagerException(sprintf(
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
     * @throws DownloadManagerException
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
        $absoluteFilePath = realpath($absoluteFilePath);

        if ($absoluteFilePath === false) {
            throw new DownloadManagerException(sprintf(
                "Accessing %s from path %s failed.",
                $filePath,
                $rootPath
            ));
        }

        return $absoluteFilePath;
    }

    /**
     * @param $filePath
     * @param $fileContent
     *
     * @throws DownloadManagerException
     *
     * @return void
     */
    protected function writeLocalFile($filePath, $fileContent)
    {
        $result = @file_put_contents($filePath, $fileContent);

        if ($result === false) {
            $error = error_get_last();

            throw new DownloadManagerException(sprintf(
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
     * @throws DownloadManagerException
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
            throw new DownloadManagerException(sprintf(
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
            throw new DownloadManagerException(sprintf(
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
            echo "${text}\n";
        }
    }
}
