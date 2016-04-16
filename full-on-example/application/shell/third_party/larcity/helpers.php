<?php
function makepath() {
    $bits = func_get_args();
    if (is_array($bits)) {
        return implode(DIRECTORY_SEPARATOR, $bits);
    }
}

function readcsv($file_uri) {
    /** @TODO support reading from remote URL * */
    if (!is_file($file_uri)) {
        throw new Exception("File must exist or be local. $file_uri is NOT a valid local file", 400);
    }
    /** @TODO check file size -> make sure not too large **/
    ini_set('auto_detect_line_endings', TRUE);
    $csv_data = array_map('str_getcsv', file($file_uri));
    ini_set('auto_detect_line_endings', FALSE);
    
    return $csv_data;
}

function protocol() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? "https" : "http";
}

function isHTTPS() {
    return preg_match("/^https/", protocol());
}

function windowspath($path) {
    return str_replace("/", DIRECTORY_SEPARATOR, $path);
}

function is_valid_domain_name($domain_name)
{
    return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name) //valid chars check
            && preg_match("/^.{1,253}$/", $domain_name) //overall length check
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name)   ); //length of each label
}