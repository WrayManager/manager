{include "message.tpl"}
<div class="modal fade" tabindex="-1" role="dialog" id="deleteConfirmModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">确认删除?</h4>
            </div>
            <div class="modal-body">
                确认删除服务器 {$server->name} ?
            </div>
            <div class="modal-footer">
                <a href="?module=wray&action=del_server_confirm&server_id={$server->id}" class="btn btn-warning"> 确认 </a>
                <a href="?module=wray" class="btn">返回</a>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
    $(function () {
        $("#deleteConfirmModal").modal();
    });
</script>