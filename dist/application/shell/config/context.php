<?php
/** Example environment setup using customized ContextOption sub class **/

// Declare third party path constant
if(!defined('_3RD_PARTY_PATH_NOTAIL')) {
    define('_3RD_PARTY_PATH_NOTAIL', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'third_party');
}
require_once _3RD_PARTY_PATH_NOTAIL . DIRECTORY_SEPARATOR . 'larcity' . DIRECTORY_SEPARATOR . 'helpers.php';

// Run environment tests
require_once makepath(__DIR__, 'LarcityContextOption.php');

$contexts = [
    LarcityContextOption::TEST_PROD => new LarcityContextOption([
        'asset_path' => '/usr/share/codelibs/',
        'env' => \LarcityContextOption::TEST_PROD
    ]),
    LarcityContextOption::TEST_SANDBOX => new LarcityContextOption([
        'asset_path' => '/usr/share/codelibs/',
        'env' => \LarcityContextOption::TEST_SANDBOX
    ]),
    LarcityContextOption::TEST_HOMENET => new LarcityContextOption([
        'asset_path' => '/usr/share/codelibs/',
        'env' => \LarcityContextOption::TEST_HOMENET
    ]),
    LarcityContextOption::TEST_HOMEDEV => new LarcityContextOption([
        'asset_path' => '/usr/share/codelibs/',
        'env' => \LarcityContextOption::TEST_HOMEDEV
    ]),
    LarcityContextOption::TEST_ROGUE => new LarcityContextOption([
        'asset_path' => '/Applications/MAMP/htdocs/assets/',
        'env' => \LarcityContextOption::TEST_ROGUE
    ]),
];

// test and configure active context
LarcityContextOption::testSet($contexts);

$config['context'] = LarcityContextOption::getActiveOfSet($contexts);
