<?php
/**
 * Memcached Store for caching.
 * 
 * @package SplotCache
 * @subpackage Store
 * @author Michał Dudek <michal@michaldudek.pl>
 * 
 * @copyright Copyright (c) 2013, Michał Dudek
 * @license MIT
 */
namespace Splot\Cache\Store;

use Memcached;

use MD\Foundation\Exceptions\InvalidArgumentException;
use MD\Foundation\Utils\ArrayUtils;

use Splot\Cache\Store\StoreInterface;
use Splot\Cache\CacheOptions;

class MemcachedStore implements StoreInterface
{

    /**
     * Memcached client.
     * 
     * @var Memcached
     */
    protected $memcached;

    /**
     * Memory cache for versions of namespaces.
     * 
     * @var array
     */
    protected $namespaceVersions = array();

    /**
     * Constructor.
     * 
     * @param array $servers List of memcached servers.
     */
    public function __construct(array $servers) {
        // verify the structure
        foreach($servers as $server) {
            if (!ArrayUtils::checkValues($server, array('host', 'port'))) {
                throw new InvalidArgumentException('server array with non-empty keys "host" and "port"', $servers);
            }
        }

        $this->memcached = new Memcached();
        $this->memcached->addServers($servers);
    }

    /**
     * Reads from the given cache resource.
     * 
     * @param string $key Cache resource key.
     * @return mixed
     */
    public function read($key) {
        $key = $this->buildNamespacedKey($key);
        $result = $this->memcached->get($key);
        if (!$result && $this->memcached->getResultCode() === Memcached::RES_NOTFOUND) {
            return null;
        }

        return $result;
    }

    /**
     * Checks if cache resource exists.
     * 
     * @param string $key Cache resource key.
     * @return bool
     */
    public function exists($key) {
        return $this->read($key) !== null ? true : false;
    }

    /**
     * Write to the given cache resource.
     * 
     * @param string $key Cache resource key.
     * @param mixed $data Whatever data to be cached.
     */
    public function write($key, $data) {
        $key = $this->buildNamespacedKey($key);
        $this->memcached->set($key, $data);
    }

    /**
     * Removes the given cache resource from the cache.
     * 
     * @param string $key Cache resource key.
     */
    public function remove($key) {
        $key = $this->buildNamespacedKey($key);
        $this->memcached->delete($key);
    }

    /**
     * Removes all resources in the cache, filtered by the optional namespace.
     * 
     * @param string $namespace [optional] Cache namespace if only that namespace needs to be removed.
     */
    public function removeAll($namespace = '') {
        // if no namespace given then flush all
        if (empty($namespace)) {
            $this->memcached->flush();
            $this->namespaceVersions = array();
            return;
        }

        // use the incrementing namespace version pattern
        $this->memcached->increment($this->getNamespaceVersionKey($namespace));
        $this->namespaceVersions[$namespace] = 1;
    }

    /*****************************************************
     * HELPERS
     *****************************************************/
    /**
     * Builds a key that includes a namespace version for the given key.
     * 
     * @param string $key Cache resource key.
     */
    protected function buildNamespacedKey($key) {
        // can ignore index 0 as it means there's not really a namespace
        if (stripos($key, CacheOptions::NAMESPACE_SEPARATOR)) {
            $key = explode(CacheOptions::NAMESPACE_SEPARATOR, $key);
            $namespaceVersion = $this->getNamespaceVersion($key[0]);
            $key = ArrayUtils::pushAfter($key, $namespaceVersion, 1);
            $key = implode(CacheOptions::NAMESPACE_SEPARATOR, $key);
        }

        return $key;
    }

    /**
     * Returns current version of a namespace.
     * 
     * @param string $namespace Namespace.
     * @return int
     */
    protected function getNamespaceVersion($namespace) {
        if (isset($this->namespaceVersions[$namespace])) {
            return $this->namespaceVersions[$namespace];
        }

        $namespaceVersionKey = $this->getNamespaceVersionKey($namespace);
        $version = $this->memcached->get($namespaceVersionKey);
        $version = $version ? $version : 1;

        $this->namespaceVersions[$namespace] = $version;

        return $version;
    }

    /**
     * Returns a key for namespace version cache resource.
     * 
     * @param string $namespace Namespace.
     * @return string
     */
    protected function getNamespaceVersionKey($namespace) {
        return md5($namespace) . CacheOptions::NAMESPACE_SEPARATOR .'version';
    }

    /*****************************************************
     * SETTERS AND GETTERS
     *****************************************************/
    /**
     * Returns instance of the Memcached client used by this store.
     * 
     * @return Memcached
     */
    public function getMemcached() {
        return $this->memcached;
    }

}
