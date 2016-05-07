<?php
namespace LarCity\Shell;

interface ContextOptionInterface {
    
    static function matches($mode);
    public static function testSet( $contextOptions = [] );
    public function getParameter($key);
    public function getParameters();
    public static function getActiveOfSet($contextOptionsSet=[]);
    public function isVerified();
    public function setVerified($boolean);
    
}

// declare context option class
class ContextOption {

    private $verified = false;
    private $parameters;

    // declare context constants
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

    public function __construct($params = [], $testId = ContextOption::TEST_HOMEDEV) {
        if(!empty($testId)) {
            $this->verified = $this->matches($testId);
        }
        $this->parameters = $params;
        return $this;
    }
    
    static function matches($mode = self::TEST_HOMEDEV) {
        switch ($mode) {
            case self::TEST_SANDBOX:
                return preg_match("/^(www3\.sandbox\.shadowboxapps\.com|larcitysbx01)/", $_SERVER['HTTP_HOST']);
                
            case self::TEST_BETA:
                return preg_match('/^beta\.sbx\.link$/i', $_SERVER['HTTP_HOST']);

            case self::TEST_PROD:
                return preg_match('/^(www3\.shadowboxapps\.com|larcityapp01|pages\.sbx\.link)/i', $_SERVER['HTTP_HOST']);

            case self::TEST_HOMEDEV:
                return is_dir(self::FSTORE_HOMEDEV) and preg_match("/^localhost/", $_SERVER['HTTP_HOST']);

            case self::TEST_ROGUE:
                return !is_dir(self::FSTORE_HOMEDEV) and preg_match("/^localhost/", $_SERVER['HTTP_HOST']);

            case self::TEST_HOMENET:
                return !self::matches(ContextOption::TEST_PROD) and is_dir(self::FSTORE_HOMENET);
        }
        return false;
    }

    public function getParameter($key) {
        if (!empty($this->parameters[$key])) {
            return $this->parameters[$key];
        }
    }
    
    public function setVerified( $boolean ) {
        $this->verified = $boolean;
    }
    
    public function isVerified() {
        return $this->verified;
    }
    
    public function getParameters() {
        return $this->parameters;
    }
    
    /** @requires array of ContextOption objects **/
    static function getActiveOfSet($contextOptionsSet = []) {
        if(is_array($contextOptionsSet)) {
            foreach($contextOptionsSet as $option) {
                if(!is_a($option, 'LarCity\Shell\ContextOption') and !is_subclass_of($option, 'LarCity\Shell\ContextOption')) {
                    die('Items in $contextOptionsSet MUST be instances of LarCity\Shell\ContextOption. Fatal error.');
                }
                if($option->isVerified()) {
                    return $option;
                }
            }
        }
        $suspect_files=['config/config.php','config/constants.php','config/database.php'];
        foreach($suspect_files as &$f) {
            $f= "<li>" . APPPATH . $f . "</li>";
        }
        die("No LarCity configured context detected. App initialization failed. To debug this issue, review configurations in the following files: <ul>" . implode(PHP_EOL, $suspect_files) . "</ul>");
    }
    
    static function testSet( $contextOptions = [] ) {
        foreach($contextOptions as $testName=>&$context) {
            if(is_a($context, 'LarCity\Shell\ContextOption') or is_subclass_of($context, 'LarCity\Shell\ContextOption')) {
                $context->setVerified($context->matches($testName) ? true : false);
            }
        }
    }
    
}

