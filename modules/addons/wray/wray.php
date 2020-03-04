<?php

use Illuminate\Database\Schema\Blueprint;
use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\wray\Admin\AdminDispatcher;
use WHMCS\Module\Addon\wray\Client\ClientDispatcher;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function wray_config()
{

    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
    $def =  $http_type . $_SERVER['HTTP_HOST'];
    return [
        // Display name for your module
        'name' => 'Wray manager',
        // Description displayed within the admin interface
        'description' => 'V2ray selling manager.',
        // Module author name
        'author' => 'GoriGorgeSency',
        // Default language
        'language' => 'english',
        // Version number
        'version' => '1.0',
        'premium' => true,
        'fields' => [
            'domain' => [
                'FriendlyName' => '域名',
                'Type' => 'text',
                'Size' => '1000',
                'Default' => $def,
                'Description' => '节点访问本机的域名',
            ],
            'license' => [
                'FriendlyName' => 'License',
                'Type' => 'text',
                'Size' => '1000',
                'Default' => '',
                'Description' => 'License. 试用可留空.',
            ],
        ]
    ];
}

function wray_activate()
{
    // Create custom tables and schema required by your module
    try {
        if (!Capsule::hasTable('wray_servers')) {
            Capsule::schema()->create('wray_servers', function ($table) {
                $table->increments('id');
                $table->string('name');
                $table->string('host');
                $table->string('tags');
                $table->integer('port');
                $table->integer("alter_id");
                $table->string('network');
                $table->boolean('tls');
                $table->bigInteger("mem_total");
                $table->bigInteger("mem_available");
                $table->string("load");
                $table->timestamps();
            });
        }
        if (!Capsule::hasTable('wray_product_server')) {
            Capsule::schema()->create("wray_product_server", function ($table) {
                $table->increments('id');
                $table->integer('server_id');
                $table->integer('product_id');
            });
        }
        if (!Capsule::hasTable('wray_users')) {
            Capsule::schema()->create("wray_users", function ($table) {
                $table->increments('id');
                $table->uuid('uuid');
                $table->bigInteger('service_id');
                $table->bigInteger('product_id');
                $table->bigInteger('transfer');
                $table->bigInteger('upload');
                $table->bigInteger('download');
                $table->boolean('enabled');
                $table->timestamp('will_reset_on')->nullable()->default(null);
                $table->timestamps();
            });
        }
        if (!Capsule::hasTable('wray_stats')) {
            Capsule::schema()->create("wray_stats", function ($table) {
                $table->increments('id');
                $table->integer("for_id");
                $table->string("for_type");
                $table->bigInteger("upload");
                $table->bigInteger("download");
                $table->timestamps();
            });
        }
        return [
            // Supported values here include: success, error or info
            'status' => 'success',
            'description' => 'Wray插件激活成功!',
        ];
    } catch (\Exception $e) {
        if(strpos($e->getMessage(),"SQLSTATE[42S01]") !== false){
            return [
                'status' => 'success',
                'description' => 'Wray插件激活成功!',
            ];
        }
        return [
            // Supported values here include: success, error or info
            'status' => "error",
            'description' => 'Wray插件激活失败: ' . $e->getMessage(),
        ];
    }
}

function wray_deactivate()
{
    // Undo any database and schema modifications made by your module here
    try {
        return [
            // Supported values here include: success, error or info
            'status' => 'success',
            'description' => 'Wray 关闭成功',
        ];
    } catch (\Exception $e) {
        return [
            // Supported values here include: success, error or info
            "status" => "error",
            "description" => "无法删除相关数据库! {$e->getMessage()}",
        ];
    }
}

function wray_upgrade($vars)
{
    $currentlyInstalledVersion = $vars['version'];
}

function wray_output($vars)
{
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

    $dispatcher = new AdminDispatcher();
    $response = $dispatcher->dispatch($action, $vars);
    echo $response;
}
