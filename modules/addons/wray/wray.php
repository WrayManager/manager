<?php

use Illuminate\Database\Schema\Blueprint;
use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\wray\Admin\AdminDispatcher;
use WHMCS\Module\Addon\wray\Client\ClientDispatcher;
use Symfony\Component\Yaml\Yaml;

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
        'version' => '1.1',
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
                $table->double('rate')->default(1);
                $table->integer("alter_id");
                $table->string('network');
                $table->string('ws_path');
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
    $old = $vars['version'];
    if($old == "1.0"){
        Capsule::schema()->table('wray_servers', function (Blueprint $table) {
            $table->double('rate')->after("port")->default(1);
            $table->string('ws_path')->after("network")->default("");
        });
    }
}

function wray_output($vars)
{
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

    $dispatcher = new AdminDispatcher();
    $response = $dispatcher->dispatch($action, $vars);
    echo $response;
}

function wray_clientarea($vars) {
    $user = \WHMCS\Module\Addon\Wray\Models\User::where("uuid",localAPI("DecryptPassword", [
        'password2' => (string)$_GET['token'],
    ])['password'])->with([
        "product.servers" => function($query){
            $query->orderBy('name');
        }
    ])->first();

    if($user){
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            if (strpos($_SERVER['HTTP_USER_AGENT'], 'Quantumult%20X') !== false or $_GET['quanx'] == "on") {
                $uri = '';
                foreach ($user->product->servers as $item) {
                    $uri .= "vmess=" . $item->host . ":" . $item->port . ", method=none, password=" . $user->uuid . ", fast-open=false, udp-relay=false, tag=" . $item->name;
                    switch ($item->network){
                        case "tcp":
                            if($item->tls){
                                $uri .= ', obfs=over-tls';
                            }
                            break;
                        case "ws":
                            if($item->tls){
                                $uri .= ', obfs=wss';
                            }else{
                                $uri .= ', obfs=ws';
                            }
                            $uri .= ', obfs-uri='.$item->ws_path;
                            break;
                        default:
                            $uri .= " 暂不被QuanX支持的协议";
                            break;
                    }
                    $uri .= "\r\n";
                }
                echo $uri;
            } else if (strpos($_SERVER['HTTP_USER_AGENT'], 'Quantumult') !== false or $_GET['quan'] == "on") {
                $uri = '';
                header('subscription-userinfo: upload=' . $user->u . '; download=' . $user->d . ';total=' . $user->transfer_enable);
                foreach ($user->product->servers as $item) {
                    $str = '';
                    $str .= $item->name . '= vmess, ' . $item->host . ', ' . $item->port . ', chacha20-ietf-poly1305, "' . $user->v2ray_uuid . '", over-tls=' . ($item->tls ? "true" : "false") . ', certificate=0';
                    $str .= ', obfs=' . $item->network . ', obfs-uri='.$item->ws_path;
                    $uri .= "vmess://" . base64_encode($str) . "\r\n";
                }
                echo base64_encode($uri);
            } else if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'clash') !== false or $_GET['clash'] == "on") {
                $proxy = [];
                $proxyGroup = [];
                $proxies = [];
                $rules = [];
                foreach ($user->product->servers as $item) {
                    $array = [];
                    $array['name'] = $item->name;
                    $array['type'] = 'vmess';
                    $array['server'] = $item->host;
                    $array['port'] = $item->port;
                    $array['uuid'] = $user->uuid;
                    $array['alterId'] = $item->alter_id;
                    $array['cipher'] = 'auto';
                    if ($item->tls) {
                        $array['tls'] = true;
                        $array['skip-cert-verify'] = true;
                    }
                    if($item->network != "tcp"){
                        $array['network'] = $item->network;
                    }
                    $array['ws-path'] = $item->ws_path;
                    array_push($proxy, $array);
                    array_push($proxies, $item->name);
                }

                array_push($proxyGroup, [
                    'name' => 'auto',
                    'type' => 'url-test',
                    'proxies' => $proxies,
                    'url' => 'https://www.bing.com',
                    'interval' => 300
                ]);
                array_push($proxyGroup, [
                    'name' => 'fallback-auto',
                    'type' => 'fallback',
                    'proxies' => $proxies,
                    'url' => 'https://www.bing.com',
                    'interval' => 300
                ]);
                array_push($proxyGroup, [
                    'name' => 'select',
                    'type' => 'select',
                    'proxies' => $proxies
                ]);

                $config = [
                    'port' => 7890,
                    'socks-port' => 7891,
                    'allow-lan' => false,
                    'mode' => 'Rule',
                    'log-level' => 'info',
                    'external-controller' => '0.0.0.0:9090',
                    'secret' => '',
                    'proxies' => $proxy,
                    'proxy-groups' => $proxyGroup,
                    'rules' => [
                        "DOMAIN-SUFFIX,apps.apple.com,select",
                        "DOMAIN-SUFFIX,itunes.apple.com,select",
                        "DOMAIN-SUFFIX,blobstore.apple.com,select",
                        "DOMAIN-SUFFIX,music.apple.com,DIRECT",
                        "DOMAIN-SUFFIX,icloud.com,DIRECT",
                        "DOMAIN-SUFFIX,icloud-content.com,DIRECT",
                        "DOMAIN-SUFFIX,me.com,DIRECT",
                        "DOMAIN-SUFFIX,mzstatic.com,DIRECT",
                        "DOMAIN-SUFFIX,akadns.net,DIRECT",
                        "DOMAIN-SUFFIX,aaplimg.com,DIRECT",
                        "DOMAIN-SUFFIX,cdn-apple.com,DIRECT",
                        "DOMAIN-SUFFIX,apple.com,DIRECT",
                        "DOMAIN-SUFFIX,apple-cloudkit.com,DIRECT",
                        "DOMAIN-SUFFIX,cn,DIRECT",
                        "DOMAIN-KEYWORD,-cn,DIRECT",
                        "DOMAIN-SUFFIX,126.com,DIRECT",
                        "DOMAIN-SUFFIX,126.net,DIRECT",
                        "DOMAIN-SUFFIX,127.net,DIRECT",
                        "DOMAIN-SUFFIX,163.com,DIRECT",
                        "DOMAIN-SUFFIX,360buyimg.com,DIRECT",
                        "DOMAIN-SUFFIX,36kr.com,DIRECT",
                        "DOMAIN-SUFFIX,acfun.tv,DIRECT",
                        "DOMAIN-SUFFIX,air-matters.com,DIRECT",
                        "DOMAIN-SUFFIX,aixifan.com,DIRECT",
                        "DOMAIN-SUFFIX,akamaized.net,DIRECT",
                        "DOMAIN-KEYWORD,alicdn,DIRECT",
                        "DOMAIN-KEYWORD,alipay,DIRECT",
                        "DOMAIN-KEYWORD,taobao,DIRECT",
                        "DOMAIN-SUFFIX,amap.com,DIRECT",
                        "DOMAIN-SUFFIX,autonavi.com,DIRECT",
                        "DOMAIN-KEYWORD,baidu,DIRECT",
                        "DOMAIN-SUFFIX,bdimg.com,DIRECT",
                        "DOMAIN-SUFFIX,bdstatic.com,DIRECT",
                        "DOMAIN-SUFFIX,bilibili.com,DIRECT",
                        "DOMAIN-SUFFIX,caiyunapp.com,DIRECT",
                        "DOMAIN-SUFFIX,clouddn.com,DIRECT",
                        "DOMAIN-SUFFIX,cnbeta.com,DIRECT",
                        "DOMAIN-SUFFIX,cnbetacdn.com,DIRECT",
                        "DOMAIN-SUFFIX,cootekservice.com,DIRECT",
                        "DOMAIN-SUFFIX,csdn.net,DIRECT",
                        "DOMAIN-SUFFIX,ctrip.com,DIRECT",
                        "DOMAIN-SUFFIX,dgtle.com,DIRECT",
                        "DOMAIN-SUFFIX,dianping.com,DIRECT",
                        "DOMAIN-SUFFIX,douban.com,DIRECT",
                        "DOMAIN-SUFFIX,doubanio.com,DIRECT",
                        "DOMAIN-SUFFIX,duokan.com,DIRECT",
                        "DOMAIN-SUFFIX,easou.com,DIRECT",
                        "DOMAIN-SUFFIX,ele.me,DIRECT",
                        "DOMAIN-SUFFIX,feng.com,DIRECT",
                        "DOMAIN-SUFFIX,fir.im,DIRECT",
                        "DOMAIN-SUFFIX,frdic.com,DIRECT",
                        "DOMAIN-SUFFIX,g-cores.com,DIRECT",
                        "DOMAIN-SUFFIX,godic.net,DIRECT",
                        "DOMAIN-SUFFIX,gtimg.com,DIRECT",
                        "DOMAIN,cdn.hockeyapp.net,DIRECT",
                        "DOMAIN-SUFFIX,hongxiu.com,DIRECT",
                        "DOMAIN-SUFFIX,hxcdn.net,DIRECT",
                        "DOMAIN-SUFFIX,iciba.com,DIRECT",
                        "DOMAIN-SUFFIX,ifeng.com,DIRECT",
                        "DOMAIN-SUFFIX,ifengimg.com,DIRECT",
                        "DOMAIN-SUFFIX,ipip.net,DIRECT",
                        "DOMAIN-SUFFIX,iqiyi.com,DIRECT",
                        "DOMAIN-SUFFIX,jd.com,DIRECT",
                        "DOMAIN-SUFFIX,jianshu.com,DIRECT",
                        "DOMAIN-SUFFIX,knewone.com,DIRECT",
                        "DOMAIN-SUFFIX,le.com,DIRECT",
                        "DOMAIN-SUFFIX,lecloud.com,DIRECT",
                        "DOMAIN-SUFFIX,lemicp.com,DIRECT",
                        "DOMAIN-SUFFIX,licdn.com,DIRECT",
                        "DOMAIN-SUFFIX,linkedin.com,DIRECT",
                        "DOMAIN-SUFFIX,luoo.net,DIRECT",
                        "DOMAIN-SUFFIX,meituan.com,DIRECT",
                        "DOMAIN-SUFFIX,meituan.net,DIRECT",
                        "DOMAIN-SUFFIX,mi.com,DIRECT",
                        "DOMAIN-SUFFIX,miaopai.com,DIRECT",
                        "DOMAIN-SUFFIX,microsoft.com,DIRECT",
                        "DOMAIN-SUFFIX,microsoftonline.com,DIRECT",
                        "DOMAIN-SUFFIX,miui.com,DIRECT",
                        "DOMAIN-SUFFIX,miwifi.com,DIRECT",
                        "DOMAIN-SUFFIX,mob.com,DIRECT",
                        "DOMAIN-SUFFIX,netease.com,DIRECT",
                        "DOMAIN-SUFFIX,office.com,DIRECT",
                        "DOMAIN-SUFFIX,office365.com,DIRECT",
                        "DOMAIN-KEYWORD,officecdn,DIRECT",
                        "DOMAIN-SUFFIX,oschina.net,DIRECT",
                        "DOMAIN-SUFFIX,ppsimg.com,DIRECT",
                        "DOMAIN-SUFFIX,pstatp.com,DIRECT",
                        "DOMAIN-SUFFIX,qcloud.com,DIRECT",
                        "DOMAIN-SUFFIX,qdaily.com,DIRECT",
                        "DOMAIN-SUFFIX,qdmm.com,DIRECT",
                        "DOMAIN-SUFFIX,qhimg.com,DIRECT",
                        "DOMAIN-SUFFIX,qhres.com,DIRECT",
                        "DOMAIN-SUFFIX,qidian.com,DIRECT",
                        "DOMAIN-SUFFIX,qihucdn.com,DIRECT",
                        "DOMAIN-SUFFIX,qiniu.com,DIRECT",
                        "DOMAIN-SUFFIX,qiniucdn.com,DIRECT",
                        "DOMAIN-SUFFIX,qiyipic.com,DIRECT",
                        "DOMAIN-SUFFIX,qq.com,DIRECT",
                        "DOMAIN-SUFFIX,qqurl.com,DIRECT",
                        "DOMAIN-SUFFIX,rarbg.to,DIRECT",
                        "DOMAIN-SUFFIX,ruguoapp.com,DIRECT",
                        "DOMAIN-SUFFIX,segmentfault.com,DIRECT",
                        "DOMAIN-SUFFIX,sinaapp.com,DIRECT",
                        "DOMAIN-SUFFIX,smzdm.com,DIRECT",
                        "DOMAIN-SUFFIX,snapdrop.net,DIRECT",
                        "DOMAIN-SUFFIX,sogou.com,DIRECT",
                        "DOMAIN-SUFFIX,sogoucdn.com,DIRECT",
                        "DOMAIN-SUFFIX,sohu.com,DIRECT",
                        "DOMAIN-SUFFIX,soku.com,DIRECT",
                        "DOMAIN-SUFFIX,speedtest.net,DIRECT",
                        "DOMAIN-SUFFIX,sspai.com,DIRECT",
                        "DOMAIN-SUFFIX,suning.com,DIRECT",
                        "DOMAIN-SUFFIX,taobao.com,DIRECT",
                        "DOMAIN-SUFFIX,tencent.com,DIRECT",
                        "DOMAIN-SUFFIX,tenpay.com,DIRECT",
                        "DOMAIN-SUFFIX,tianyancha.com,DIRECT",
                        "DOMAIN-SUFFIX,tmall.com,DIRECT",
                        "DOMAIN-SUFFIX,tudou.com,DIRECT",
                        "DOMAIN-SUFFIX,umetrip.com,DIRECT",
                        "DOMAIN-SUFFIX,upaiyun.com,DIRECT",
                        "DOMAIN-SUFFIX,upyun.com,DIRECT",
                        "DOMAIN-SUFFIX,veryzhun.com,DIRECT",
                        "DOMAIN-SUFFIX,weather.com,DIRECT",
                        "DOMAIN-SUFFIX,weibo.com,DIRECT",
                        "DOMAIN-SUFFIX,xiami.com,DIRECT",
                        "DOMAIN-SUFFIX,xiami.net,DIRECT",
                        "DOMAIN-SUFFIX,xiaomicp.com,DIRECT",
                        "DOMAIN-SUFFIX,ximalaya.com,DIRECT",
                        "DOMAIN-SUFFIX,xmcdn.com,DIRECT",
                        "DOMAIN-SUFFIX,xunlei.com,DIRECT",
                        "DOMAIN-SUFFIX,yhd.com,DIRECT",
                        "DOMAIN-SUFFIX,yihaodianimg.com,DIRECT",
                        "DOMAIN-SUFFIX,yinxiang.com,DIRECT",
                        "DOMAIN-SUFFIX,ykimg.com,DIRECT",
                        "DOMAIN-SUFFIX,youdao.com,DIRECT",
                        "DOMAIN-SUFFIX,youku.com,DIRECT",
                        "DOMAIN-SUFFIX,zealer.com,DIRECT",
                        "DOMAIN-SUFFIX,zhihu.com,DIRECT",
                        "DOMAIN-SUFFIX,zhimg.com,DIRECT",
                        "DOMAIN-SUFFIX,zimuzu.tv,DIRECT",
                        "DOMAIN-SUFFIX,zoho.com,DIRECT",
                        "DOMAIN-KEYWORD,amazon,select",
                        "DOMAIN-KEYWORD,google,select",
                        "DOMAIN-KEYWORD,gmail,select",
                        "DOMAIN-KEYWORD,youtube,select",
                        "DOMAIN-KEYWORD,facebook,select",
                        "DOMAIN-SUFFIX,fb.me,select",
                        "DOMAIN-SUFFIX,fbcdn.net,select",
                        "DOMAIN-KEYWORD,twitter,select",
                        "DOMAIN-KEYWORD,instagram,select",
                        "DOMAIN-KEYWORD,dropbox,select",
                        "DOMAIN-SUFFIX,twimg.com,select",
                        "DOMAIN-KEYWORD,blogspot,select",
                        "DOMAIN-SUFFIX,youtu.be,select",
                        "DOMAIN-KEYWORD,whatsapp,select",
                        "DOMAIN-KEYWORD,admarvel,REJECT",
                        "DOMAIN-KEYWORD,admaster,REJECT",
                        "DOMAIN-KEYWORD,adsage,REJECT",
                        "DOMAIN-KEYWORD,adsmogo,REJECT",
                        "DOMAIN-KEYWORD,adsrvmedia,REJECT",
                        "DOMAIN-KEYWORD,adwords,REJECT",
                        "DOMAIN-KEYWORD,adservice,REJECT",
                        "DOMAIN-KEYWORD,domob,REJECT",
                        "DOMAIN-KEYWORD,duomeng,REJECT",
                        "DOMAIN-KEYWORD,dwtrack,REJECT",
                        "DOMAIN-KEYWORD,guanggao,REJECT",
                        "DOMAIN-KEYWORD,lianmeng,REJECT",
                        "DOMAIN-SUFFIX,mmstat.com,REJECT",
                        "DOMAIN-KEYWORD,omgmta,REJECT",
                        "DOMAIN-KEYWORD,openx,REJECT",
                        "DOMAIN-KEYWORD,partnerad,REJECT",
                        "DOMAIN-KEYWORD,pingfore,REJECT",
                        "DOMAIN-KEYWORD,supersonicads,REJECT",
                        "DOMAIN-KEYWORD,tracking,REJECT",
                        "DOMAIN-KEYWORD,uedas,REJECT",
                        "DOMAIN-KEYWORD,umeng,REJECT",
                        "DOMAIN-KEYWORD,usage,REJECT",
                        "DOMAIN-KEYWORD,wlmonitor,REJECT",
                        "DOMAIN-KEYWORD,zjtoolbar,REJECT",
                        "DOMAIN-SUFFIX,9to5mac.com,select",
                        "DOMAIN-SUFFIX,abpchina.org,select",
                        "DOMAIN-SUFFIX,adblockplus.org,select",
                        "DOMAIN-SUFFIX,adobe.com,select",
                        "DOMAIN-SUFFIX,alfredapp.com,select",
                        "DOMAIN-SUFFIX,amplitude.com,select",
                        "DOMAIN-SUFFIX,ampproject.org,select",
                        "DOMAIN-SUFFIX,android.com,select",
                        "DOMAIN-SUFFIX,angularjs.org,select",
                        "DOMAIN-SUFFIX,aolcdn.com,select",
                        "DOMAIN-SUFFIX,apkpure.com,select",
                        "DOMAIN-SUFFIX,appledaily.com,select",
                        "DOMAIN-SUFFIX,appshopper.com,select",
                        "DOMAIN-SUFFIX,appspot.com,select",
                        "DOMAIN-SUFFIX,arcgis.com,select",
                        "DOMAIN-SUFFIX,archive.org,select",
                        "DOMAIN-SUFFIX,armorgames.com,select",
                        "DOMAIN-SUFFIX,aspnetcdn.com,select",
                        "DOMAIN-SUFFIX,att.com,select",
                        "DOMAIN-SUFFIX,awsstatic.com,select",
                        "DOMAIN-SUFFIX,azureedge.net,select",
                        "DOMAIN-SUFFIX,azurewebsites.net,select",
                        "DOMAIN-SUFFIX,bing.com,select",
                        "DOMAIN-SUFFIX,bintray.com,select",
                        "DOMAIN-SUFFIX,bit.com,select",
                        "DOMAIN-SUFFIX,bit.ly,select",
                        "DOMAIN-SUFFIX,bitbucket.org,select",
                        "DOMAIN-SUFFIX,bjango.com,select",
                        "DOMAIN-SUFFIX,bkrtx.com,select",
                        "DOMAIN-SUFFIX,blog.com,select",
                        "DOMAIN-SUFFIX,blogcdn.com,select",
                        "DOMAIN-SUFFIX,blogger.com,select",
                        "DOMAIN-SUFFIX,blogsmithmedia.com,select",
                        "DOMAIN-SUFFIX,blogspot.com,select",
                        "DOMAIN-SUFFIX,blogspot.hk,select",
                        "DOMAIN-SUFFIX,bloomberg.com,select",
                        "DOMAIN-SUFFIX,box.com,select",
                        "DOMAIN-SUFFIX,box.net,select",
                        "DOMAIN-SUFFIX,cachefly.net,select",
                        "DOMAIN-SUFFIX,chromium.org,select",
                        "DOMAIN-SUFFIX,cl.ly,select",
                        "DOMAIN-SUFFIX,cloudflare.com,select",
                        "DOMAIN-SUFFIX,cloudfront.net,select",
                        "DOMAIN-SUFFIX,cloudmagic.com,select",
                        "DOMAIN-SUFFIX,cmail19.com,select",
                        "DOMAIN-SUFFIX,cnet.com,select",
                        "DOMAIN-SUFFIX,cocoapods.org,select",
                        "DOMAIN-SUFFIX,comodoca.com,select",
                        "DOMAIN-SUFFIX,crashlytics.com,select",
                        "DOMAIN-SUFFIX,culturedcode.com,select",
                        "DOMAIN-SUFFIX,d.pr,select",
                        "DOMAIN-SUFFIX,danilo.to,select",
                        "DOMAIN-SUFFIX,dayone.me,select",
                        "DOMAIN-SUFFIX,db.tt,select",
                        "DOMAIN-SUFFIX,deskconnect.com,select",
                        "DOMAIN-SUFFIX,disq.us,select",
                        "DOMAIN-SUFFIX,disqus.com,select",
                        "DOMAIN-SUFFIX,disquscdn.com,select",
                        "DOMAIN-SUFFIX,dnsimple.com,select",
                        "DOMAIN-SUFFIX,docker.com,select",
                        "DOMAIN-SUFFIX,dribbble.com,select",
                        "DOMAIN-SUFFIX,droplr.com,select",
                        "DOMAIN-SUFFIX,duckduckgo.com,select",
                        "DOMAIN-SUFFIX,dueapp.com,select",
                        "DOMAIN-SUFFIX,dytt8.net,select",
                        "DOMAIN-SUFFIX,edgecastcdn.net,select",
                        "DOMAIN-SUFFIX,edgekey.net,select",
                        "DOMAIN-SUFFIX,edgesuite.net,select",
                        "DOMAIN-SUFFIX,engadget.com,select",
                        "DOMAIN-SUFFIX,entrust.net,select",
                        "DOMAIN-SUFFIX,eurekavpt.com,select",
                        "DOMAIN-SUFFIX,evernote.com,select",
                        "DOMAIN-SUFFIX,fabric.io,select",
                        "DOMAIN-SUFFIX,fast.com,select",
                        "DOMAIN-SUFFIX,fastly.net,select",
                        "DOMAIN-SUFFIX,fc2.com,select",
                        "DOMAIN-SUFFIX,feedburner.com,select",
                        "DOMAIN-SUFFIX,feedly.com,select",
                        "DOMAIN-SUFFIX,feedsportal.com,select",
                        "DOMAIN-SUFFIX,fiftythree.com,select",
                        "DOMAIN-SUFFIX,firebaseio.com,select",
                        "DOMAIN-SUFFIX,flexibits.com,select",
                        "DOMAIN-SUFFIX,flickr.com,select",
                        "DOMAIN-SUFFIX,flipboard.com,select",
                        "DOMAIN-SUFFIX,g.co,select",
                        "DOMAIN-SUFFIX,gabia.net,select",
                        "DOMAIN-SUFFIX,geni.us,select",
                        "DOMAIN-SUFFIX,gfx.ms,select",
                        "DOMAIN-SUFFIX,ggpht.com,select",
                        "DOMAIN-SUFFIX,ghostnoteapp.com,select",
                        "DOMAIN-SUFFIX,git.io,select",
                        "DOMAIN-KEYWORD,github,select",
                        "DOMAIN-SUFFIX,globalsign.com,select",
                        "DOMAIN-SUFFIX,gmodules.com,select",
                        "DOMAIN-SUFFIX,godaddy.com,select",
                        "DOMAIN-SUFFIX,golang.org,select",
                        "DOMAIN-SUFFIX,gongm.in,select",
                        "DOMAIN-SUFFIX,goo.gl,select",
                        "DOMAIN-SUFFIX,goodreaders.com,select",
                        "DOMAIN-SUFFIX,goodreads.com,select",
                        "DOMAIN-SUFFIX,gravatar.com,select",
                        "DOMAIN-SUFFIX,gstatic.com,select",
                        "DOMAIN-SUFFIX,gvt0.com,select",
                        "DOMAIN-SUFFIX,hockeyapp.net,select",
                        "DOMAIN-SUFFIX,hotmail.com,select",
                        "DOMAIN-SUFFIX,icons8.com,select",
                        "DOMAIN-SUFFIX,ifixit.com,select",
                        "DOMAIN-SUFFIX,ift.tt,select",
                        "DOMAIN-SUFFIX,ifttt.com,select",
                        "DOMAIN-SUFFIX,iherb.com,select",
                        "DOMAIN-SUFFIX,imageshack.us,select",
                        "DOMAIN-SUFFIX,img.ly,select",
                        "DOMAIN-SUFFIX,imgur.com,select",
                        "DOMAIN-SUFFIX,imore.com,select",
                        "DOMAIN-SUFFIX,instapaper.com,select",
                        "DOMAIN-SUFFIX,ipn.li,select",
                        "DOMAIN-SUFFIX,is.gd,select",
                        "DOMAIN-SUFFIX,issuu.com,select",
                        "DOMAIN-SUFFIX,itgonglun.com,select",
                        "DOMAIN-SUFFIX,itun.es,select",
                        "DOMAIN-SUFFIX,ixquick.com,select",
                        "DOMAIN-SUFFIX,j.mp,select",
                        "DOMAIN-SUFFIX,js.revsci.net,select",
                        "DOMAIN-SUFFIX,jshint.com,select",
                        "DOMAIN-SUFFIX,jtvnw.net,select",
                        "DOMAIN-SUFFIX,justgetflux.com,select",
                        "DOMAIN-SUFFIX,kat.cr,select",
                        "DOMAIN-SUFFIX,klip.me,select",
                        "DOMAIN-SUFFIX,libsyn.com,select",
                        "DOMAIN-SUFFIX,linode.com,select",
                        "DOMAIN-SUFFIX,lithium.com,select",
                        "DOMAIN-SUFFIX,littlehj.com,select",
                        "DOMAIN-SUFFIX,live.com,select",
                        "DOMAIN-SUFFIX,live.net,select",
                        "DOMAIN-SUFFIX,livefilestore.com,select",
                        "DOMAIN-SUFFIX,llnwd.net,select",
                        "DOMAIN-SUFFIX,macid.co,select",
                        "DOMAIN-SUFFIX,macromedia.com,select",
                        "DOMAIN-SUFFIX,macrumors.com,select",
                        "DOMAIN-SUFFIX,mashable.com,select",
                        "DOMAIN-SUFFIX,mathjax.org,select",
                        "DOMAIN-SUFFIX,medium.com,select",
                        "DOMAIN-SUFFIX,mega.co.nz,select",
                        "DOMAIN-SUFFIX,mega.nz,select",
                        "DOMAIN-SUFFIX,megaupload.com,select",
                        "DOMAIN-SUFFIX,microsofttranslator.com,select",
                        "DOMAIN-SUFFIX,mindnode.com,select",
                        "DOMAIN-SUFFIX,mobile01.com,select",
                        "DOMAIN-SUFFIX,modmyi.com,select",
                        "DOMAIN-SUFFIX,msedge.net,select",
                        "DOMAIN-SUFFIX,myfontastic.com,select",
                        "DOMAIN-SUFFIX,name.com,select",
                        "DOMAIN-SUFFIX,nextmedia.com,select",
                        "DOMAIN-SUFFIX,nsstatic.net,select",
                        "DOMAIN-SUFFIX,nssurge.com,select",
                        "DOMAIN-SUFFIX,nyt.com,select",
                        "DOMAIN-SUFFIX,nytimes.com,select",
                        "DOMAIN-SUFFIX,omnigroup.com,select",
                        "DOMAIN-SUFFIX,onedrive.com,select",
                        "DOMAIN-SUFFIX,onenote.com,select",
                        "DOMAIN-SUFFIX,ooyala.com,select",
                        "DOMAIN-SUFFIX,openvpn.net,select",
                        "DOMAIN-SUFFIX,openwrt.org,select",
                        "DOMAIN-SUFFIX,orkut.com,select",
                        "DOMAIN-SUFFIX,osxdaily.com,select",
                        "DOMAIN-SUFFIX,outlook.com,select",
                        "DOMAIN-SUFFIX,ow.ly,select",
                        "DOMAIN-SUFFIX,paddleapi.com,select",
                        "DOMAIN-SUFFIX,parallels.com,select",
                        "DOMAIN-SUFFIX,parse.com,select",
                        "DOMAIN-SUFFIX,pdfexpert.com,select",
                        "DOMAIN-SUFFIX,periscope.tv,select",
                        "DOMAIN-SUFFIX,pinboard.in,select",
                        "DOMAIN-SUFFIX,pinterest.com,select",
                        "DOMAIN-SUFFIX,pixelmator.com,select",
                        "DOMAIN-SUFFIX,pixiv.net,select",
                        "DOMAIN-SUFFIX,playpcesor.com,select",
                        "DOMAIN-SUFFIX,playstation.com,select",
                        "DOMAIN-SUFFIX,playstation.com.hk,select",
                        "DOMAIN-SUFFIX,playstation.net,select",
                        "DOMAIN-SUFFIX,playstationnetwork.com,select",
                        "DOMAIN-SUFFIX,pushwoosh.com,select",
                        "DOMAIN-SUFFIX,rime.im,select",
                        "DOMAIN-SUFFIX,servebom.com,select",
                        "DOMAIN-SUFFIX,sfx.ms,select",
                        "DOMAIN-SUFFIX,shadowsocks.org,select",
                        "DOMAIN-SUFFIX,sharethis.com,select",
                        "DOMAIN-SUFFIX,shazam.com,select",
                        "DOMAIN-SUFFIX,skype.com,select",
                        "DOMAIN-SUFFIX,smartdnsselect.com,select",
                        "DOMAIN-SUFFIX,smartmailcloud.com,select",
                        "DOMAIN-SUFFIX,sndcdn.com,select",
                        "DOMAIN-SUFFIX,sony.com,select",
                        "DOMAIN-SUFFIX,soundcloud.com,select",
                        "DOMAIN-SUFFIX,sourceforge.net,select",
                        "DOMAIN-SUFFIX,spotify.com,select",
                        "DOMAIN-SUFFIX,squarespace.com,select",
                        "DOMAIN-SUFFIX,sstatic.net,select",
                        "DOMAIN-SUFFIX,st.luluku.pw,select",
                        "DOMAIN-SUFFIX,stackoverflow.com,select",
                        "DOMAIN-SUFFIX,startpage.com,select",
                        "DOMAIN-SUFFIX,staticflickr.com,select",
                        "DOMAIN-SUFFIX,steamcommunity.com,select",
                        "DOMAIN-SUFFIX,symauth.com,select",
                        "DOMAIN-SUFFIX,symcb.com,select",
                        "DOMAIN-SUFFIX,symcd.com,select",
                        "DOMAIN-SUFFIX,tapbots.com,select",
                        "DOMAIN-SUFFIX,tapbots.net,select",
                        "DOMAIN-SUFFIX,tdesktop.com,select",
                        "DOMAIN-SUFFIX,techcrunch.com,select",
                        "DOMAIN-SUFFIX,techsmith.com,select",
                        "DOMAIN-SUFFIX,thepiratebay.org,select",
                        "DOMAIN-SUFFIX,theverge.com,select",
                        "DOMAIN-SUFFIX,time.com,select",
                        "DOMAIN-SUFFIX,timeinc.net,select",
                        "DOMAIN-SUFFIX,tiny.cc,select",
                        "DOMAIN-SUFFIX,tinypic.com,select",
                        "DOMAIN-SUFFIX,tmblr.co,select",
                        "DOMAIN-SUFFIX,todoist.com,select",
                        "DOMAIN-SUFFIX,trello.com,select",
                        "DOMAIN-SUFFIX,trustasiassl.com,select",
                        "DOMAIN-SUFFIX,tumblr.co,select",
                        "DOMAIN-SUFFIX,tumblr.com,select",
                        "DOMAIN-SUFFIX,tweetdeck.com,select",
                        "DOMAIN-SUFFIX,tweetmarker.net,select",
                        "DOMAIN-SUFFIX,twitch.tv,select",
                        "DOMAIN-SUFFIX,txmblr.com,select",
                        "DOMAIN-SUFFIX,typekit.net,select",
                        "DOMAIN-SUFFIX,ubertags.com,select",
                        "DOMAIN-SUFFIX,ublock.org,select",
                        "DOMAIN-SUFFIX,ubnt.com,select",
                        "DOMAIN-SUFFIX,ulyssesapp.com,select",
                        "DOMAIN-SUFFIX,urchin.com,select",
                        "DOMAIN-SUFFIX,usertrust.com,select",
                        "DOMAIN-SUFFIX,v.gd,select",
                        "DOMAIN-SUFFIX,v2ex.com,select",
                        "DOMAIN-SUFFIX,vimeo.com,select",
                        "DOMAIN-SUFFIX,vimeocdn.com,select",
                        "DOMAIN-SUFFIX,vine.co,select",
                        "DOMAIN-SUFFIX,vivaldi.com,select",
                        "DOMAIN-SUFFIX,vox-cdn.com,select",
                        "DOMAIN-SUFFIX,vsco.co,select",
                        "DOMAIN-SUFFIX,vultr.com,select",
                        "DOMAIN-SUFFIX,w.org,select",
                        "DOMAIN-SUFFIX,w3schools.com,select",
                        "DOMAIN-SUFFIX,webtype.com,select",
                        "DOMAIN-SUFFIX,wikiwand.com,select",
                        "DOMAIN-SUFFIX,wikileaks.org,select",
                        "DOMAIN-SUFFIX,wikimedia.org,select",
                        "DOMAIN-SUFFIX,wikipedia.com,select",
                        "DOMAIN-SUFFIX,wikipedia.org,select",
                        "DOMAIN-SUFFIX,windows.com,select",
                        "DOMAIN-SUFFIX,windows.net,select",
                        "DOMAIN-SUFFIX,wire.com,select",
                        "DOMAIN-SUFFIX,wordpress.com,select",
                        "DOMAIN-SUFFIX,workflowy.com,select",
                        "DOMAIN-SUFFIX,wp.com,select",
                        "DOMAIN-SUFFIX,wsj.com,select",
                        "DOMAIN-SUFFIX,wsj.net,select",
                        "DOMAIN-SUFFIX,xda-developers.com,select",
                        "DOMAIN-SUFFIX,xeeno.com,select",
                        "DOMAIN-SUFFIX,xiti.com,select",
                        "DOMAIN-SUFFIX,yahoo.com,select",
                        "DOMAIN-SUFFIX,yimg.com,select",
                        "DOMAIN-SUFFIX,ying.com,select",
                        "DOMAIN-SUFFIX,yoyo.org,select",
                        "DOMAIN-SUFFIX,ytimg.com,select",
                        "DOMAIN-SUFFIX,telegra.ph,select",
                        "DOMAIN-SUFFIX,telegram.org,select",
                        "IP-CIDR,91.108.56.0/22,select",
                        "IP-CIDR,91.108.4.0/22,select",
                        "IP-CIDR,91.108.8.0/22,select",
                        "IP-CIDR,109.239.140.0/24,select",
                        "IP-CIDR,149.154.160.0/20,select",
                        "IP-CIDR,149.154.164.0/22,select",
                        "DOMAIN-SUFFIX,local,DIRECT",
                        "IP-CIDR,127.0.0.0/8,DIRECT",
                        "IP-CIDR,172.16.0.0/12,DIRECT",
                        "IP-CIDR,192.168.0.0/16,DIRECT",
                        "IP-CIDR,10.0.0.0/8,DIRECT",
                        "IP-CIDR,17.0.0.0/8,DIRECT",
                        "IP-CIDR,100.64.0.0/10,DIRECT",
                        "GEOIP,CN,DIRECT",
                        "MATCH,select",
                    ]
                ];

                echo Yaml::dump($config);
            } else if (strpos($_SERVER['HTTP_USER_AGENT'], 'Surge') !== false) {
                $ret=file_get_contents(__DIR__."/surge.conf");

                $proxies = "";
                $proxyGroup = "";
                foreach($user->product->servers as $s){
                    if($s->network == 'ws' or $s->network == 'tcp'){
                        // [Proxy]
                        $proxies .= $s->name . "=vmess, " . $s->host . ", " . $s->port . ", username=" . $user->uuid . ", tls=" . ($s->tls ? "true" : "false");
                        // Proxy Websocket Settings
                        if ($s->network=='ws') {
                            $proxies .= ", ws=ture, ws-path={$s->ws_path}";
                        }
                        $proxies .= PHP_EOL;
                        // [Proxy Group]
                        $proxyGroup .= $s->name . ", ";
                    }
                }

                $ret = str_replace("{proxies}",$proxies,$ret);
                $ret = str_replace("{proxy_group}",rtrim($proxyGroup, ", "),$ret);
                $ret = str_replace("{subs_link}",$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'],$ret);
                echo $ret;
            } else {
                $ret = "";
                foreach($user->product->servers as $s){
                    $ret.=$s->toVmess($user->uuid)."\r\n";
                }
                echo base64_encode($ret);
            }
        }else{
            $ret = "";
            foreach($user->product->servers as $s){
                $ret.=$s->toVmess($user->uuid)."\r\n";
            }
            echo base64_encode($ret);
        }
    }else{
        echo "Access Denied!";
    }
    die();
}