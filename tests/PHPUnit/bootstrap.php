<?php
/**
 * Bootstrap PHPUnit.
 *
 * @package GitHookd
 */

namespace GitHookd;

if (! defined('TMPDIR')) {
    define('TMPDIR', __DIR__ . '../../tmp/');
}

if (! file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    throw new \PHPUnit_Framework_Exception(
        "You must use Composer to install the test suite's dependencies!"
    );
}

require_once __DIR__ . '/MockArgument.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/Setup.php';
