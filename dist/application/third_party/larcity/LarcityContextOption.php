<?php
# namespace LarCity\Shell;
class LarcityContextOption extends LarCity\Shell\ContextOption implements ContextOptionInterface {

    static function matches($mode = ContextOption::TEST_HOMEDEV) {
        switch ($mode) {
            case LarcityContextOption::TEST_SANDBOX:
                return preg_match("/^(sandbox2\.larcity\.com|demo\.larcity\.com|www3\.sandbox\.larcity\.com|larcitysbx01)/", $_SERVER['HTTP_HOST']);

            case LarcityContextOption::TEST_PROD:
                return is_dir('/var/www/html/api.larcity.com/') and preg_match('/^(larcityapp01|(www3|api3|api)\.larcity\.com)/i', $_SERVER['HTTP_HOST']);

            case LarcityContextOption::TEST_HOMENET:
                return preg_match("/^((www4|api4)\.)?larcity\.com$/", $_SERVER['HTTP_HOST']);
                //return !LarcityContextOption::matches(LarcityContextOption::TEST_PROD) and preg_match("/^www4\.larcity\.com/", $_SERVER['HTTP_HOST']);

            case LarcityContextOption::TEST_HOMEDEV:
                return is_dir('/Users/Shared/www/www3.larcity.com/') and preg_match("/localhost/", $_SERVER['HTTP_HOST']);
                //return preg_match("/^(dev\.larcity\.com|sandbox2\.larcity\.com|www3\.sandbox\.larcity\.com|larcitysbx01)/", $_SERVER['HTTP_HOST']);

            case LarcityContextOption::TEST_ROGUE:
                return is_dir('/Applications/MAMP/htdocs/www3.larcity.com/') and preg_match("/^localhost/", $_SERVER['HTTP_HOST']);
        }
        return false;
    }

}
