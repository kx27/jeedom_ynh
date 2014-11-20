<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
if (config::byKey('jeedom::licence') < 5) {
    throw new Exception(__('Votre licence ne vous autorise pas à utiliser le mode esclave'));
}
?>

<div class="row row-overflow">
    <div class="col-lg-2">
        <div class="bs-sidebar">
            <ul id="ul_jeeNetwork" class="nav nav-list bs-sidenav">
                <a id="bt_addJeeNetwork" class="btn btn-default" style="width : 100%;margin-top : 5px;margin-bottom: 5px;"><i class="fa fa-plus-circle"></i> {{Ajouter un Jeedom}}</a>
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
                <?php
                foreach (jeeNetwork::all() as $jeeNetwork) {
                    echo '<li class="cursor li_jeeNetwork" data-jeeNetwork_id="' . $jeeNetwork->getId() . '"><a>' . $jeeNetwork->getName() . '</a></li>';
                }
                ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-10 jeeNetwork" style="display: none;" id="div_conf">
        <div class="row">
            <div class="col-lg-6">
                <form class="form-horizontal">
                    <fieldset>
                        <legend>{{Général}}</legend>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Nom du Jeedom esclave}}</label>
                            <div class="col-lg-6">
                                <input class="form-control jeeNetworkAttr" type="text" data-l1key="id" style="display : none;"/>
                                <input class="form-control jeeNetworkAttr" type="text" data-l1key="name" placeholder="Nom du Jeedom exclave"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{IP}}</label>
                            <div class="col-lg-4">
                                <input class="form-control jeeNetworkAttr" type="text" data-l1key="ip" placeholder="IP"/>
                            </div>
                            <div class="col-lg-4">
                                <div class="alert alert-info">{{Attention ne pas oublié le /jeedom après l'ip si vous l'utilisez pour vous rendre sur l'interface de jeedom}}</div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Clef API}}</label>
                            <div class="col-lg-6">
                                <input class="form-control jeeNetworkAttr" type="text" data-l1key="apikey" placeholder="Clef API"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Accéder (attention ne marche que si vous êtes sur le réseau local)}}</label>
                            <div class="col-lg-6">
                                <a class="btn btn-default" id="bt_connectToSlave" target="_blank">{{Se connecter}}</a>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Arrêter/Redémarrer}}</label>
                            <div class="col-lg-6">
                                <a class="btn btn-danger" id="bt_stop"><i class="fa fa-stop"></i> {{Arrêter}}</a>
                                <a class="btn btn-warning" id="bt_stop"><i class="fa fa-repeat"></i> {{Redémarrer}}</a>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Mise à jour}}</label>
                            <div class="col-lg-6">
                                <a class="btn btn-warning" id="bt_checkUpdateSlave"><i class="fa fa-refresh"></i>  {{Vérifier}}</a>
                                <a class="btn btn-default" id="bt_updateSlave"><i class="fa fa-check"></i> {{Mettre à jour}}</a>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Backup}}</label>
                            <div class="col-lg-6">
                                <select class="form-control" id="sel_backupList"></select>
                            </div>
                            <div class="col-lg-2">
                                <a class="btn btn-default" id="bt_restoreSlave"><i class="fa fa-check"></i> {{Restaurer}}</a>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>
            <div class="col-lg-6">
                <form class="form-horizontal">
                    <fieldset>
                        <legend>{{Informations}}</legend>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Statut}}</label>
                            <div class="col-lg-3">
                                <span class="label label-default jeeNetworkAttr" type="text" data-l1key="status" ></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Version de Jeedom}}</label>
                            <div class="col-lg-3">
                                <span class="label label-default jeeNetworkAttr" type="text" data-l1key="configuration" data-l2key="version" ></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Nombre de mise(s) à jour}}</label>
                            <div class="col-lg-3">
                                <span class="label label-default jeeNetworkAttr" type="text" data-l1key="configuration" data-l2key="nbUpdate" ></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Nombre de message(s)}}</label>
                            <div class="col-lg-3">
                                <span class="label label-default jeeNetworkAttr" type="text" data-l1key="configuration" data-l2key="nbMessage" ></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Dernière communication}}</label>
                            <div class="col-lg-3">
                                <span class="label label-default jeeNetworkAttr" type="text" data-l1key="configuration" data-l2key="lastCommunication" ></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Plugin}}</label>
                            <div class="col-lg-6" id="div_pluginList"></div>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>
        <legend>{{Logs & messages :}}</legend>
        <select id="sel_logSlave" class="form-control" style="display: inline-block;width: 200px;"></select>
        <a class="btn btn-success" id="bt_showLog">Voir</a>
        <a class="btn btn-warning" id="bt_emptyLog"><i class="fa fa-times"></i> Vider</a>
        <a class="btn btn-danger" id="bt_removeLog"><i class="fa fa-trash-o"></i> Supprimer</a>
        <a class="btn btn-default pull-right" id="bt_showMessage"><i class="fa fa-eye"></i> Voir les messages</a>
        <a class="btn btn-danger pull-right" id="bt_emptyMessage"><i class="fa fa-trash-o"></i> Vider les messages</a>
        <br/><br/>
        <pre id="pre_updateInfo"></pre>
        <hr/>
        <form class="form-horizontal">
            <fieldset>
                <div class="form-actions">
                    <a class="btn btn-danger" id="bt_removeJeeNetwork"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
                    <a class="btn btn-success" id="bt_saveJeeNetwork"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
                </div>
            </fieldset>
        </form>
    </div>
</div>

<?php include_file('desktop', 'jeeNetwork', 'js'); ?>