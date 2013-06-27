<?php
/**
 * Interface for stores used in Splot Caches.
 * 
 * @package SplotCache
 * @subpackage Store
 * @author Michał Dudek <michal@michaldudek.pl>
 * 
 * @copyright Copyright (c) 2013, Michał Dudek
 * @license MIT
 */
namespace Splot\Cache\Store;

interface StoreInterface
{

    /**
     * Reads from the given cache resource.
     * 
     * @param string $key Cache resource key.
     * @return mixed
     */
    public function read($key);

    /**
     * Checks if cache resource exists.
     * 
     * @param string $key Cache resource key.
     * @return bool
     */
    public function exists($key);

    /**
     * Write to the given cache resource.
     * 
     * @param string $key Cache resource key.
     * @param mixed $data Whatever data to be cached.
     */
    public function write($key, $data);

    /**
     * Removes the given cache resource from the cache.
     * 
     * @param string $key Cache resource key.
     */
    public function remove($key);

    /**
     * Removes all resources in the cache, filtered by the optional namespace.
     * 
     * @param string $namespace [optional] Cache namespace if only that namespace needs to be removed.
     */
    public function removeAll($namespace = '');

}