<?php
namespace Splot\Cache\Tests\Store;

use Splot\Cache\Store\FileStore;

class FileStoreTest extends \PHPUnit_Framework_TestCase
{

    protected $tmpDir;

    public function setUp() {
        parent::setUp();
        $this->tmpDir = realpath(dirname(__FILE__) .'/../../tests_tmp/');

        // clear all contents from it
        $dirIterator = new \RecursiveDirectoryIterator($this->tmpDir);
        $iterator = new \RecursiveIteratorIterator($dirIterator);
        $regexIteration = new \RegexIterator($iterator, '/^.+\\.cache$/i');

        foreach($regexIteration as $file => $fileInfo) {
            @unlink($file);
        }
    }

    public function testInterface() {
        $fileStore = new FileStore($this->tmpDir);
        $this->assertInstanceOf('Splot\Cache\Store\StoreInterface', $fileStore);
    }

    /**
     * @dataProvider provideData
     */
    public function testReadingAndWriting($key, $value) {
        $fileStore = new FileStore($this->tmpDir);

        // test that a key doesn't exist
        $this->assertFalse($fileStore->exists($key));

        // test that reading from this inexistent key returns null
        $this->assertNull($fileStore->read($key));

        // test that writing to a key will make it exist
        $fileStore->write($key, $value);
        $this->assertTrue($fileStore->exists($key));

        // test that reading from a created key will return the same thing
        $this->assertEquals($value, $fileStore->read($key));

        // test that removing a key will make it not readable again
        $fileStore->remove($key);
        $this->assertFalse($fileStore->exists($key));
    }

    public function testRemoveAll() {
        $fileStore = new FileStore($this->tmpDir);

        foreach($this->provideData() as $data) {
            $fileStore->write($data[0], $data[1]);
            $this->assertTrue($fileStore->exists($data[0]));
        }

        $fileStore->removeAll();
        foreach($this->provideData() as $data) {
            $this->assertFalse($fileStore->exists($data[0]));
        }
    }

    public function provideData() {
        $obj = new \stdClass();
        $obj->title = 'Lorem ipsum dolor sit amet';
        $obj->date = new \DateTime();

        return array(
            array('key1', 'lorem ipsum'),
            array('key.with.dot', 'dolor sit amet'),
            array('key/with/stuff', array(0, 1, 'adipiscit' => 'elit')),
            array('object_key', $obj),
            array('integer_value', 4),
            array('bool_val', false),
            array('key::separator', $obj)
        );
    }

}
