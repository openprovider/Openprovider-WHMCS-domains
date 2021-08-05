<?php

namespace OpenProvider\API;

use phpDocumentor\Reflection\DocBlockFactory;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use function GuzzleHttp\Psr7\_caseless_remove;

class ParamsCreator
{
    const NO_CLASS = "no class";

    /**
     * @var CamelCaseToSnakeCaseNameConverter
     */
    private $camelCaseToSnakeCaseNameConverter;


    public function __construct()
    {
        $this->camelCaseToSnakeCaseNameConverter = new CamelCaseToSnakeCaseNameConverter();
    }

    /**
     * @param array $args
     * @param mixed $client
     * @param string $method
     * @return array
     * @throws \ReflectionException
     */
    public function createParameters(array $args, $client, string $method)
    {
        $reflection = new \ReflectionMethod($client, $method);
        $namesOfArgs = array_column($reflection->getParameters(), 'name');

        $requestParameters = $this->convertRequestKeysToSnakeCase($args);

        return in_array('body', $namesOfArgs) ?
            $this->createParametersPostPut($requestParameters, $client, $method) :
            $this->createParametersGetDelete($requestParameters, $client, $method);
    }

    /**
     * @param array $args
     * @param mixed $client
     * @param string $method
     * @return array
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
     * @return array
     * @throws \ReflectionException
     */
    private function createParametersPostPut(array $args, object $client, string $method)
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
     * @param array $data
     * @return array
     */
    private function convertRequestKeysToSnakeCase(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $result[$this->camelCaseToSnakeCaseNameConverter->normalize($key)] = is_array($value) ?
                $this->convertRequestKeysToSnakeCase($value) :
                $value;
        }

        return $result;
    }

    /**
     * @param object $client
     * @param string $method
     * @return string
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
     * @return array
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
     * @return array
     */
    private function getMethodParamsTypes(\ReflectionMethod $method)
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
}
