<?php


namespace WHMCS\Module\Addon\Wray\Models;

use Carbon\Carbon;
use WHMCS\Module\Addon\Wray\Tools;

class User extends Model
{
    protected $table="wray_users";

    protected $dates = [
        'will_reset_on'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stat()
    {
        return $this->morphMany(Stat::class, 'for');
    }

    public function getFriendlyTransferAttribute(){
        return Tools::bytesToStr($this->transfer);
    }

    public function getFriendlyDownloadAttribute(){
        return Tools::bytesToStr($this->download);
    }

    public function getFriendlyUploadAttribute(){
        return Tools::bytesToStr($this->upload);
    }

    public function getUsedAttribute(){
        return $this->upload + $this->download;
    }

    public function getRatioAttribute(){
        return (int)($this->used / $this->transfer * 100);
    }

    public function getFriendlyUsedAttribute(){
        return Tools::bytesToStr($this->used);
    }

    public function getFriendlyEnabledAttribute(){
        $status = $this->enabled ? "#5cb85c" : "#d9534f";
        $text = $this->enabled ? "是" : "否";
        return <<<HTML
<span class="badge" style="background-color: $status">{$text}</span>
HTML;
    }
}
