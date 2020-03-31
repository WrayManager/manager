<?php

namespace WHMCS\Module\Addon\Wray\Admin;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use WHMCS\Module\Addon\Wray\Models\Product;
use WHMCS\Module\Addon\Wray\Models\Server;
use WHMCS\Module\Addon\Wray\Models\User;
use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\Wray\Tools;

/**
 * Sample Admin Area Controller
 */
class Controller
{
    protected $smarty;

    public function index($vars)
    {
        global $whmcs;
        $sub = Capsule::table("tblhosting")->selectRaw("count(*)")->whereRaw("packageid = tblproducts.id");
        $servers = Server::withUserCount()->orderBy('name')->get();
        $demo = new Server();
        $demo->host="请更改这里的地址";
        $demo->network = 'tcp';
        $demo->alter_id = 2;
        $demo->tls = false;
        $demo->port = 443;
        $demo->name = "测试服务器";
        foreach ($servers as $server){
            $server->token = localAPI("EncryptPassword", [
                'password2' => (string)$server->id,
            ])['password'];
        }
        return $this->view('manage.tpl', [
            'servers' => $servers,
            'products' => Product::withCount('users')->addSelect(Capsule::raw("({$sub->toSql()}) as all_users_count"))->get(),
            'users' => User::all(),
            'host' => $vars['domain'],
            'demoserver' => $demo
        ]);

    }
    
    public function add_server($vars)
    {
        return $this->view('add_server.tpl');
    }

    public function add_server_submit($vars)
    {
        $server = new Server();
        $server->name = $this->input('name', "示例服务器-1");
        $server->host = $this->input('host', "123.123.123.123");
        $server->port = $this->input('port', "443");
        $server->alter_id = $this->input('alter_id', "2");
        $server->rate = $this->input('rate', 1);
        $server->network = $this->input('network', "tcp");
        $server->ws_path = $this->input('ws_path', "/");
        $server->tags = $this->input('tags', "");
        $server->tls = $this->input('tls', false);
        if ($server->tls == 'on') {
            $server->tls = true;
        } elseif ($server->tls == 'off') {
            $server->tls = false;
        }
        $server->save();
        if ($this->input("add_to_all") == "on") {
            foreach (Product::all() as $p) {
                $p->servers()->attach($server->id);
            }
        }
        if ($this->input("continue") == 'on') {
            return $this->view('add_server.tpl', [
                'status' => 'success',
                'message' => "服务器添加成功",
            ]);
        } else {
            $this->redirect("?module=wray&status=success&message=服务器添加成功");
        }
    }

    public function del_server($vars)
    {
        return $this->view('del_server.tpl', [
            'server' => Server::find($this->input("server_id"))
        ]);
    }

    public function del_server_confirm($vars)
    {
        $server = Server::find($this->input("server_id"));
        if ($server) {
            $server->delete();
            $this->redirect("?module=wray&status=success&message=服务器删除成功!");
        } else {
            $this->redirect("?module=wray&status=danger&message=服务器删除失败, 服务器不存在");
        }
    }

    public function edit_server($vars)
    {

        $sub2 = "(select sum(download+upload) from wray_stats where for_id = `wray_servers`.`id` and for_type = \"WHMCS\\\\Module\\\\Addon\\\\Wray\\\\Models\\\\Server\" and created_at > \"{date}\") as transfer";
        $date = Carbon::now();
        $date->addMonths(-1);
        $sub2 = str_replace("{date}",$date->toDateTimeString(),$sub2);

        $server = Server::select("*")->addSelect(Capsule::raw($sub2))->find($this->input("server_id"));
        if ($server) {
            return $this->view('edit_server.tpl', [
                'server' => $server,
            ]);
        } else {
            return $this->redirect("?module=wray&status=danger&message=服务器不存在!");
        }
    }

    public function edit_server_submit($vars){
        $server = Server::find($this->input("server_id"));
        if ($server) {
            $server->name = $this->input('name', "示例服务器-1");
            $server->host = $this->input('host', "123.123.123.123");
            $server->port = $this->input('port', "443");
            $server->alter_id = $this->input('alter_id', "2");
            $server->rate = $this->input('rate', 1);
            $server->network = $this->input('network', "tcp");
            $server->ws_path = $this->input('ws_path', "/");
            $server->tags = $this->input('tags', "");
            $server->tls = $this->input('tls', false);
            if ($server->tls == 'on') {
                $server->tls = true;
            } elseif ($server->tls == 'off') {
                $server->tls = false;
            }
            $server->save();
            $this->redirect("?module=wray&status=success&message=服务器编辑成功");
        } else {
            return $this->redirect("?module=wray&status=danger&message=服务器不存在!");
        }
    }

    public function edit_product($vars){
        $product = Product::find($this->input("product_id"));
        if($product){
            return $this->view('edit_product.tpl', [
                'product' => $product,
                'servers' => Server::all(),
            ]);
        }else{
            return $this->redirect("?module=wray&status=danger&message=产品不存在!");
        }
    }

    public function edit_product_submit($vars){
        $product = Product::find($this->input("product_id"));
        if($product){
            $product->name = $this->input('name');
            $product->transfer = $this->input('transfer');
            $product->unit = $this->input('unit');
            $product->cycle = $this->input('cycle');
            $product->save();

            $servers = [];
            foreach($this->input("servers") as $key => $s){
                if($s == 'on') $servers[]=$key;
            }

            $product->servers()->sync($servers);

            $this->redirect("?module=wray&status=success&message=产品编辑成功");
        }else{
            return $this->redirect("?module=wray&status=danger&message=产品不存在!");
        }
    }

    public function __construct()
    {
        global $whmcs;
        $smarty = new \Smarty();
        $smarty->setCompileDir($whmcs->getTemplatesCacheDir());
        $smarty->setTemplateDir($whmcs->getTemplatesCacheDir() . "/../modules/addons/wray/templates");
        $smarty->registerPlugin("modifier","flowautoshow", [Tools::class, "bytesToStr"]);
        $this->smarty = $smarty;
    }

    public function view($filename, $vars = [])
    {
        $this->smarty->assign($vars);
        if ($this->input("status")) {
            $this->smarty->assign([
                'status' => $this->input("status"),
                'message' => $this->input("message"),
            ]);
        }
        ob_start();
        $this->smarty->display($filename);
        return ob_get_clean();
    }

    public function input($key, $default=null)
    {
        global $whmcs;
        return $whmcs->get_req_var($key) == "" ? $default : $whmcs->get_req_var($key);
    }

    public function redirect($to)
    {
        header("Location: $to");
        return "";
    }
}
