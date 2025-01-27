<?php
class TCPDF_IMAGES {
    public static function getImageFileType($imgfile, $iminfo = array()) {
        return array('type' => 'png', 'width' => 0, 'height' => 0, 'channels' => 3);
    }
} 