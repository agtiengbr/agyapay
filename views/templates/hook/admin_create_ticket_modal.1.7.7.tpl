<div class="modal agyapay-create-ticket" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form class='form-horizontal'>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Criar Boleto</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                        <div class='form-group row'>
                        <label class='form-control-label '>Data de Vencimento</label>
                        <div class='col-sm'>
                            <input id='agyapay-expiration-date' type="date" min="{date('Y-m-d')}" value="{$default_expiration_date}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary">Gerar Boleto</button>
                </div>
            </div>
        </div>
    </form>
</div>
