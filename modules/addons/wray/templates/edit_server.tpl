{include "message.tpl"}
<div class="container" style="width: auto;">
    <div class="col-md-6 col-md-push-3">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">编辑服务器 #{$server->id}</h3>
            </div>
            <div class="panel-body">
                <form class="form-horizontal" action="?module=wray&action=edit_server_submit&server_id={$server->id}" method="POST">
                    <div class="form-group">
                        <label for="name" class="col-sm-2 control-label">名称</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="name" name="name" placeholder="示例服务器-1" value="{$server->name}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">月流量</label>
                        <div class="col-sm-10">
                            <p class="form-control-static">{$server->transfer|flowautoshow}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="host" class="col-sm-2 control-label">地址</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="host" name="host" placeholder="123.123.123.123" value="{$server->host}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="port" class="col-sm-2 control-label">端口</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="port" name="port" placeholder="443" value="{$server->port}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="alter_id" class="col-sm-2 control-label">AlterID</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="alter_id" name="alter_id" placeholder="2" value="{$server->alter_id}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="tags" class="col-sm-2 control-label">标签</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="tags" name="tags" placeholder="Netflix,HK,IPLC" value="{$server->attributes['tags']}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="network" class="col-sm-2 control-label">协议</label>
                        <div class="btn-group col-sm-10" data-toggle="buttons">
                            <label class="btn btn-info {if $server->network == "tcp"}active{/if}">
                                <input type="radio" name="network" value="tcp" {if $server->network == "tcp"}checked{/if}> TCP
                            </label>
                            <label class="btn btn-info {if $server->network == "ws"}active{/if}">
                                <input type="radio" name="network" value="ws" {if $server->network == "ws"}checked{/if}> Websocket
                            </label>
                            <label class="btn btn-info {if $server->network == "h2"}active{/if}">
                                <input type="radio" name="network" value="h2" {if $server->network == "h2"}checked{/if}> HTTP/2
                            </label>
                            <!--
                            <label class="btn btn-info {if $server->network == "quic"}active{/if}">
                                <input type="radio" name="network" value="quic" {if $server->network == "quic"}checked{/if}> QUIC
                            </label>
                            -->
                            <label class="btn btn-info {if $server->network == "kcp"}active{/if}">
                                <input type="radio" name="network" value="kcp" {if $server->network == "kcp"}checked{/if}> KCP
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <div class="">
                                <label class="">
                                    <input type="checkbox" name="tls" {if $server->tls}checked{/if} > TLS
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <input type="submit" class="btn btn-success"/>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
