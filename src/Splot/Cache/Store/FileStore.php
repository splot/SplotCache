<?php
/**
 * File Store for caching. This store uses filesystem in the configured directory to store cache resources.
 * 
 * @package SplotCache
 * @subpackage Store
 * @author Michał Dudek <michal@michaldudek.pl>
 * 
 * @copyright Copyright (c) 2013, Michał Dudek
 * @license MIT
 */
namespace Splot\Cache\Store;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

use MD\Foundation\Utils\ArrayUtils;
use MD\Foundation\Utils\StringUtils;

use Splot\Cache\Store\StoreInterface;
use Splot\Cache\CacheOptions;

class FileStore implements StoreInterface
{

    /**
     * Location of the cache directory.
     * 
     * @var string
     */
    protected $dir;

    /**
     * Constructor.
     * 
     * @param array $config Array of configuration options. Must contain "dir".
     */
    public function __construct(array $config) {
        // check for all required info
        if (!ArrayUtils::checkValues($config, array('dir'))) {
            throw new InvalidArgumentException('"'. get_called_class() .'::__construct()"  expects 1st argument to be an array containing non-empty key "dir", "'. implode('", "', array_keys($config)) .'" given.');
        }

        $this->dir = rtrim($config['dir'], DIRECTORY_SEPARATOR);
    }

    /**
     * Reads from the given cache resource.
     * 
     * @param string $key Cache resource key.
     * @return mixed
     */
    public function read($key) {
        $file = $this->keyToFilePath($key);
        if (!file_exists($file)) {
            return null;
        }

        $data = file_get_contents($file);
        return unserialize($data);
    }

    /**
     * Checks if cache resource exists.
     * 
     * @param string $key Cache resource key.
     * @return bool
     */
    public function exists($key) {
        $file = $this->keyToFilePath($key);
        return file_exists($file);
    }

    /**
     * Write to the given cache resource.
     * 
     * @param string $key Cache resource key.
     * @param mixed $data Whatever data to be cached.
     */
    public function write($key, $data) {
        $file = $this->keyToFilePath($key);
        $dir = dirname($file);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $data = serialize($data);
        file_put_contents($file, $data);
    }

    /**
     * Removes the given cache resource from the cache.
     * 
     * @param string $key Cache resource key.
     */
    public function remove($key) {
        $file = $this->keyToFilePath($key);
        @unlink($file);
    }

    /**
     * Removes all resources in the cache, filtered by the optional namespace.
     * 
     * @param string $namespace [optional] Cache namespace if only that namespace needs to be removed.
     */
    public function removeAll($namespace = '') {
        $dir = dirname($this->keyToFilePath($namespace . CacheOptions::SEPARATOR .'.ignore'));

        $iterator = new RecursiveDirectoryIterator($dir);
        $iterator = new RecursiveIteratorIterator($iterator);
        $iterator = new RegexIterator($iterator, '/^.+\\.cache$/i');

        foreach($iterator as $file => $fileInfo) {
            @unlink($file);
        }
    }

    /*****************************************************
     * HELPERS
     *****************************************************/
    /**
     * Translates cache resource key to full file path.
     * 
     * @param string $key Key to be translated.
     * @return string
     */
    protected function keyToFilePath($key) {
        $keyPath = explode(CacheOptions::SEPARATOR, $key);
        $keyPath = array_map(function($v) {
            return StringUtils::fileNameFriendly($v);
        }, $keyPath);
        array_push($keyPath, md5(array_pop($keyPath)));
        return $this->dir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $keyPath) .'.cache';
    }

}