<?php

use Vanderlee\SyllableBuild\LanguageFileService;

require_once __DIR__.'/../vendor/autoload.php';

$languageUrl = getenv('LANGUAGE_URL');
$maxRedirects = getenv('MAX_REDIRECTS');
$languageDir = getenv('LANGUAGE_DIR');
$logLevel = getenv('LOG_LEVEL');

$languageFileService = new LanguageFileService();
if ($languageUrl !== false) {
    $languageFileService->setLanguageUrl($languageUrl);
}
if ($maxRedirects !== false) {
    $languageFileService->setMaxRedirects((int) $maxRedirects);
}
if ($languageDir !== false) {
    $languageFileService->setLanguageDir($languageDir);
}
if ($logLevel !== false) {
    $languageFileService->setLogLevel((int) $logLevel);
}
$result = $languageFileService->updateLanguageFiles();

if ($result === false) {
    exit(1);
}
exit(0);
