<?php
/**
 * Cache provider - a cache factory for convenience.
 * 
 * @package SplotCache
 * @author Michał Dudek <michal@michaldudek.pl>
 * 
 * @copyright Copyright (c) 2013, Michał Dudek
 * @license MIT
 */
namespace Splot\Cache;

use MD\Foundation\Debug\Debugger;

use Splot\Cache\Exceptions\CacheDefinedException;
use Splot\Cache\Exceptions\StoreDefinedException;
use Splot\Cache\Exceptions\NoCacheException;
use Splot\Cache\Exceptions\NoStoreException;
use Splot\Cache\Store\StoreInterface;
use Splot\Cache\Cache;
use Splot\Cache\CacheOptions;

class CacheProvider
{

    /**
     * Container for all registered caches.
     * 
     * @var array
     */
    protected $caches = array();

    /**
     * Container for all registered stores.
     * 
     * @var array
     */
    protected $stores = array();

    /**
     * Global namespace that is prefixed to all caches.
     * 
     * @var string
     */
    protected $globalNamespace = '';

    /**
     * Constructor.
     * 
     * @param StoreInterface $defaultStore Default cache store to use with caches.
     * @param array $options [optional] Additional options, including (array) 'stores' and (string) 'global_namespace'.
     */
    public function __construct(StoreInterface $defaultStore, array $options = array()) {
        $options = array_merge(array(
            'stores' => array(),
            'global_namespace' => ''
        ), $options);

        // set global namespace
        $this->globalNamespace = $options['global_namespace'];

        // register the default store
        $this->registerStore('default', $defaultStore);

        // register other stores passed in options
        foreach($options['stores'] as $storeName => $store) {
            $this->registerStore($storeName, $store);
        }
    }

    /**
     * Provides a cache by getting it from registered caches or registering it if it's not yet registered.
     * 
     * @param string $name Name of the cache to provide.
     * @param string $store [optional] Name of a registered store to use if this cache has to be registered. Default: 'default'.
     * @return Cache
     */
    public function provide($name, $store = 'default') {
        try {
            return $this->getCache($name);
        } catch(\Exception $e) {
            return $this->registerCache($name, $store);
        }
    }

    /**
     * Registers a cache with the given name and store.
     * 
     * @param string $name Name of the cache to register.
     * @param StoreInterface|string $store [optional] Store to be used with this cache. Either an object of StoreInterface or 
     *                                     a string that's a name of previously registered store. Default: 'default'.
     * @return Cache
     * 
     * @throws CacheDefinedException When a cache with this name has already been defined.
     * @throws \InvalidArgumentException When the $store argument is invalid, ie. is not a valid store name or is not
     *                                   a valid store object.
     */
    public function registerCache($name, $store = 'default') {
        try {
            $cache = $this->getCache($name);
            // if no exception has been thrown then it means that such cache is already defined
            throw new CacheDefinedException('Cache with name "'. $name .'" has alread been registered.');
        } catch(NoCacheException $e) {
            // don't do anything, everything's fine ;)
        }

        // proceed with registering the cache
        if (is_string($store)) {
            $store = $this->getStore($store);
        }

        if (!is_object($store) || !($store instanceof StoreInterface)) {
            throw new \InvalidArgumentException('Cache store must implement "Splot\Cache\Store\StoreInterface", "'. Debugger::getType($store) .'" given.');
        }

        $fullname = $this->buildName($name);
        $cache = new Cache($store, $fullname);
        $this->caches[$fullname] = $cache;
        return $cache;
    }

    /**
     * Registers a cache store.
     * 
     * @param string $name Name of the store to register. Has to be unique.
     * @param StoreInterface $store Store to be registered.
     * 
     * @throws StoreDefinedException When trying to overwrite already defined store.
     */
    public function registerStore($name, StoreInterface $store) {
        if (isset($this->stores[$name])) {
            throw new StoreDefinedException('Cannot overwrite already defined store. Tried to register store "'. $name .'".');
        }

        $this->stores[$name] = $store;
    }

    /*****************************************************
     * HELPERS
     *****************************************************/
    /**
     * Builds a full name for a cache based on the preconfigured global namespace.
     * 
     * @param string $name Base name for the cache.
     * @return string
     */
    protected function buildName($name) {
        if (empty($this->globalNamespace)) {
            return $name;
        }

        return $this->globalNamespace . CacheOptions::SEPARATOR . $name;
    }

    /*****************************************************
     * SETTERS AND GETTERS
     *****************************************************/
    /**
     * Returns the cache with the given name.
     * 
     * @param string $name Name of the cache.
     * @return Cache
     * 
     * @throws NoCacheException When there is no such cache registered.
     */
    public function getCache($name) {
        $fullname = $this->buildName($name);

        if (!isset($this->caches[$fullname])) {
            throw new NoCacheException('There is no cache "'. $name .'" registered.');
        }

        return $this->caches[$fullname];
    }

    /**
     * Returns store registered under the given name.
     * 
     * @param string $name [optional] Name of the store to get. If no name given then default store will be returned.
     * @return StoreInterface
     * 
     * @throws NoStoreException When no such store is defined.
     */
    public function getStore($name = 'default') {
        if (!isset($this->stores[$name])) {
            throw new NoStoreException('No store called "'. $name .'" defined.');
        }

        return $this->stores[$name];
    }

    /**
     * Returns all registered caches.
     * 
     * @return array
     */
    public function getCaches() {
        return $this->caches;
    }

    /**
     * Sets the global namespace for all caches.
     * 
     * @param string $globalNamespace Global namespace that will be prepended to all cache names.
     */
    public function setGlobalNamespace($globalNamespace) {
        $this->globalNamespace = $globalNamespace;
    }

    /**
     * Returns the global namespace for all caches.
     * 
     * @return string
     */
    public function getGlobalNamespace() {
        return $this->globalNamespace;
    }

}