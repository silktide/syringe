<?php


namespace Silktide\Syringe\Tests;


use PHPUnit\Framework\TestCase;
use Pimple\Container;
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
        $array = $this->referenceResolver->resolveArray($this->container, ["foo" => "%parameter_key%", "bar" => "%parameter_key_2%"]);
        $this->assertSame(["foo" => "parameter_value", "bar" => "parameter_value_2"], $array);
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