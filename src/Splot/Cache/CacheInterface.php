<?php
/**
 * Cache interface.
 * 
 * @package SplotCache
 * @author Michał Dudek <michal@michaldudek.pl>
 * 
 * @copyright Copyright (c) 2013, Michał Dudek
 * @license MIT
 */
namespace Splot\Cache;

interface CacheInterface
{

    /**
     * Write to cache.
     * 
     * @param string $key Key under which to store the cache.
     * @param mixed $data Content to store in the cache.
     * @param int $ttl [optional] Time To Leave for the cache in seconds. Default: 0 (should be checked on read,
     *                 otherwise it will be cached indefinetely).
     */
    public function set($key, $data, $ttl = 0);

    /**
     * Read from cache.
     * 
     * @param string $key Key based on which to read from the cache.
     * @param int $age [optional] How old the cached key can be? Default: 0 - based on TTL.
     * @return mixed
     */
    public function get($key, $age = 0);

    /**
     * Check if key is stored in cache.
     * 
     * @param string $key Key based on which to read from the cache.
     * @param int $age [optional] How old the cached key can be? Default: 0 - based on TTL.
     * @return bool
     */
    public function has($key, $age = 0);

    /**
     * Clear the cache.
     * 
     * @param string $key Key based on which to clear the cache.
     */
    public function clear($key);

    /**
     * Clears everything in the cache.
     */
    public function flush();

    /*****************************************************
     * SETTERS AND GETTERS
     *****************************************************/
    /**
     * Sets the namespace for this cache.
     * 
     * @param string $namespace Cache namespace.
     */
    public function setNamespace($namespace);

    /**
     * Returns the namespace for this cache.
     * 
     * @return string
     */
    public function getNamespace();

    /**
     * Sets the cache to be enabled or not.
     * 
     * @param bool $enabled [optional] Default: true.
     */
    public function setEnabled($enabled = true);

    /**
     * Returns information whether the cache is enabled or not.
     * 
     * @return bool
     */
    public function getEnabled();

    /**
     * Returns information whether the cache is enabled or not.
     * 
     * @return bool
     */
    public function isEnabled();

}