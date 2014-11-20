<?php
if (!isConnect()) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
if (config::byKey('market::address') == '') {
    throw new Exception('{{Aucune addresse pour le market de renseignée}}');
}
if (config::byKey('market::apikey') == '' && config::byKey('market::username') == '') {
    throw new Exception('{{Aucune clef market de renseigner. Veuillez vous enregistrer sur le market puis renseigner la clef dans Jeedom avant d\'ouvrir un ticket}}');
}
?>
<div id='div_alertReportBug'></div>
<form class="form-horizontal" role="form" id="form_reportBug">
    <div class='alert alert-info'>
        {{Attention lors de l'envoi d'un rapport de bug, tous les logs sont automatiquement envoyés en même temps. Cependant ces logs ne seront accessibles que par vous ou l'équipe de support.}}
    </div>
    <div class="form-group">
        <label class="col-lg-2 control-label">{{Titre}}</label>
        <div class="col-lg-10">
            <input class="form-control input-sm ticketAttr" data-l1key="title"/>
        </div> 
    </div>
    <div class="form-group">
        <label class="col-lg-2 control-label">{{Catégorie}}</label>
        <div class="col-lg-10">
            <select class="form-control input-sm ticketAttr" data-l1key="category">
                <option>{{Générale}}</option>
                <option>{{Scénario}}</option>
                <option>{{Historique}}</option>
                <?php
                foreach (plugin::listPlugin(true) as $plugin) {
                    echo '<option>plugin::' . $plugin->getId() . '</option>';
                }
                ?>
            </select>
        </div> 
    </div>
    <div class="form-group">
        <label class="col-lg-2 control-label">{{Message}}</label>
        <div class="col-lg-10">
            <textarea class="form-control messageAttr input-sm" data-l1key="message" ></textarea>
            <input class="form-control ticketAttr" data-l1key="options" data-l2key="page" style="display: none;"/>
        </div> 
    </div>
    <div class="form-actions" style="height: 20px;">
        <a class="btn btn-success pull-right" id="bt_sendBugReport" style="color:white;"><i class="fa fa-check-circle"></i> {{Envoyer}}</a>
    </div>
</form>

<script>
    $('.ticketAttr[data-l1key=options][data-l2key=page]').value(location.href);

    $('#bt_sendBugReport').on('click', function () {
        var ticket = $('#form_reportBug').getValues('.ticketAttr');
        var ticket = ticket[0];
        ticket.messages = $('#form_reportBug').getValues('.messageAttr');
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "core/ajax/market.ajax.php", // url du fichier php
            data: {
                action: "sendReportBug",
                ticket: json_encode(ticket),
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error, $('#div_alertReportBug'));
            },
            success: function (data) { // si l'appel a bien fonctionné
                if (data.state != 'ok') {
                    $('#div_alertReportBug').showAlert({message: data.result, level: 'danger'});
                    return;
                }
                $('#div_alertReportBug').showAlert({message: '{{Votre ticket à bien été ouvert. Vous pouvez le suivre}} <a target="_blank" href="' + data.result.url + '">{{ici}}</a>', level: 'success'});
            }
        });
    });
</script>