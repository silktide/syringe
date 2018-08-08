<?php


namespace Silktide\Syringe\Tests;


use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Silktide\Syringe\Exception\ConfigException;
use Silktide\Syringe\ReferenceResolver;
use Silktide\Syringe\Token;

class ReferenceResolverTest extends TestCase
{
    const EXAMPLE_TEST = "example_result";

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var ReferenceResolver
     */
    protected $referenceResolver;

    public function setUp()
    {
        $this->container = new Container();
        $this->referenceResolver = new ReferenceResolver();
    }

    public function testRegularConstantResolve()
    {
        define("foo", "bar");
        
        $value = $this->referenceResolver->resolve(
            $this->container,
            Token::CONSTANT_CHAR . "foo". Token::CONSTANT_CHAR
        );
        $this->assertEquals("bar", $value);
    }

    public function testClassConstantResolve()
    {
        $value = $this->referenceResolver->resolve(
            $this->container,
            Token::CONSTANT_CHAR . "Silktide\\Syringe\\Tests\\ReferenceResolverTest::EXAMPLE_TEST". Token::CONSTANT_CHAR
        );
        $this->assertEquals("example_result", $value);
    }

    /**
     * @expectedException \Silktide\Syringe\Exception\ConfigException
     */
    public function testConstantNonExistentResolve()
    {
        $this->referenceResolver->resolve($this->container,Token::CONSTANT_CHAR . "bar". Token::CONSTANT_CHAR);
    }

    public function testEnvironmentResolve()
    {
        $this->setEnvVar("banana", "salad");
        $value = $this->referenceResolver->resolve($this->container, Token::ENV_CHAR . "banana" . Token::ENV_CHAR);
        $this->assertEquals("salad", $value);
    }


    public function testFalseEnvironmentResolve()
    {
        $this->setEnvVar("chips", false);
        $value = $this->referenceResolver->resolve($this->container, Token::ENV_CHAR . "chips" . Token::ENV_CHAR);
        $this->assertEquals(false, $value);
        // Herein lies the rub, environment variables do not understant the concept of true or false, so it'll change it
        // to in this case, ''
        $this->assertSame('', $value);
    }


    /**
     * @expectedException \Silktide\Syringe\Exception\ConfigException
     */
    public function testFailedEnvironmentResolve()
    {
        $this->referenceResolver->resolve($this->container, Token::ENV_CHAR . "chicken" . Token::ENV_CHAR);
    }

    public function testParameterResolve()
    {
        $this->container["parameter_key"] = function(){return "parameter_value";};
        $value = $this->referenceResolver->resolve($this->container, Token::PARAMETER_CHAR . "parameter_key" . Token::PARAMETER_CHAR);
        $this->assertEquals("parameter_value", $value);
    }

    /**
     * @expectedException \Silktide\Syringe\Exception\ConfigException
     */
    public function testFailedParameterResolve()
    {
        $this->referenceResolver->resolve($this->container, Token::PARAMETER_CHAR . "parameter_key" . Token::PARAMETER_CHAR);
    }

    public function testRecursiveParameterResolve()
    {
        $this->container["parameter_key"] = function(){return Token::PARAMETER_CHAR . "parameter_key_2" . Token::PARAMETER_CHAR;};
        $this->container["parameter_key_2"] = function(){return "parameter_value";};
        $value = $this->referenceResolver->resolve($this->container, Token::PARAMETER_CHAR . "parameter_key" . Token::PARAMETER_CHAR);
        $this->assertEquals("parameter_value", $value);
    }

    public function testParameterArrayResolve()
    {
        $this->container["parameter_key"] = function(){return "parameter_value";};
        $this->container["parameter_key_2"] = function(){return "parameter_value_2";};
        $array = $this->referenceResolver->resolveArray($this->container, [
            "foo" => Token::PARAMETER_CHAR . "parameter_key" . Token::PARAMETER_CHAR,
            "bar" => Token::PARAMETER_CHAR . "parameter_key_2" . Token::PARAMETER_CHAR
        ]);
        $this->assertSame(["foo" => "parameter_value", "bar" => "parameter_value_2"], $array);
    }


    public function testRecursiveParameterArrayResolve()
    {
        $this->container["parameter_key"] = function(){return "parameter_value";};
        $this->container["parameter_key_2"] = function(){return "parameter_value_2";};
        $array = $this->referenceResolver->resolveArray($this->container, [
            "foo" => [
                Token::PARAMETER_CHAR . "parameter_key" . Token::PARAMETER_CHAR,
                Token::PARAMETER_CHAR . "parameter_key_2" . Token::PARAMETER_CHAR
            ]
        ]);

        $this->assertSame([
            "foo" => [
                "parameter_value",
                "parameter_value_2"
            ]
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
        $this->container["my_key_1"] = function(){return "my_value_1";};
        $this->container["my_key_2"] = function(){return "my_value_2";};
        $value = $this->referenceResolver->resolve($this->container, $parameter);
        $this->assertSame($expected, $value);
    }

    /**
     * @param $name
     * @param $value
     */
    private function setEnvVar($name, $value)
    {
        putenv($name . "=" . $value);
    }
}