<?php
/**
 * Exception usually thrown when trying to redefine a store.
 * 
 * @package SplotCache
 * @subpackage Exceptions
 * @author Michał Dudek <michal@michaldudek.pl>
 * 
 * @copyright Copyright (c) 2013, Michał Dudek
 * @license MIT
 */
namespace Splot\Cache\Exceptions;

use MD\Foundation\Exceptions\ReadOnlyException;

class StoreDefinedException extends ReadOnlyException
{

}
