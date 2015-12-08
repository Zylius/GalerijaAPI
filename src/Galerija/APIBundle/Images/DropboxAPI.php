<?php
/**
 * Created by PhpStorm.
 * User: Zylius
 * Date: 12/8/2015
 * Time: 23:18
 */

namespace Galerija\APIBundle\Images;


class DropboxAPI extends \Dropbox_API
{
    public function getMetaData($path, $list = true, $hash = null, $fileLimit = null, $root = null) {
        return true;
    }
}