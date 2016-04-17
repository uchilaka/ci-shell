<?php
namespace LarCity\CodeIgniter\Shell;

interface ContextOptionInterface {
    
    static function matches($mode);
    static function testSet( $contextOptions = [] );
    public function getParameter($key);
    public function getParameters();
    public function getActiveOfSet($contextOptionsSet=[]);
    public function isVerified();
    public function setVerified();
    
}

// declare context option class
class ContextOption {

    private $verified = false;
    private $parameters;

    // declare context constants
    const ENVTEST_PROD = 'prod';
    const ENVTEST_BETA = 'beta';
    const ENVTEST_DEV = 'dev';
    const ENVTEST_SANDBOX = 'sandbox';
    const ENVTEST_HOMENET = 'homenetwork';
    const ENVTEST_HOMEDEV = 'homedev';
    const ENVTEST_ROGUE = 'rogue';

    public function __construct($params = [], $testId = ContextOption::ENVTEST_HOMEDEV) {
        if(!empty($testId)) {
            $this->verified = $this->matches($testId);
        }
        $this->parameters = $params;
        return $this;
    }
    
    static function matches($mode = self::ENVTEST_HOMEDEV) {
        switch ($mode) {
            case self::ENVTEST_SANDBOX:
                // example test for sandbox state
                return preg_match('/https?\:\/\/(www\.)?.+sandbox.+\.(com|org)!/', $_SERVER['HTTP_HOST']);

                // example test for rogue state (e.g. off your laptop 'off the reservation'
            case self::ENVTEST_ROGUE:
                return preg_match("/^localhost/", $_SERVER['HTTP_HOST']);

            case self::ENVTEST_PROD:
                return preg_match("/^https?\:\/\/(www3?\.)?larcity\.com/", $_SERVER['HTTP_HOST']);
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
    public static function getActiveOfSet($contextOptionsSet = []) {
        if(is_array($contextOptionsSet)) {
            foreach($contextOptionsSet as $option) {
                if(!is_a($option, 'LarCity\CodeIgniter\Shell\ContextOption')) {
                    die("Items in $contextOptionsSet MUST be instances of ContextOption. Class type found: " . get_class($option) . "  Fatal error.");
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
    
    public static function testSet( $contextOptions = [] ) {
        foreach($contextOptions as $testName=>&$context) {
            if(is_a($context, 'ContextOption')) {
                $context->setVerified($context->matches($testName) ? true : false);
            }
        }
    }
    
}

