<?php

namespace Silktide\Syringe\Tests\Loader;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Silktide\Syringe\Exception\LoaderException;
use Silktide\Syringe\Loader\PhpLoader;

class PhpLoaderTest extends TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    public function setUp() : void
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
        self::assertEquals($array, $expected);
    }

    public function testImportFailure()
    {
        $this->expectException(LoaderException::class);
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
        self::assertTrue($phpLoader->supports($filename));
    }

    public function testFilenameFailure()
    {
        $filename = $this->root->url() . "/example.json";
        $phpLoader = new PhpLoader();
        self::assertFalse($phpLoader->supports($filename));
    }
}