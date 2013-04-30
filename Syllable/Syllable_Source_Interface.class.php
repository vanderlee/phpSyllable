<?php

    /**
     * Defines the interface for Language strategies.
     * Create your own language strategy to load the TeX files from a different
     * source. i.e. filenaming system, database or remote server.
     */
    interface Syllable_Source_Interface extends Iterator {}