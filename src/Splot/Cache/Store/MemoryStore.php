<?php
/**
 * Memory Store for caching. This store uses memory for caching, so it only lives for period of one request.
 * 
 * Also known as "array cache".
 * 
 * @package SplotCache
 * @subpackage Store
 * @author Michał Dudek <michal@michaldudek.pl>
 * 
 * @copyright Copyright (c) 2013, Michał Dudek
 * @license MIT
 */
namespace Splot\Cache\Store;

use MD\Foundation\Utils\ArrayUtils;
use MD\Foundation\Utils\StringUtils;

use Splot\Cache\Store\StoreInterface;
use Splot\Cache\CacheOptions;

class MemoryStore implements StoreInterface
{

    /**
     * Memory cache.
     * 
     * @var array
     */
    protected $cache = array();

    /**
     * Constructor.
     * 
     * @param array $config Array of configuration options. None required.
     */
    public function __construct(array $config) {
        // this cache doesn't require any configuration
    }

    /**
     * Reads from the given cache resource.
     * 
     * @param string $key Cache resource key.
     * @return mixed
     */
    public function read($key) {
        return isset($this->cache[$key]) ? $this->cache[$key] : null;
    }

    /**
     * Checks if cache resource exists.
     * 
     * @param string $key Cache resource key.
     * @return bool
     */
    public function exists($key) {
        return isset($this->cache[$key]);
    }

    /**
     * Write to the given cache resource.
     * 
     * @param string $key Cache resource key.
     * @param mixed $data Whatever data to be cached.
     */
    public function write($key, $data) {
        $this->cache[$key] = $data;
    }

    /**
     * Removes the given cache resource from the cache.
     * 
     * @param string $key Cache resource key.
     */
    public function remove($key) {
        if (isset($this->cache[$key])) {
            unset($this->cache[$key]);
        }
    }

    /**
     * Removes all resources in the cache, filtered by the optional namespace.
     * 
     * @param string $namespace [optional] Cache namespace if only that namespace needs to be removed.
     */
    public function removeAll($namespace = '') {
        // if no namespace given then clear all
        if (empty($namespace)) {
            $this->cache = array();
            return;
        }

        foreach($this->cache as $key => $data) {
            if (stripos($key, $namespace) === 0) {
                unset($this->cache[$key]);
            }
        }
    }

}