<?php


namespace Silktide\Syringe\Tests;


use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Silktide\Syringe\Exception\ConfigException;
use Silktide\Syringe\ParameterResolver;
use Silktide\Syringe\Token;

class ReferenceResolverTest extends TestCase
{
    const EXAMPLE_TEST = "example_result";

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var ParameterResolver
     */
    protected $referenceResolver;

    protected $tagMap = [];

    public function setUp() : void
    {
        $this->container = new Container();
        $this->referenceResolver = new ParameterResolver();
    }

    public function testRegularConstantResolve()
    {
        define("foo", "bar");
        
        $value = $this->referenceResolver->resolve(
            [],
            Token::CONSTANT_CHAR . "foo". Token::CONSTANT_CHAR,
            $this->tagMap
        );
        self::assertEquals("bar", $value);
    }

    public function testClassConstantResolve()
    {
        $value = $this->referenceResolver->resolve(
            [],
            Token::CONSTANT_CHAR . "Silktide\\Syringe\\Tests\\ReferenceResolverTest::EXAMPLE_TEST". Token::CONSTANT_CHAR,
            $this->tagMap
        );
        self::assertEquals("example_result", $value);
    }

    public function testConstantNonExistentResolve()
    {
        $this->expectException(ConfigException::class);
        $this->referenceResolver->resolve([],Token::CONSTANT_CHAR . "bar". Token::CONSTANT_CHAR, $this->tagMap);
    }

    public function testEnvironmentResolve()
    {
        $this->setEnvVar("banana", "salad");
        $value = $this->referenceResolver->resolve([], Token::ENV_CHAR . "banana" . Token::ENV_CHAR, $this->tagMap);
        self::assertEquals("salad", $value);
    }


    public function testFalseEnvironmentResolve()
    {
        $this->setEnvVar("chips", false);
        $value = $this->referenceResolver->resolve([], Token::ENV_CHAR . "chips" . Token::ENV_CHAR, $this->tagMap);
        self::assertEquals(false, $value);
        // Herein lies the rub, environment variables do not understant the concept of true or false, so it'll change it
        // to in this case, ''
        self::assertSame('', $value);
    }


    public function testFailedEnvironmentResolve()
    {
        $this->expectException(ConfigException::class);
        $this->referenceResolver->resolve([], Token::ENV_CHAR . "chicken" . Token::ENV_CHAR, $this->tagMap);
    }

    public function testParameterResolve()
    {
        $parameters = [];
        $parameters["parameter_key"] = "parameter_value";
        $value = $this->referenceResolver->resolve($parameters, Token::PARAMETER_CHAR . "parameter_key" . Token::PARAMETER_CHAR, $this->tagMap);
        self::assertEquals("parameter_value", $value);
    }

    public function testFailedParameterResolve()
    {
        $this->expectException(ConfigException::class);
        $this->referenceResolver->resolve([], Token::PARAMETER_CHAR . "parameter_key" . Token::PARAMETER_CHAR, $this->tagMap);
    }

    public function testRecursiveParameterResolve()
    {
        $parameters = [];
        $parameters["parameter_key"] = Token::PARAMETER_CHAR . "parameter_key_2" . Token::PARAMETER_CHAR;
        $parameters["parameter_key_2"] = "parameter_value";
        $value = $this->referenceResolver->resolve($parameters, Token::PARAMETER_CHAR . "parameter_key" . Token::PARAMETER_CHAR, $this->tagMap);
        self::assertEquals("parameter_value", $value);
    }

    public function testParameterArrayResolve()
    {
        $parameters = [];
        $parameters["parameter_key"] = "parameter_value";
        $parameters["parameter_key_2"] = "parameter_value_2";

        $array = $this->referenceResolver->resolveArray($parameters, [
            "foo" => Token::PARAMETER_CHAR . "parameter_key" . Token::PARAMETER_CHAR,
            "bar" => Token::PARAMETER_CHAR . "parameter_key_2" . Token::PARAMETER_CHAR
        ], $this->tagMap);
        self::assertSame(["foo" => "parameter_value", "bar" => "parameter_value_2"], $array);
    }

    public function testRecursiveParameterArrayResolve()
    {
        $parameters = [];
        $parameters["parameter_key"] = "parameter_value";
        $parameters["parameter_key_2"] = "parameter_value_2";

        $array = $this->referenceResolver->resolveArray($parameters, [
            "foo" => [
                Token::PARAMETER_CHAR . "parameter_key" . Token::PARAMETER_CHAR,
                Token::PARAMETER_CHAR . "parameter_key_2" . Token::PARAMETER_CHAR
            ],
        ], $this->tagMap);

        self::assertSame([
            "foo" => [
                "parameter_value",
                "parameter_value_2"
            ]
        ], $array);
    }

    public function testResolveNull()
    {
        $parameters = ["parameter_key" => null];
        $array = $this->referenceResolver->resolveArray($parameters, [
            Token::PARAMETER_CHAR . "parameter_key" . Token::PARAMETER_CHAR
        ], $this->tagMap);

        self::assertSame([
            null,
        ], $array);
    }

    public function testArrayConcatenationFailure()
    {
        $this->expectException(ConfigException::class);
        $parameters = ["parameter_key" =>  ["foo", "bar"]];
        $this->referenceResolver->resolve($parameters, "foo%parameter_key%", $this->tagMap);
    }

    public function testNullConcatenationFailure()
    {
        $this->expectException(ConfigException::class);
        $parameters = ["parameter_key" => null];
        $this->container["parameter_key"] = function(){ return null; };
        $this->referenceResolver->resolve($parameters, "foo%parameter_key%", $this->tagMap);
    }

    public function testReferencedParameterArray()
    {
        $parameters = [
            "options" => ["foo" => "bar"]
        ];
        $array = $this->referenceResolver->resolve($parameters, "%options%", $this->tagMap);
        self::assertSame([
            "foo" => "bar"
        ], $array);
    }

    public function testReferencedParameterArrayResolve()
    {
        $parameters = [
            "options" => ["foo" => "%bar%"],
            "bar" => "chicken"
        ];

        $array = $this->referenceResolver->resolve($parameters, "%options%", $this->tagMap);
        self::assertSame([
            "foo" => "chicken"
        ], $array);
    }

    public function parameterEscapedResolveProvider()
    {
        return [
            [
                "%my_key_1%",
                "my_value_1"
            ],
            [
                "%my_key_1%50%%",
                "my_value_150%"
            ],
            [
                "%%%my_key_1%",
                "%my_value_1"
            ],
            [
                "%my_key_1%%%%my_key_2%",
                "my_value_1%my_value_2"
            ]
        ];
    }

    /**
     * @dataProvider parameterEscapedResolveProvider
     */
    public function testParameterEscapedResolve($parameter, $expected)
    {
        $parameters = [
            "my_key_1" => "my_value_1",
            "my_key_2" => "my_value_2"
        ];

        $value = $this->referenceResolver->resolve($parameters, $parameter, $this->tagMap);
        self::assertSame($expected, $value);
    }

    /**
     * @param string $name
     * @param string $value
     */
    private function setEnvVar(string $name, string $value)
    {
        putenv($name . "=" . $value);
    }
}