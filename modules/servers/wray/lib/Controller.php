<?php

namespace WHMCS\Module\Server\Wray;

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

    public function index($vars){
        $user = User::where("service_id",$vars['serviceid'])->with("product.servers")->first();
        return [
            'tabOverviewReplacementTemplate' => 'overview',
            'templateVariables' => [
                'user' => $user,
                'nextDueDay' => $vars['model']->nextDueDate
            ]
        ];
    }
}
