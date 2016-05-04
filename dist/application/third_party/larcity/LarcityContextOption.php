<?php
namespace LarCity\CodeIgniter\Shell;

/** @FYI example customization of the ContextOption class **/
require_once __DIR__ . DIRECTORY_SEPARATOR . 'ContextOption.php';

class LarcityContextOption extends ContextOption {
    
    const FSTORE_PROD = 'gs://filestore.shadowboxapps.com/';
    const FSTORE_HOMENET = '/var/www/html/www3.shadowboxapps.com/files/';
    const FSTORE_HOMEDEV = '/Library/Server/Web/Data/Sites/com.sbx.v3/data/';
    const FSTORE_SANDBOX = '/var/www/html/www3.shadowboxapps.com/files/';
    const FSTORE_ROGUE = '/Applications/MAMP/htdocs/com.sbx.v3/data/';
    const TEST_PROD = 'prod';
    const TEST_BETA = 'beta';
    const TEST_DEV = 'dev';
    const TEST_SANDBOX = 'sandbox';
    const TEST_HOMENET = 'homenetwork';
    const TEST_HOMEDEV = 'homedev';
    const TEST_ROGUE = 'rogue';
    
    /** Example scenarios tested for @ LarCity to figure out where an app is running **/
    static function matches($mode = self::TEST_HOMEDEV) {
        switch ($mode) {
            case self::TEST_SANDBOX:
                return preg_match("/^(sandbox2\.larcity\.com|demo\.larcity\.com|www3\.sandbox\.larcity\.com|larcitysbx01)/", $_SERVER['HTTP_HOST']);

            case self::TEST_PROD:
                return is_dir('/var/www/html/api.larcity.com/') and preg_match('/^(larcityapp01|((api|www3)\.)?larcity\.com)/i', $_SERVER['HTTP_HOST']);

            case self::TEST_HOMENET:
                return preg_match("/^www4\.larcity\.com$/", $_SERVER['HTTP_HOST']);

            case self::TEST_HOMEDEV:
                return is_dir('/Users/Shared/www/www3.larcity.com/') and preg_match("/localhost/", $_SERVER['HTTP_HOST']);
                //return preg_match("/^(dev\.larcity\.com|sandbox2\.larcity\.com|www3\.sandbox\.larcity\.com|larcitysbx01)/", $_SERVER['HTTP_HOST']);

            case self::TEST_ROGUE:
                return is_dir('/Applications/MAMP/htdocs/www3.larcity.com/') and preg_match("/^localhost/", $_SERVER['HTTP_HOST']);
        }
        return false;
    }
    
    public static function testSet( $contextOptions = [] ) {
        foreach($contextOptions as $testName=>&$context) {
            if(is_a($context, 'LarcityContextOption')) {
                $context->setVerified($context->matches($testName) ? true : false);
            }
        }
    }
}
