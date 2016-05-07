<?php
/** Example implementation of ContextOption class to determine app context **/
use LarCity\Shell;

// Run environment tests
require_once makepath(_3RD_PARTY_PATH_NOTAIL, 'larcity', 'LarcityContextOption.php');

$contexts = [
    Shell\LarcityContextOption::TEST_PROD => new Shell\LarcityContextOption([
        'asset_path' => '/usr/share/codelibs/',
        'env' => Shell\LarcityContextOption::TEST_PROD
    ]),
    Shell\LarcityContextOption::TEST_SANDBOX => new Shell\LarcityContextOption([
        'asset_path' => '/usr/share/codelibs/',
        'env' => Shell\LarcityContextOption::TEST_SANDBOX
    ]),
    Shell\LarcityContextOption::TEST_HOMENET => new Shell\LarcityContextOption([
        'asset_path' => '/usr/share/codelibs/',
        'env' => Shell\LarcityContextOption::TEST_HOMENET
    ]),
    Shell\LarcityContextOption::TEST_HOMEDEV => new Shell\LarcityContextOption([
        'asset_path' => '/usr/share/codelibs/',
        'env' => Shell\LarcityContextOption::TEST_HOMEDEV
    ]),
    Shell\LarcityContextOption::TEST_ROGUE => new Shell\LarcityContextOption([
        'asset_path' => '/Applications/MAMP/htdocs/assets/',
        'env' => Shell\LarcityContextOption::TEST_ROGUE
    ]),
];

// test and configure active context
Shell\LarcityContextOption::testSet($contexts);

$config['context'] = Shell\LarcityContextOption::getActiveOfSet($contexts);
