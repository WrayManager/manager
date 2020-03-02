<?php
namespace WHMCS\Module\Addon\Wray\Models;

use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    public $table='tblproducts';

    public $timestamps = false;

    /**
     * 默认加载的关联。
     *
     * @var array
     */
    //protected $with = ['servers'];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('servertype', function (Builder $builder) {
            $builder->where('servertype', 'wray');
        });
    }

    public function servers()
    {
        return $this->belongsToMany(Server::class, 'wray_product_server', 'product_id', 'server_id');
    }

    public function users()
    {
        return $this->hasMany(User::class)->where("enabled", 1);
    }

    public function getTransferAttribute()
    {
        return $this->configoption1;
    }

    public function setTransferAttribute($val)
    {
        $this->configoption1 = $val;
    }

    public function getUnitAttribute()
    {
        return $this->configoption2;
    }

    public function setUnitAttribute($val)
    {
        $this->configoption2 = $val;
    }

    public function getCycleAttribute()
    {
        return $this->configoption3;
    }

    public function setCycleAttribute($val)
    {
        $this->configoption3 = $val;
    }

    public function getFriendlyCycleAttribute(){
        return [
            'month' => "月",
            'quarter' => "季",
            'year' => "年"
        ][$this->cycle];
    }

    public function hasServer($id){
        foreach($this->servers as $s){
            if($s->id == $id) return true;
        }
        return false;
    }
}
