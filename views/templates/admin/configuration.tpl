<div class="row">
    <ul class="nav nav-tabs vertical col-lg-2" aria-orientation="vertical">
        <li class="active"><a data-toggle="tab" href="#tabConfig"><i class="icon-cogs"></i> Configurações</a></li>
        <li class=""><a href="{$url_transactions}"><i class="icon-usd"></i> Transações</a></li>
        <li class=""><a href="{$url_requests}"><i class="icon-cloud"></i> Requisições API</a></li>
    </ul>

    <div class='tab-content col-lg-10'>
        <div class='tab-pane active' id="tabConfig">
            <ul class="nav nav-tabs" role="tablist">
                <li class="active"><a data-toggle="tab" href="#tabAuth"><i class="icon-lock"></i> Autenticação</a></li>
                <li><a data-toggle="tab" href="#tabTicket"><i class="icon-barcode"></i> Boleto Bancário</a></li>
                <li><a data-toggle="tab" href="#tabCreditCard"><i class="icon-credit-card"></i> Cartão de Crédito</a></li>
                <li><a data-toggle="tab" href="#tabPix"><i class="icon-qrcode"></i> PIX</a></li>
                <li><a data-toggle="tab" href="#tabMappings"><i class="icon-arrows-h"></i> Mapeamentos</a></li>
                {if $tabs['extra']}<li><a data-toggle="tab" href="#tabExtra"><i class="icon-cogs"></i> Configurações Extras</a></li>{/if}
                {if $tabs['maintenance']}<li><a data-toggle="tab" href="#tabMaintance"><i class="icon-question-circle"></i> Manutenção</a></li>{/if}
                <li><a data-toggle="tab" href="#tabHelp"><i class="icon-question-circle"></i> Ajuda</a></li>
            </ul>

            <div class='tab-content'>
                <div class='tab-pane active' id="tabAuth">{$tabs['auth']}</div>
                <div class='tab-pane' id="tabTicket">{$tabs['ticket']}</div>
                <div class='tab-pane' id="tabCreditCard">{$tabs['credit_card']}</div>
                <div class='tab-pane' id="tabPix">{$tabs['pix']}</div>
                <div class='tab-pane' id="tabMappings">{$tabs['mappings']}</div>
                {if $tabs['extra']}<div class='tab-pane' id="tabExtra">{$tabs['extra']}</div>{/if}
                {if $tabs['maintenance']}
                    <div class='tab-pane' id="tabMaintance">
                        <div class='panel'>
                            {include file=$modules_path|cat:"agcliente/views/templates/hook/includes/tab_maintenance.tpl"}
                        </div>
                    </div>
                {/if}
                <div class='tab-pane' id="tabHelp">
                    <div class='panel'>
                        {include file=$modules_path|cat:"agcliente/views/templates/hook/includes/tab_help.tpl"}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>