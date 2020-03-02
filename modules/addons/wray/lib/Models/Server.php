<?php
namespace WHMCS\Module\Addon\Wray\Models;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\Wray\Tools;

class Server extends Model
{
    protected $casts = [
        'tls' => 'boolean',
    ];

    public $table='wray_servers';

    public function products()
    {
        return $this->belongsToMany(Product::class, 'wray_product_server', 'server_id', 'product_id');
    }

    public function stat()
    {
        return $this->morphMany(Stat::class, 'for');
    }

    public function getTagsAttribute()
    {
        return explode(',', $this->attributes['tags']);
    }

    public function setTagsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['tags'] = implode(",", $value);
        } else {
            $this->attributes['tags'] = $value;
        }
    }

    public static function withUserCount()
    {
        $sub = User::query()->selectRaw("count(*)")->whereIn('product_id', function (Builder $query) {
            $query->select("tblproducts.id")->from("tblproducts")->join("wray_product_server", 'tblproducts.id', '=', 'wray_product_server.product_id')->whereRaw("wray_product_server.server_id = wray_servers.id");
        });
        return static::query()->select("*", Capsule::raw("({$sub->toSql()}) as user_count"));
    }

    public function getFriendlyMemAttribute(){
        return Tools::bytesToStr($this->mem_available) . " / " . Tools::bytesToStr($this->mem_total);
    }

    public function getFriendlyLoadAttribute(){
        $loads = $this->load;
        $loads = explode(" ",$loads);
        $ret = "";
        foreach($loads as $load){
            if((float)$load < 0.8) {
                $ret.="<span class=\"badge\" style='background-color: #5cb85c;'>{$load}</span> ";
            }else if((float)$load < 1){
                $ret.="<span class=\"badge\" style='background-color: #f0ad4e;'>{$load}</span> ";
            } else {
                $ret.="<span class=\"badge\" style='background-color: #d9534f;'>{$load}</span> ";
            }
        }
        return $ret;
    }

    public function getFriendlyLoadUserAttribute(){
        $loads = $this->load;
        $loads = explode(" ",$loads);
        $load = $loads[0];
        if((float)$load < 0.8) {
            $ret="<span class=\"badge\" style='background-color: #5cb85c;'>低</span> ";
        }else if((float)$load < 1){
            $ret="<span class=\"badge\" style='background-color: #f0ad4e;'>中</span> ";
        } else {
            $ret="<span class=\"badge\" style='background-color: #d9534f;'>高</span> ";
        }
        return $ret;
    }

    public function toVmess($uuid){
        $config = [
            "v" => "2",
            "ps" => (string)$this->name,
            "add" => (string)$this->host,
            "port" => (string)$this->port,
            "id" => (string)$uuid,
            "aid" => "2",
            "net" => $this->network,
            "type" => "none",
            "host" => "",
            "path" => "",
            "tls" => $this->tls ? "tls" : ""
        ];
        return "vmess://" . base64_encode(json_encode($config));
    }
}
