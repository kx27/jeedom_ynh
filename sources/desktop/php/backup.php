<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
include_file('3rdparty', 'jquery.fileupload/jquery.ui.widget', 'js');
include_file('3rdparty', 'jquery.fileupload/jquery.iframe-transport', 'js');
include_file('3rdparty', 'jquery.fileupload/jquery.fileupload', 'js');
?>
<div id="backup">
    <div class="row">
        <div class="col-lg-6">
            <legend>{{Sauvegardes}}</legend>
            <form class="form-horizontal">
                <fieldset>
                    <div class="form-group">
                        <div class="form-group expertModeVisible">
                            <label class="col-lg-4 control-label">{{Fréquence des sauvegardes}}</label>
                            <div class="col-lg-3">
                                <input type="text"  class="configKey form-control" data-l1key="backup::cron" />
                            </div>
                            <div class="col-lg-1">
                                <i class="fa fa-question-circle cursor bt_pageHelp" data-name='cronSyntaxe'></i>
                            </div>
                        </div>
                        <label class="col-lg-4 control-label">{{Sauvegardes}}</label>
                        <div class="col-lg-4">
                            <a class="btn btn-default" id="bt_backupJeedom"><i class="fa fa-refresh fa-spin" style="display : none;"></i> <i class="fa fa-floppy-o"></i> Sauvegarder</a>
                        </div>
                    </div>
                    <div class="form-group expertModeVisible">
                        <label class="col-lg-4 control-label">{{Emplacement des sauvegardes}}</label>
                        <div class="col-lg-4">
                            <input type="text" class="configKey form-control" data-l1key="backup::path" />
                        </div>
                    </div>
                    <div class="form-group expertModeVisible">
                        <label class="col-lg-4 control-label">{{Nombre de jour(s) de mémorisation des sauvegardes}}</label>
                        <div class="col-lg-4">
                            <input type="text" class="configKey form-control" data-l1key="backup::keepDays" />
                        </div>
                    </div>
                    <?php  if (config::byKey('market::apikey') != '' || (config::byKey('market::username') != '' && config::byKey('market::password') != '')) { ?>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Envoyer les sauvegardes dans le cloud}}</label>
                            <div class="col-lg-4">
                                <input type="checkbox" class="configKey" data-l1key="backup::cloudUpload" />
                            </div>
                        </div>
                    <?php } ?>
                </fieldset>
            </form>
            <div class="form-actions" style="height: 20px;">
                <a class="btn btn-success" id="bt_saveBackup"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
            </div><br/><br/>
            <legend>{{Sauvegardes locales}}</legend>
            <form class="form-horizontal">
                <fieldset>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">{{Sauvegardes disponibles}}</label>
                        <div class="col-lg-4">
                            <select class="form-control" id="sel_restoreBackup">

                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">{{Restaurer la sauvegarde}}</label>
                        <div class="col-lg-4">
                            <a class="btn btn-warning" id="bt_restoreJeedom"><i class="fa fa-refresh fa-spin" style="display : none;"></i> <i class="fa fa-file"></i> {{Restaurer}}</a>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">{{Supprimer la sauvegarde}}</label>
                        <div class="col-lg-4">
                            <a class="btn btn-danger" id="bt_removeBackup"><i class="fa fa-trash-o"></i> {{Supprimer}}</a>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">{{Envoyer une sauvegarde}}</label>
                        <div class="col-lg-8">
                            <input id="bt_uploadBackup" type="file" name="file" data-url="core/ajax/jeedom.ajax.php?action=backupupload">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">{{Télécharger la sauvegarde}}</label>
                        <div class="col-lg-4">
                            <a class="btn btn-success" id="bt_downloadBackup"><i class="fa fa-cloud-download"></i> {{Télécharger}}</a>
                        </div>
                    </div>
                </fieldset>
            </form>
            <?php  if (config::byKey('market::apikey') != '' || (config::byKey('market::username') != '' && config::byKey('market::password') != '')) { ?>
                <legend>{{Sauvegardes cloud}}</legend>
                <form class="form-horizontal">
                    <fieldset>
                        <?php
                        try {
                            $listeCloudBackup = market::listeBackup();
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
                        }
                        ?>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Sauvegardes disponibles}}</label>
                            <div class="col-lg-4">
                                <select class="form-control" id="sel_restoreCloudBackup">
                                    <?php
                                    try {
                                        foreach ($listeCloudBackup as $backup) {
                                            echo '<option>' . $backup . '</option>';
                                        }
                                    } catch (Exception $e) {
                                        
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Restaurer la sauvegarde}}</label>
                            <div class="col-lg-4">
                                <a class="btn btn-warning" id="bt_restoreCloudJeedom"><i class="fa fa-refresh fa-spin" style="display : none;"></i> <i class="fa fa-file"></i> {{Restaurer}}</a>
                            </div>
                        </div>
                    </fieldset>
                </form>
            <?php } ?>
        </div>
        <div class="col-lg-6">
            <legend>{{Informations sauvegardes}}</legend>
            <pre id="pre_backupInfo"></pre>
            <legend>{{Informations restaurations}}</legend>
            <pre id="pre_restoreInfo">

            </pre>
        </div>
    </div>
</div>


<?php include_file("desktop", "backup", "js"); ?>
