<?php

namespace Vanderlee\SyllableBuild;

class LanguageFileService
{
    /**
     * @var string
     */
    protected $languageUrl;

    /**
     * @var int
     */
    protected $maxRedirects;

    /**
     * @var string
     */
    protected $languageDir;

    public function __construct()
    {
        $this->languageUrl = 'http://mirror.ctan.org/language/hyph-utf8/tex/generic/hyph-utf8/patterns/tex';
        $this->maxRedirects = 20;
        $this->languageDir = realpath(__DIR__.'/../../languages');
    }

    /**
     * @param string $languageUrl
     */
    public function setLanguageUrl($languageUrl)
    {
        $this->languageUrl = $languageUrl;
    }

    /**
     * @param int $maxRedirects
     */
    public function setMaxRedirects($maxRedirects)
    {
        $this->maxRedirects = $maxRedirects;
    }

    /**
     * @param string $languageDir
     */
    public function setLanguageDir($languageDir)
    {
        $this->languageDir = $languageDir;
    }

    public function updateLanguageFiles()
    {
        $languageFiles = glob("{$this->languageDir}/*.tex");

        $numTotal = count($languageFiles);
        $numChanged = 0;
        $numUnchanged = 0;
        $numFailed = 0;

        $this->printToConsole(sprintf(
            'Updating %s language files on %s.',
            $numTotal,
            date('Y-m-d H:i:s T')
        ));

        foreach ($languageFiles as $filePath) {
            $fileName = basename($filePath);
            $fileUrl = "{$this->languageUrl}/{$fileName}";

            try {
                $oldFileContent = file_get_contents($filePath);
                $newFileContent = $this->fetchFile($fileUrl);
                if ($newFileContent != $oldFileContent) {
                    file_put_contents($filePath, $newFileContent);
                    $this->printToConsole(sprintf('File %s has CHANGED.', $fileName));
                    $numChanged++;
                } else {
                    $this->printToConsole(sprintf('File %s has not changed.', $fileName));
                    $numUnchanged++;
                }
            } catch (LanguageFileServiceException $exception) {
                $this->printToConsole($exception->getMessage());
                $numFailed++;
            }
        }

        $numProcessed = $numChanged + $numUnchanged + $numFailed;

        $this->printToConsole(sprintf(
            'Result: %s/%s files processed, %s changed, %s unchanged and %s failed.',
            $numProcessed,
            $numTotal,
            $numChanged,
            $numUnchanged,
            $numFailed
        ));
    }

    /**
     * @param $fileUrl
     *
     * @throws LanguageFileServiceException
     *
     * @return bool|string
     */
    protected function fetchFile($fileUrl)
    {
        $curl = curl_init($fileUrl);

        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, $this->maxRedirects);

        $fileContent = curl_exec($curl);

        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($status < 200 || $status >= 300) {
            throw new LanguageFileServiceException(sprintf(
                "Error: Call to URL %s failed with\n%s",
                $fileUrl,
                json_encode([
                    'status'            => $status,
                    'response'          => substr($fileContent, 0, 500).' ..',
                    'cURL error'        => curl_error($curl),
                    'cURL error number' => curl_errno($curl),
                ], JSON_PRETTY_PRINT)
            ));
        }

        curl_close($curl);

        return $fileContent;
    }

    protected function printToConsole($text)
    {
        echo "${text}\n";
    }
}