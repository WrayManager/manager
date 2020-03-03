{include "message.tpl"}
<div>
    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="active">
            <a href="#servers" aria-controls="servers" role="tab" data-toggle="tab">服务器管理</a>
        </li>
        <li role="presentation">
            <a href="#products" aria-controls="products" role="tab" data-toggle="tab">产品管理</a>
        </li>
        <li role="presentation">
            <a href="#users" aria-controls="users" role="tab" data-toggle="tab">用户管理</a>
        </li>
    </ul>
    <div class="tab-content">
        <div role="tabpanel" class="tab-pane active" id="servers">
            <div class="panel panel-default">
                <div class="panel-heading" style="display: flex; align-items: center">
                    <h3 class="panel-title">服务器管理</h3>
                    <div style="margin-left: auto;">
                        <div class="btn-group">
                            <button class="btn btn-success btn" data-clipboard-text="curl -o install.sh https://raw.githubusercontent.com/WrayManager/manager/master/install.sh && bash ./install.sh --host {$host} --token demo"> 复制测试服务器安装脚本 </button>
                            <button type="button" class="btn btn-info autohides qrcode" data-qrname="V2ray" data-qrcode="{$demoserver->toVmess("00000000-0000-0000-0000-000000000000")}" data-client="移动端" title="V2ray 二维码" name="qrcode">
                                <span class="fa fa-qrcode" aria-hidden="true"></span>
                            </button>
                        </div>
                        <a href="?module=wray&action=add_server" class="btn btn-success btn-sm">添加服务器</a>
                    </div>
                </div>
                <div class="panel-body" style="overflow: auto">
                    <table class="table table-hover" style="white-space: nowrap;">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>名称</th>
                            <th>地址</th>
                            <th>标签</th>
                            <th>端口</th>
                            <th>AlterID</th>
                            <th>网络设置</th>
                            <th>TLS</th>
                            <th>用户数</th>
                            <th>内存</th>
                            <th>负载</th>
                            <th>最后上线于</th>
                            <th style="width: 180px;">
                                操作
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        {if $servers->count() > 0}
                            {foreach $servers as $server}
                                <tr>
                                    <th>{$server->id}</th>
                                    <th>{$server->name}</th>
                                    <th>{$server->host}</th>
                                    <th>
                                        {foreach $server->tags as $tag}
                                            <span class="badge badge-success">{$tag}</span>
                                        {/foreach}
                                    </th>
                                    <th>{$server->port}</th>
                                    <th>{$server->alter_id}</th>
                                    <th>{$server->network}</th>
                                    <th>{if $server->tls} ✓ {else} X {/if}</th>
                                    <th>{$server->user_count}</th>
                                    <th>{$server->friendly_mem}</th>
                                    <th>{$server->friendly_load}</th>
                                    <th>{$server->updated_at->toDateTimeString()}</th>
                                    <th style="width: 180px;">
                                        <div class="btn-group" style="display: flex">
                                            <button class="btn btn-success btn-xs" data-clipboard-text="curl -o install.sh https://raw.githubusercontent.com/WrayManager/manager/master/install.sh && bash ./install.sh --host {$host} --token {$server->token}"> 复制一键安装脚本 </button>
                                            <a class="btn btn-success btn-xs" href="?module=wray&action=edit_server&server_id={$server->id}">编辑</a>
                                            <a class="btn btn-xs btn-danger" href="?module=wray&action=del_server&server_id={$server->id}">删除</a>
                                        </div>
                                    </th>
                                </tr>
                            {/foreach}
                        {else}
                            <tr style="height:100px;">
                                <th colspan="13" class="text-center" style="vertical-align: middle;">
                                    <a href="?module=wray&action=add_server">
                                        当前没有服务器, 点我添加.
                                    </a>
                                </th>
                            </tr>
                        {/if}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="products">
            <div class="panel panel-default">
                <div class="panel-heading" style="display: flex; align-items: center">
                    <h3 class="panel-title">产品管理</h3>
                    <div style="margin-left: auto;">
                        <a href="configproducts.php?action=create" class="btn btn-success btn-sm">添加产品</a>
                    </div>
                </div>
                <div class="panel-body">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>名称</th>
                            <th>流量</th>
                            <th>周期</th>
                            <th>活动的用户数量</th>
                            <th>总用户数量</th>
                            <th>
                                操作
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        {if $products->count() > 0}
                            {foreach $products as $product}
                                <tr>
                                    <th>{$product->id}</th>
                                    <th>{$product->name}</th>
                                    <th>{$product->transfer} {$product->unit}</th>
                                    <th>{$product->friendly_cycle}</th>
                                    <th>{$product->users_count}</th>
                                    <th>{$product->all_users_count}</th>
                                    <th>
                                        <div class="btn-group">
                                            <a class="btn btn-success btn-xs" href="configproducts.php?action=edit&id={$product->id}">WHMCS产品页</a>
                                            <a class="btn btn-success btn-xs" href="?module=wray&action=edit_product&product_id={$product->id}">编辑</a>
                                        </div>
                                    </th>
                                </tr>
                            {/foreach}
                        {else}
                            <tr style="height:100px;">
                                <th colspan="8" class="text-center" style="vertical-align: middle;">
                                    <a href="configproducts.php?action=create">
                                        当前没有产品, 点我添加.
                                    </a>
                                </th>
                            </tr>
                        {/if}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="users">
            <div class="panel panel-default">
                <div class="panel-heading" style="display: flex; align-items: center">
                    <h3 class="panel-title">用户</h3>
                </div>
                <div class="panel-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>UUID</th>
                                <th>服务ID</th>
                                <th>产品ID</th>
                                <th>流量</th>
                                <th>上传</th>
                                <th>下载</th>
                                <th>启用</th>
                                <th>流量重置日期</th>
                            </tr>
                        </thead>
                        <tbody>
                        {if $users->count() > 0}
                            {foreach $users as $user}
                                <tr>
                                    <th>{$user->id}</th>
                                    <th>{$user->uuid}</th>
                                    <th><a href="clientsservices.php?id={$user->service_id}">{$user->service_id}</a></th>
                                    <th><a href="?module=wray&action=edit_product&product_id={$user->product_id}">{$user->product_id}</a></th>
                                    <th>{$user->friendly_transfer}</th>
                                    <th>{$user->friendly_upload}</th>
                                    <th>{$user->friendly_download}</th>
                                    <th>{$user->friendly_enabled}</th>
                                    <th>{$user->will_reset_on}</th>
                                </tr>
                            {/foreach}
                        {else}
                            <tr style="height:100px;">
                                <th colspan="9" class="text-center" style="vertical-align: middle;">
                                    当前没有用户.
                                </th>
                            </tr>
                        {/if}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="/modules/servers/wray/public/jquery.qrcode.min.js"></script>
<script src="/modules/servers/wray/public/clipboard.min.js"></script>
<div class="modal fade" tabindex="-1" role="dialog" id="modal">
    <div class="modal-dialog" role="document" style="width: 288px">
        <div class="modal-content">
            <div class="modal-body" style="align-content: center">
                扫码后先编辑地址再连接.
                <div id="qrcode" style="width: 256px; height: 256px"></div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<script>
    $(function () {
        $(".qrcode").click(function () {
            $("#qrcode").html("");
            $('#qrcode').qrcode({
                text: $(this).data("qrcode")
            });
            $("#modal").modal()
        });
        new ClipboardJS('.btn');
    })
</script>

