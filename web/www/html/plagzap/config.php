<?php
/**
 * Created by PhpStorm.
 * User: abautu
 * Date: 12.02.2018
 * Time: 05:30
 */
$SOLR_URL = 'http://plagzap_solr:8983/solr/gettingstarted';
$FILE_DIR = 'files';

if (!function_exists('curl_file_create')) {
    function curl_file_create($filename, $mimetype = '', $postname = '') {
        return "@$filename;filename="
            . ($postname ?: basename($filename))
            . ($mimetype ? ";type=$mimetype" : '');
    }
}