<?php
/**
 * Exception usually thrown when there was no store defined.
 * 
 * @package SplotCache
 * @subpackage Exceptions
 * @author Michał Dudek <michal@michaldudek.pl>
 * 
 * @copyright Copyright (c) 2013, Michał Dudek
 * @license MIT
 */
namespace Splot\Cache\Exceptions;

use MD\Foundation\Exceptions\NotFoundException;

class NoStoreException extends NotFoundException
{



}