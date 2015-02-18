<?php
namespace Splot\Cache\Tests\Store;

use Splot\Cache\Store\MemoryStore;

class MemoryStoreTest extends \PHPUnit_Framework_TestCase
{

    public function testInterface() {
        $store = new MemoryStore();
        $this->assertInstanceOf('Splot\Cache\Store\StoreInterface', $store);
    }

    /**
     * @dataProvider provideData
     */
    public function testReadingAndWriting($key, $value) {
        $store = new MemoryStore();

        // test that a key doesn't exist
        $this->assertFalse($store->exists($key));

        // test that reading from this inexistent key returns null
        $this->assertNull($store->read($key));

        // test that writing to a key will make it exist
        $store->write($key, $value);
        $this->assertTrue($store->exists($key));

        // test that reading from a created key will return the same thing
        $this->assertEquals($value, $store->read($key));

        // test that removing a key will make it not readable again
        $store->remove($key);
        $this->assertFalse($store->exists($key));
    }

    public function testRemoveAll() {
        $store = new MemoryStore();

        foreach($this->provideData() as $data) {
            $store->write($data[0], $data[1]);
            $this->assertTrue($store->exists($data[0]));
        }

        $store->removeAll();
        foreach($this->provideData() as $data) {
            $this->assertFalse($store->exists($data[0]));
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
