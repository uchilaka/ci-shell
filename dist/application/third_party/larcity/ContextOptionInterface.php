<?php
namespace LarCity\Shell;

interface ContextOptionInterface {
    
    static function matches($mode);
    static function testSet( $contextOptions = [] );
    public function getParameter($key);
    public function getParameters();
    public function getActiveOfSet($contextOptionsSet=[]);
    public function isVerified();
    public function setVerified();
    
}