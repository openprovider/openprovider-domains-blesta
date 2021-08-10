<?php

use phpDocumentor\Reflection\DocBlockFactory;

class ParamsCreator
{
    const NO_CLASS = 'no class';

    /**
     * @param array $args
     * @param mixed $client
     * @param string $method
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    public function createParameters(array $args, $client, string $method): array
    {
        $reflection = new \ReflectionMethod($client, $method);
        $namesOfArgs = array_column($reflection->getParameters(), 'name');

        return in_array('body', $namesOfArgs) ?
            $this->createParametersPostPut($args, $client, $method) :
            $this->createParametersGetDelete($args, $client, $method);
    }

    /**
     * @param array $args
     * @return array with formatted domain name by idn converter
     */
    public function modifyArgsIfDomainIdn(array $args): array
    {
        if (empty($args)) {
            return $args;
        }

        if (isset($args['domain']['name']) && isset($args['domain']['extension'])) {
            $args['domain']['name'] = $this->idnEncode($args['domain']['name']);
            $args['domain']['extension'] = $this->idnEncode($args['domain']['extension']);

            return $args;
        }

        if (isset($args['name_pattern'])) {
            $args['name_pattern'] = $this->idnEncode($args['name_pattern']);

            return $args;
        }

        if (isset($args['name']) && !is_array($args['name'])) {
            $args['name'] = $this->idnEncode($args['name']);

            return $args;
        }

        if (isset($args['full_name'])) {
            $args['full_name'] = $this->idnEncode($args['full_name']);

            return $args;
        }

        if (isset($args['domains']) && is_array($args['domains'])) {
            foreach ($args['domains'] as $index => $domain) {
                $args['domains'][$index]['name'] = $this->idnEncode($domain['name']);
                $args['domains'][$index]['extension'] = $this->idnEncode($domain['extension']);
            }

            return $args;
        }

        return $args;
    }

    /**
     * @param array $args
     * @param mixed $client
     * @param string $method
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    private function createParametersGetDelete(array $args, $client, string $method): array
    {
        $haveAnyOrderBy = !empty($args['orderBy']);
        $reflectionMethod = new \ReflectionMethod($client, $method);
        $parameters = [];
        /** @var \ReflectionParameter $parameter */
        foreach ($reflectionMethod->getParameters() as $parameter) {
            $parameterValue = $args[$parameter->name] ?? $parameter->getDefaultValue();
            $isOrderByParam = preg_match('/^order_by_.+/', $parameter->name);

            // if we have not empty orderBy in source args we should skip default values for all params order_by_*
            if ($haveAnyOrderBy && $isOrderByParam && !isset($args[$parameter->name])) {
                $parameterValue = null;
            }
            $parametersTypes = $this->getMethodParamsTypes($reflectionMethod);

            if (
                array_key_exists($parameter->name, $args) &&
                array_key_exists($parameter->name, $parametersTypes) &&
                $args[$parameter->name] !== null
            ) {
                settype($parameterValue, $parametersTypes[$parameter->name]);
            }
            $parameters[] = $parameterValue;
        }

        return $parameters;
    }

    /**
     * @param array $args
     * @param mixed $client
     * @param string $method
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    private function createParametersPostPut(array $args, object $client, string $method): array
    {
        $classNameOfBody = $this->parseClassNameOfBody($client, $method);

        if ($classNameOfBody === self::NO_CLASS) {
            return array((object)[]);
        }

        $processedArgs = [];
        foreach ($args as $key => $value) {
            if (is_array($value) && empty($value)) {
                $processedArgs[$key] = null;
                continue;
            }

            $processedArgs[$key] = $value;
        }

        $body = new $classNameOfBody($processedArgs);

        return $this->argsCollect($client, $method, $body);
    }

    /**
     * @param object $client
     * @param string $method
     *
     * @return string
     *
     * @throws \ReflectionException
     * @throws \Exception
     */
    private function parseClassNameOfBody(object $client, string $method): string
    {
        try {
            $reflector = new \ReflectionClass($client);
            $doc = $reflector->getMethod($method)->getDocComment();

            // case when in body object with complex model
            // example: '* @param  \Openprovider\AuthContracts\Client\Rest\Model\AuthLoginRequest $body (required)'
            $isStructureInBody = preg_match_all('/@param.*Rest.*/', $doc, $matches);

            // case when in body object without model
            // example: '* @param  object $body body (required)'
            $isObjectInBody = preg_match_all('/@param.*object.*body.*/', $doc, $matchesOfEmptyBody);

            if (!$isStructureInBody && $isObjectInBody) {
                return self::NO_CLASS;
            }
        } catch (\ReflectionException $e) {
            throw $e;
        }

        if (empty($matches[0])) {
            throw new \Exception('Cannot parse class name. Regexp not matched!');
        }
        $str = array_values($matches[0])[0];

        return explode(' ', $str)[2];
    }

    /**
     * @param object $class
     * @param string $method
     * @param object $body
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    private function argsCollect(object $class, string $method, object $body): array
    {
        $reflection = new \ReflectionMethod($class, $method);
        $methodArgsNames = array_column($reflection->getParameters(), 'name');
        $args = [];

        foreach ($methodArgsNames as $argName) {
            if ('body' === $argName) {
                $args[] = $body;
            } else {
                $getterName = $body::getters()[$argName];
                $args[] = $body->$getterName();
            }
        }

        return $args;
    }

    /**
     * @param \ReflectionMethod $method
     *
     * @return array
     */
    private function getMethodParamsTypes(\ReflectionMethod $method): array
    {
        $factory  = DocBlockFactory::createInstance();
        $docblock = $factory->create($method->getDocComment());
        $paramTags = [];
        foreach ($docblock->getTagsByName('param') as $tag) {
            /** @var $tag \phpDocumentor\Reflection\DocBlock\Tags\Param */
            $paramTags[$tag->getVariableName()] = (string)$tag->getType();
        }

        return $paramTags;
    }

    /**
     * @param string $domainName
     * @return string encoded domain name with punycode
     */
    private function idnEncode(string $domainName): string
    {
        $encodedDomainName = $domainName;
        if (!preg_match('//u', $domainName)) {
            $encodedDomainName = utf8_encode($domainName);
        }

        return idn_to_ascii($encodedDomainName);
    }
}
