<?php
class TCPDF_STATIC {
    public static function unichr($c) { return chr($c); }
    public static function substr($str, $start, $length=null) { return substr($str, $start, $length); }
    public static function strlen($str) { return strlen($str); }
    public static function fileGetContents($file) { return file_get_contents($file); }
    public static function isValidURL($url) { return (bool)filter_var($url, FILTER_VALIDATE_URL); }
    public static function file_exists($file) { return @file_exists($file); }
} 