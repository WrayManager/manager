<?php


namespace WHMCS\Module\Addon\Wray\Models;


class Stat extends Model
{
    public $table='wray_stats';

    public function for(){
        return $this->morphTo();
    }
}