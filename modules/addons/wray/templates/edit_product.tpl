{include "message.tpl"}
<div class="container" style="width: auto;">
    <div class="col-md-6 col-md-push-3">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">编辑产品 #{$product->id}</h3>
            </div>
            <div class="panel-body">
                <form class="form-horizontal" action="?module=wray&action=edit_product_submit&product_id={$product->id}" method="POST">
                    <div class="form-group">
                        <label for="name" class="col-sm-2 control-label">名称</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="name" name="name" value="{$product->name}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="transfer" class="col-sm-2 control-label">流量</label>
                        <div class="col-sm-10">
                            <div class="input-group">
                                <input type="text" class="form-control" aria-label="" id="transfer" name="transfer" value="{$product->transfer}">
                                <input type="text" class="hidden" aria-label="" id="unit" name="unit" value="{$product->unit}">
                                <div class="input-group-btn">
                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <span id="unit-label">{$product->unit|upper}</span> <span class="caret"></span></button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        <li><a class="unit-dropdown" href="#" data-value="gb">GB</a></li>
                                        <li><a class="unit-dropdown" href="#" data-value="tb">TB</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="cycle" class="col-sm-2 control-label"> 周期 </label>
                        <div class="col-sm-10">
                            <div class="dropdown">
                                <input type="text" class="hidden" aria-label="" id="cycle" name="cycle" value="{$product->cycle}">
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <span id="cycle-label">{$product->friendly_cycle}</span> <span class="caret"></span></button>
                                <ul class="dropdown-menu">
                                    <li><a class="cycle-dropdown" href="#" data-value="month">月</a></li>
                                    <li><a class="cycle-dropdown" href="#" data-value="quarter">季</a></li>
                                    <li><a class="cycle-dropdown" href="#" data-value="year">年</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="servers" class="col-sm-2 control-label"> 服务器: </label>
                        <ul class="list-group col-sm-10" style="padding: 6px 12px;">
                            {foreach $servers as $server}
                                <li class="list-group-item server-list" data-toggle="servers_{$server->id}">
                                    <input type="checkbox" name="servers[{$server->id}]" id="servers_{$server->id}" {if $product->hasServer($server->id)} checked {/if}>
                                    #{$server->id} {$server->name}
                                </li>
                            {/foreach}
                        </ul>
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


<script>
    $(function () {
        $(".unit-dropdown").click(function () {
            $("#unit").val($(this).data("value"));
            $("#unit-label").html($(this).html());
        });
        $(".cycle-dropdown").click(function () {
            $("#cycle").val($(this).data("value"));
            $("#cycle-label").html($(this).html());
        })
        $(".server-list").click(function () {
            var checkbox = $("#"+$(this).data("toggle"));
            checkbox.prop("checked", !checkbox.prop("checked"));
        })
        $("input[type=checkbox]").click(function () {
            var checkbox = $(this);
            checkbox.prop("checked", !checkbox.prop("checked"));
        });
    });
</script>