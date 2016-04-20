<?php
/**
 * Jaeger
 *
 * @copyright	Copyright (c) 2015-2016, mithra62
 * @link		http://jaeger-app.com
 * @version		1.0
 * @filesource 	./tests/CompressTest.php
 */
namespace JaegerApp\tests;

use JaegerApp\Files;

/**
 * Jaeger - Files object Unit Tests
 *
 * Contains all the unit tests for the \mithra62\Files object
 *
 * @package mithra62\Tests
 * @author Eric Lamb <eric@mithra62.com>
 */
class FilesTest extends \PHPUnit_Framework_TestCase
{

    protected $test_content = 'Test Content';

    public function testInit()
    {
        $this->assertClassHasAttribute('file_data', 'JaegerApp\\Files');
    }

    public function testWrite()
    {
        $test_file = $this->getWorkingDir() . 'test_file.txt';
        $file = new Files();
        
        $this->assertFileNotExists($test_file);
        
        $file->write($test_file, $this->test_content, 'a+');
        $this->assertFileExists($test_file);
        
        return $test_file;
    }

    /**
     * @depends testWrite
     */
    public function testRead($test_file)
    {
        $file = new Files();
        $this->assertEquals($this->test_content, $file->read($test_file));
        return $test_file;
    }

    /**
     * @depends testWrite
     */
    public function testDelete($test_file)
    {
        $file = new Files();
        $file->delete($test_file);
        $this->assertFileNotExists($test_file);
        
        return $test_file;
    }

    public function testCopyDir()
    {
        $file = new Files();
        $path = $this->dataPath() . DIRECTORY_SEPARATOR . 'languages';
        $destination = $this->getWorkingDir() . DIRECTORY_SEPARATOR . 'file_test';
        $file->copyDir($path, $destination);
        $this->assertFileExists($destination);
        return $destination;
    }

    /**
     * @depends testCopyDir
     */
    public function testGetFilenames($destination)
    {
        $file = new Files();
        $file_data = $file->getFilenames($destination);
        
        return $destination;
    }

    /**
     * @depends testGetFilenames
     */
    public function testRemoveDir($destination)
    {
        $file = new Files();
        $file_data = $file->deleteDir($destination, true);
        
        $this->assertFileNotExists($destination);
        return $destination;
    }

    /**
     * Tests the various states of the $file->file_data container
     */
    public function testFileData()
    {
        // check defaults
        $file = new Files();
        $this->assertEmpty($file->getFileData());
        
        // check adding works
        $file_data = array(
            'Foo',
            'Bar'
        );
        foreach ($file_data as $data) {
            $file->setFileData($data);
        }
        
        $this->assertNotEmpty($file->getFileData());
        $this->assertContains('Foo', $file->getFileData());
        $this->assertContains('Bar', $file->getFileData());
        $this->assertCount(2, $file->getFileData());
        
        // check breakdown
        $file->setFileData(false, true);
        $this->assertEmpty($file->getFileData());
    }

    public function testFilesizeFormat()
    {
        $number = 555555;
        $file = new Files();
        
        $this->assertEquals('543 KiB', $file->filesizeFormat($number));
        $this->assertEquals('556 kB', $file->filesizeFormat($number, 3, 'SI'));
        $this->assertEquals('4.24 Mib', $file->filesizeFormat($number, 3, 'IEC', 'b'));
        $this->assertEquals('0 B', $file->filesizeFormat(false));
    }
    
    /**
     * The full path to the data directory
     *
     * @return string
     */
    protected function dataPath()
    {
        return realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR.'data');
    }
    
    /**
     * The full path to the working directory any file system activity happens
     *
     * @return string
     */
    protected function getWorkingDir()
    {
        return $this->dataPath().DIRECTORY_SEPARATOR.'working_dir';
    }
}