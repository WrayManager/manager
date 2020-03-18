{if $user}
    <div class="row">
        <div class="col-md-3">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div>当前套餐</div>
                    <h1>{$product}</h1>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div>下次续费日期</div>
                    <h3>{$nextDueDay}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div>下次流量重置日期</div>
                    <h3>{$user->will_reset_on->toDateString()}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div>流量</div>
                    <h1>
                        <div class="progress" style="height: 10px">
                            <div class="progress-bar progress-bar-{if $user->ratio < 50}success{else}{if $user->ratio > 80}danger{else}warning{/if}{/if}" role="progressbar" style="width: {$user->ratio}%;font-size: 10px;line-height: 10px">
                            </div>
                        </div>
                        <div style="font-size: 18px">
                            <div class="pull-left">
                                {$user->friendly_used}
                            </div>
                            <div class="pull-right">
                                / {$user->friendly_transfer}
                            </div>
                            <div class="clearfix"></div>
                        </div>
                    </h1>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title">产品信息</h3>
                </div>
                <div>
                    <table class="table">
                        <thead>
                        <tr>
                            <th>产品编号</th>
                            <th>UUID</th>
                            <th>订阅地址</th>
                            <th>安全设置</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td style="width: 10%;">{$user->id}</td>
                            <td style="width: 45%;"><span id="userId" onclick="javascript:document.getElementById('userId').innerHTML='{$user->uuid}';">点击显示隐藏内容</span></td>
                            <td style="width: 10%">
                                <div class="btn-group btn-group-xs" role="group" aria-label="Extra-small button group">
                                    <button type="button" class="btn btn-info btn-xs autoset" id="selectsub"  data-clipboard-text="{$systemurl}/index.php?m=wray&token={$sub}">
                                        <span class="glyphicon glyphicon-link" aria-hidden="true"></span> 复制订阅地址
                                    </button>
                                </div>
                            </td>
                            <td style="width: 10%">
                                <div class="btn-group btn-group-xs" role="group" aria-label="Extra-small button group">
                                    <a href="?action=productdetails&id={$moduleParams['serviceid']}&modop=custom&a=changeUUID" type="button" class="btn btn-info btn-xs autoset" id="securityReset">
                                        <span class="glyphicon glyphicon-refresh" aria-hidden="true"></span> 重置订阅与UUID
                                    </a>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title">节点信息</h3>
                </div>
                <div class="legend-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>节点名称</th>
                            <th>连接地址</th>
                            <th>流量倍率</th>
                            <th>连接端口</th>
                            <th>加密方式</th>
                            <th>TLS</th>
                            <th>节点标签</th>
                            <th>节点负载</th>
                            <th>扫码连接</th>
                        </tr>
                        </thead>
                        <tbody>
                        {foreach $user->product->servers as $server}
                            <tr>
                                <td>{$server->name}</td>
                                <td>{$server->host}{if $server->network=="ws"}{$server->ws_path}{/if}</td>
                                <th>{$server->rate}</th>
                                <td>{$server->port}</td>
                                <td>{$server->network}</td>
                                <td>{if $server->tls} ✓ {else} X {/if}</td>
                                <td>
                                    {foreach $server->tags as $tag}
                                        <span class="badge badge-success">{$tag}</span>
                                    {/foreach}
                                </td>
                                <th>{$server->friendly_load_user}</th>
                                <td>
                                    <div class="btn-group btn-group-xs" role="group" aria-label="Extra-small button group">
                                        <button type="button" class="btn btn-info btn-xs autohides qrcode" data-qrname="V2ray" data-qrcode="{$server->toVmess($user->uuid)}" data-client="移动端" title="V2ray 二维码" name="qrcode">
                                            <span class="fa fa-qrcode" aria-hidden="true"></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" tabindex="-1" role="dialog" id="modal">
        <div class="modal-dialog" role="document" style="width: 288px">
            <div class="modal-content">
                <div class="modal-body" style="align-content: center">
                    <div id="qrcode" style="width: 256px; height: 256px"></div>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    <script src="/modules/servers/wray/public/jquery.qrcode.min.js"></script>
    <script src="/modules/servers/wray/public/clipboard.min.js"></script>
    <script>
        $(document).ready(function(){
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
    {else}
    <script>
        alert("产品还未开通!");
    </script>
{/if}