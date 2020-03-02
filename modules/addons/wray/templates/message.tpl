{if $message}
    <div class="alert alert-{$status} alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        {$message}
    </div>
{/if}