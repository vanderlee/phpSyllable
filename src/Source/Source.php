<?php
declare(strict_types=1);

namespace Vanderlee\Syllable\Source;

/**
 * Defines the interface for Language strategies.
 * Create your own language strategy to load the TeX files from a different
 * source. i.e. filenaming system, database or remote server.
 */
interface Source
{

    /**
     * Get the minimum lengths of syllables at the start and end of a word before a hyphenation may occur.
     *
     * @return int[]|null
     */
    public function getMinHyphens(): ?array;

    /**
     * Get all patterns loaded
     *
     * @return array
     */
    public function getPatterns(): array;

    /**
     * Get the length of the longest pattern loaded
     *
     * @return int
     */
    public function getMaxPattern(): int;

    /**
     * Get all exception hyphenations loaded
     *
     * @return array
     */
    public function getHyphentations(): array;
}
