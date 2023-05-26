<?php

namespace Vanderlee\SyllableBuild;

class SemanticVersioning
{
    const MAJOR_RELEASE = 0;
    const MINOR_RELEASE = 1;
    const PATCH_RELEASE = 2;

    public function getNextReleaseTag($tag, $releaseType)
    {
        $tagPrefix = substr($tag, 0, strcspn($tag, '0123456789'));
        $tagVersion = substr($tag, strlen($tagPrefix));
        $tagVersionParts = explode('.', $tagVersion);
        $releaseVersionParts = $tagVersionParts + [0, 0, 0];
        $releaseVersionParts = array_slice($releaseVersionParts, 0, $releaseType + 1);
        $releaseVersionParts[$releaseType]++;
        $releaseVersion = implode('.', $releaseVersionParts);

        return $tagPrefix.$releaseVersion;
    }
}
