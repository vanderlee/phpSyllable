<?php

namespace Vanderlee\SyllableTest\Build;

class ReflectionFixture
{
    /**
     * @var array
     */
    protected $methods;

    /**
     * @var array
     */
    protected static $parameters;

    /**
     * The public setter method.
     *
     * @param array $methods
     *
     * @return void
     *
     * @see https://github.com/vanderlee/phpSyllable/blob/master/tests/build/ReflectionFixture.php
     */
    public function setMethods($methods)
    {
        $this->methods = $methods;
    }

    /**
     * The public getter method.
     *
     * @return array
     */
    public function getMethods()
    {
        $this->loadMethods();

        return $this->methods;
    }

    /**
     * The protected method.
     *
     * @return void
     */
    protected function loadMethods()
    {
        $this->fetchMethods();
    }

    /**
     * The private method.
     *
     * @return void
     */
    private function fetchMethods()
    {
        $this->methods = [];
    }

    /**
     * The public static method.
     *
     * @return array
     */
    public static function getParameters()
    {
        self::loadParameters();

        return self::$parameters;
    }

    /**
     * The protected static method.
     *
     * @return void
     */
    protected static function loadParameters()
    {
        self::fetchParameters();
    }

    /**
     * The private static method.
     *
     * @return void
     */
    private static function fetchParameters()
    {
        self::$parameters = [];
    }
}
