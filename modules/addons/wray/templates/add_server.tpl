{include "message.tpl"}
<div class="container" style="width: auto;">
    <div class="col-md-6 col-md-push-3">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">添加服务器</h3>
            </div>
            <div class="panel-body">
                <form class="form-horizontal" action="?module=wray&action=add_server_submit" method="POST">
                    <div class="form-group">
                        <label for="name" class="col-sm-2 control-label">名称</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="name" name="name" placeholder="示例服务器-1">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="host" class="col-sm-2 control-label">地址</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="host" name="host" placeholder="123.123.123.123">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="port" class="col-sm-2 control-label">端口</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="port" name="port" placeholder="443">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="alter_id" class="col-sm-2 control-label">AlterID</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="alter_id" name="alter_id" placeholder="2">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="tags" class="col-sm-2 control-label">标签</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="tags" name="tags" placeholder="Netflix,HK,IPLC">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="network" class="col-sm-2 control-label">协议</label>
                        <div class="btn-group col-sm-10" data-toggle="buttons">
                            <label class="btn btn-info active">
                                <input type="radio" name="network" value="tcp" checked> TCP
                            </label>
                            <label class="btn btn-info">
                                <input type="radio" name="network" value="ws"> Websocket
                            </label>
                            <label class="btn btn-info">
                                <input type="radio" name="network" value="h2"> HTTP/2
                            </label>
                            <label class="btn btn-info">
                                <input type="radio" name="network" value="quic"> QUIC
                            </label>
                            <label class="btn btn-info">
                                <input type="radio" name="network" value="kcp"> KCP
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <div class="">
                                <label class="">
                                    <input type="checkbox" name="tls"> TLS
                                </label>
                                <label class="">
                                    <input type="checkbox" name="add_to_all"> 添加到所有现有产品
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <div class="checkbox" data-toggle="buttons">
                                <label class="">
                                    <input type="checkbox" name="continue" checked> 继续添加
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