<?php

namespace Silktide\Syringe\Tests\Loader;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Silktide\Syringe\Loader\PhpLoader;

class PhpLoaderTest extends TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    public function setUp()
    {
        $this->root = vfsStream::setup();
    }

    public function testImportSuccess()
    {
        $code = '<?php return ["services" => []];';
        $expected = ["services" => []];

        $filename = $this->root->url() . "/example.php";
        file_put_contents($filename, $code);
        $phpLoader = new PhpLoader();
        $array = $phpLoader->loadFile($filename);
        $this->assertEquals($array, $expected);
    }

    /**
     * @expectedException \Silktide\Syringe\Exception\LoaderException
     */
    public function testImportFailure()
    {
        $code = '<?php return "bananas";';
        $filename = $this->root->url() . "/example.php";
        file_put_contents($filename, $code);
        $phpLoader = new PhpLoader();
        $phpLoader->loadFile($filename);
    }

    public function testFilenameSuccess()
    {
        $filename = $this->root->url() . "/example.php";
        $phpLoader = new PhpLoader();
        $this->assertTrue($phpLoader->supports($filename));
    }

    public function testFilenameFailure()
    {
        $filename = $this->root->url() . "/example.json";
        $phpLoader = new PhpLoader();
        $this->assertFalse($phpLoader->supports($filename));
    }
}