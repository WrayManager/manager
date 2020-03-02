<?php
namespace WHMCS\Module\Addon\Wray;

class Tools
{
    public static function bytesToStr($bytes){
        $suffixes = ['Bytes', 'KB', 'MB', 'GB','TB','PB'];
        $index = 0;
        while($bytes > 1024 && $index < 5){
            $bytes /= 1024;
            $index+=1;
        }
        return round($bytes,1)." ".$suffixes[$index];
    }
}
