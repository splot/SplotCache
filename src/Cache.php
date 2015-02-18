<?php
/**
 * Cache system.
 * 
 * @package SplotCache
 * @author Michał Dudek <michal@michaldudek.pl>
 * 
 * @copyright Copyright (c) 2013, Michał Dudek
 * @license MIT
 */
namespace Splot\Cache;

use Splot\Cache\Store\StoreInterface;
use Splot\Cache\CacheInterface;
use Splot\Cache\CacheOptions;

class Cache implements CacheInterface
{

    /**
     * Cache store.
     * 
     * @var StoreInterface
     */
    protected $store;

    /**
     * Cache namespace that's added to all cache keys.
     * 
     * @var string|null
     */
    protected $namespace = null;

    /**
     * Flag for cache enabled.
     * 
     * @var bool
     */
    protected $enabled = true;

    /**
     * Memory cache that stores all info about already read meta cache data.
     * 
     * Helps with faster retrieval when first calling ->has() and then ->get().
     * 
     * @var array
     */
    protected $metaCache = array();

    /**
     * Constructor.
     * 
     * @param StoreInterface $store Cache store.
     */
    public function __construct(StoreInterface $store, $namespace = null, $enabled = true) {
        $this->store = $store;
        $this->namespace = strtolower($namespace);
        $this->enabled = $enabled;
    }

    /**
     * Write to cache.
     * 
     * @param string $key Key under which to store the cache.
     * @param mixed $data Content to store in the cache.
     * @param int $ttl [optional] Time To Leave for the cache in seconds. Default: 0 (should be checked on read,
     *                 otherwise it will be cached indefinetely).
     */
    public function set($key, $data, $ttl = 0) {
        if (!$this->enabled) {
            return;
        }

        $metaKey = $this->buildMetaKey($key);
        $key = $this->buildKey($key);

        $ttl = intval($ttl);
        $now = time();

        $this->store->write($key, $data);
        $this->store->write($metaKey, array(
            'ttl' => intval($ttl),
            'created_at' => $now,
            'expires' => ($ttl) ? $now + $ttl : 0
        ));
    }

    /**
     * Read from cache.
     * 
     * @param string $key Key based on which to read from the cache.
     * @param int $age [optional] How old the cached key can be? Default: 0 - based on TTL.
     * @param callable $callback [optional] Callback that is called if the resource was not found in the cache and that
     *                           should return a value to cache. It will set the TTL of the cached resource to what was
     *                           set in $age. Similar to Memcached "read-through callbacks", but it doesn't get any
     *                           arguments and should return the cached resource. That resource will then also be sent
     *                           as a return value of the get() method.
     * @return mixed
     */
    public function get($key, $age = 0, $callback = null) {
        if (!$this->enabled) {
            return null;
        }

        $has = $this->has($key, $age);

        // if there is a callback defined then don't exit yet
        if (!$has && !is_callable($callback)) {
            return null;
        }

        // if resource is definetely available then read it
        if ($has) {
            $resourceKey = $this->buildKey($key);
            $resource = $this->store->read($resourceKey);

            // if resource was found or if the $callback hasn't been defined, return it (or null)
            if ($resource !== null || !is_callable($callback)) {
                return $resource;
            }
        }

        // if $callback is set then call it to get the resource to be cached
        $resource = call_user_func($callback);
        // cache it
        $this->set($key, $resource, $age);
        return $resource;
    }

    /**
     * Check if data is stored in cache.
     * 
     * @param string $key Key based on which to read from the cache.
     * @param int $age [optional] How old the cached key can be? Default: 0 - based on TTL.
     * @return bool
     */
    public function has($key, $age = 0) {
        if (!$this->enabled) {
            return false;
        }

        $metaKey = $this->buildMetaKey($key);
        $meta = (isset($this->metaCache[$metaKey])) ? $this->metaCache[$metaKey] : $this->store->read($metaKey);

        // if no meta then don't even check for the resource itself
        if (!$meta) {
            return false;
        }

        /* check the cache's age */
        $now = time();

        // check the age in meta data if so desired
        if ($age) {
            $createdAfter = $now - intval($age);
            if (!isset($meta['created_at']) || $createdAfter > $meta['created_at']) {
                return false;
            }
        }

        // check when the cache was set to expire (if at all)
        if (isset($meta['expires']) && $meta['expires']) {
            if ($now > $meta['expires']) {
                return false;
            }
        }

        /* and try to read the resource itself just in case it was deleted for any reason */
        $resourceKey = $this->buildKey($key);
        $data = $this->store->exists($resourceKey);
        return ($data) ? true : false;
    }

    /**
     * Clear the cache.
     * 
     * @param string $key Key based on which to clear the cache.
     */
    public function clear($key) {
        $metaKey = $this->buildMetaKey($key);
        $resourceKey = $this->buildKey($key);

        $this->store->remove($metaKey);
        $this->store->remove($resourceKey);

        if (isset($this->metaCache[$metaKey])) {
            unset($this->metaCache[$metaKey]);
        }
    }

    /**
     * Clears everything in the cache.
     */
    public function flush() {
        $this->store->removeAll($this->namespace);
    }

    /*****************************************************
     * HELPERS
     *****************************************************/
    /**
     * Builds a full key for a cached resource based on the cache's namespace.
     * 
     * @param string $key Base key for the resource.
     * @param string $type [optional] Type of the key. Default: 'resource'.
     * @return string
     */
    protected function buildKey($key, $type = 'resource') {
        $key = $type . CacheOptions::SEPARATOR . $key;

        if (empty($this->namespace)) {
            return $key;
        }

        return $this->namespace . CacheOptions::NAMESPACE_SEPARATOR . $key;
    }

    /**
     * Builds a full ket for meta data about the cached resource, based on the cache's namespace.
     * 
     * @param string $key Base key for the resource.
     * @return string
     */
    protected function buildMetaKey($key) {
        return $this->buildKey($key, 'meta');
    }

    /*****************************************************
     * SETTERS AND GETTERS
     *****************************************************/
    /**
     * Sets the namespace for this cache.
     * 
     * @param string $namespace Cache namespace.
     */
    public function setNamespace($namespace) {
        $this->namespace = strtolower($namespace);
    }

    /**
     * Returns the namespace for this cache.
     * 
     * @return string
     */
    public function getNamespace() {
        return $this->namespace;
    }

    /**
     * Sets the cache to be enabled or not.
     * 
     * @param bool $enabled [optional] Default: true.
     */
    public function setEnabled($enabled = true) {
        $this->enabled = $enabled;
    }

    /**
     * Returns information whether the cache is enabled or not.
     * 
     * @return bool
     */
    public function getEnabled() {
        return $this->enabled;
    }

    /**
     * Returns information whether the cache is enabled or not.
     * 
     * @return bool
     */
    public function isEnabled() {
        return $this->getEnabled();
    }

}
