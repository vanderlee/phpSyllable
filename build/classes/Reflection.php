<?php

namespace Vanderlee\SyllableBuild;

/**
 * Extract information from native Reflection class and PHPDoc annotations.
 */
class Reflection
{
    /**
     * @var array
     */
    protected $methods;

    /**
     * @throws Exception
     *
     * @param string $class
     *
     * @return array
     */
    public function getPublicMethodsWithSignatureAndComment($class)
    {
        $this->parse($class);

        $methods = [];

        foreach ($this->methods as $method) {
            $signature = 'public ';
            $signature .= $method['static'] ? 'static ' : '';
            $signature .= $method['name'];
            $signature .= '(';
            for ($i = 0, $count = count($method['parameters']); $i < $count; $i++) {
                $parameter = $method['parameters'][$i];
                $signature .= !empty($parameter['type']) ? $parameter['type'].' ' : '';
                $signature .= '$';
                $signature .= $parameter['name'];
                $signature .= array_key_exists('defaultValue', $parameter) ?
                    ($parameter['defaultValueIsConstant'] ?
                        '='.$parameter['defaultValue'] :
                        (is_string($parameter['defaultValue']) ?
                            "='".$parameter['defaultValue']."'" :
                            (is_null($parameter['defaultValue']) ?
                                '=null' :
                                '='.$parameter['defaultValue']))) : '';
                $signature .= $i < $count - 1 ? ', ' : '';
            }
            $signature .= ")";
            $signature .= !empty($method['returnType']) ? ': '.$method['returnType'] : '';
            $comment = !empty($method['commentLines']) ? implode("\n", $method['commentLines']) : '';

            $methods[] = [
                'signature' => $signature,
                'comment' => $comment,
            ];
        }

        return $methods;
    }

    /**
     * @param string $class
     *
     * @throws Exception
     *
     * @return void
     */
    protected function parse($class)
    {
        $this->methods = $methods = [];

        try {
            $reflectionClass = new \ReflectionClass($class);
            $reflectionMethodFilter = \ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_STATIC;

            foreach ($reflectionClass->getMethods($reflectionMethodFilter) as $reflectionMethod) {
                if ($reflectionMethod->isStatic() && ($reflectionMethod->isProtected() || $reflectionMethod->isPrivate())) {
                    continue;
                }

                $parametersType = [];
                $returnType = '';
                $commentLines = [];

                if ($reflectionMethod->getDocComment() !== false) {
                    $docCommentLines = explode("\n", $reflectionMethod->getDocComment());
                    $docCommentLines = array_map(function($line){return ltrim($line, '/* ');}, $docCommentLines);
                    foreach ($docCommentLines as $line) {
                        if (strpos($line, '@param') === 0) {
                            $annotation = explode(' ', $line);
                            $parametersType[$annotation[2]] = $annotation[1];
                        } elseif (strpos($line, '@return') === 0) {
                            $annotation = explode(' ', $line);
                            $returnType = $annotation[1] !== 'void' ? $annotation[1] : '';
                        } elseif (strpos($line, '@see') === 0) {
                            $annotation = explode(' ', $line);
                            $commentLines[] = 'See '.$annotation[1].'.';
                        } elseif (!empty($line)) {
                            $commentLines[] = $line;
                        }
                    }
                }

                $parameters = [];

                if ($reflectionMethod->getNumberOfParameters() > 0) {
                    foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
                        $parameter = [];
                        $parameter['name'] = $reflectionParameter->getName();
                        $parameter['type'] = !empty($parametersType['$'.$reflectionParameter->getName()]) ?
                            $parametersType['$'.$reflectionParameter->getName()] :
                            '';
                        if ($reflectionParameter->isDefaultValueAvailable()) {
                            $parameter['defaultValue'] = $reflectionParameter->isDefaultValueConstant() ?
                                $reflectionParameter->getDefaultValueConstantName() :
                                $reflectionParameter->getDefaultValue();
                            $parameter['defaultValueIsConstant'] = $reflectionParameter->isDefaultValueConstant();
                        }
                        $parameters[] = $parameter;
                    }
                }

                $method = [];
                $method['static'] = $reflectionMethod->isStatic();
                $method['name'] = $reflectionMethod->getName();
                $method['parameters'] = $parameters;
                $method['returnType'] = $returnType;
                $method['commentLines'] = $commentLines;
                $methods[] = $method;
            }
        } catch (\ReflectionException $exception) {
            throw new Exception(sprintf(
                "Reflecting class %s has failed with:\n%s",
                $class,
                $exception->getMessage()
            ));
        }

        $this->methods = $methods;
    }
}
