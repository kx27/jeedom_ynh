<?php
$startLoadTime = getmicrotime();
include_file('core', 'authentification', 'php');
global $JEEDOM_INTERNAL_CONFIG;
if (init('p') == '' && isConnect()) {
    $homePage = explode('::', $_SESSION['user']->getOptions('homePage', 'core::dashboard'));
    if (count($homePage) == 2) {
        if ($homePage[0] == 'core') {
            redirect('index.php?v=d&p=' . $homePage[1]);
        } else {
            redirect('index.php?v=d&m=' . $homePage[0] . '&p=' . $homePage[1]);
        }
    } else {
        redirect('index.php?v=d&p=dashboard');
    }
}
$page = '';
if (isConnect() && init('p') != '') {
    $page = init('p');
}
$plugin = init('m');
if ($plugin != '') {
    $plugin = plugin::byId($plugin);
    if (is_object($plugin)) {
        $title = $plugin->getName();
    }
}
$plugins_list = plugin::listPlugin(true, true);
$plugin_menu = '';
$panel_menu = '';
if (count($plugins_list) > 0) {
    foreach ($plugins_list as $category_name => $category) {
        $icon = '';
        if (isset($JEEDOM_INTERNAL_CONFIG['plugin']['category'][$category_name]) && isset($JEEDOM_INTERNAL_CONFIG['plugin']['category'][$category_name]['icon'])) {
            $icon = $JEEDOM_INTERNAL_CONFIG['plugin']['category'][$category_name]['icon'];
        }
        $name = $category_name;
        if (isset($JEEDOM_INTERNAL_CONFIG['plugin']['category'][$category_name]) && isset($JEEDOM_INTERNAL_CONFIG['plugin']['category'][$category_name]['name'])) {
            $name = $JEEDOM_INTERNAL_CONFIG['plugin']['category'][$category_name]['name'];
        }
        $plugin_menu .= '<li class="dropdown-submenu"><a data-toggle="dropdown"><i class="fa ' . $icon . '"></i> {{' . $name . '}}</a>';
        $plugin_menu .= '<ul class="dropdown-menu">';
        foreach ($category as $pluginList) {
            $plugin_menu .= '<li><a href="index.php?v=d&m=' . $pluginList->getId() . '&p=' . $pluginList->getIndex() . '"><i class="' . $pluginList->getIcon() . '"></i> ' . $pluginList->getName() . '</a></li>';
            if ($pluginList->getDisplay() != '') {
                $panel_menu .= '<li><a href="index.php?v=d&m=' . $pluginList->getId() . '&p=' . $pluginList->getDisplay() . '"><i class="' . $pluginList->getIcon() . '"></i> ' . $pluginList->getName() . '</a></li>';
            }
        }
        $plugin_menu .= '</ul>';
        $plugin_menu .= '</li>';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Jeedom</title>
        <link rel="shortcut icon" href="core/img/logo-jeedom-sans-nom-couleur-25x25.png">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="">
        <meta name="author" content="">
        <META HTTP-EQUIV="Pragma" CONTENT="private">
        <META HTTP-EQUIV="Cache-Control" CONTENT="private, max-age=5400, pre-check=5400">
        <META HTTP-EQUIV="Expires" CONTENT="<?php echo date(DATE_RFC822, strtotime("1 day")); ?>">
        <script>
            var clientDatetime = new Date();
            var clientServerDiffDatetime = (<?php echo strtotime('now'); ?> * 1000) - clientDatetime.getTime();
            var io = null;
        </script>
        <script type="text/javascript" src="/socket.io/socket.io.js?1.1.0"></script>
        <?php
        if (!isConnect() || $_SESSION['user']->getOptions('bootstrap_theme') == '') {
            include_file('3rdparty', 'bootstrap/css/bootstrap.min', 'css');
        } else {
            try {
                include_file('3rdparty', 'bootstrap/css/bootstrap.min.' . $_SESSION['user']->getOptions('bootstrap_theme'), 'css');
            } catch (Exception $e) {
                include_file('3rdparty', 'bootstrap/css/bootstrap.min', 'css');
            }
        }
        include_file('core', 'icon.inc', 'php');
        include_file('desktop', 'commun', 'css');
        include_file('core', 'core', 'css');
        include_file('3rdparty', 'jquery.toastr/jquery.toastr.min', 'css');
        include_file('3rdparty', 'jquery.ui/jquery-ui-bootstrap/jquery-ui', 'css');
        include_file('3rdparty', 'jquery.utils/jquery.utils', 'css');
        include_file('3rdparty', 'jquery/jquery.min', 'js');
        include_file('3rdparty', 'jquery.utils/jquery.utils', 'js');
        include_file('core', 'core', 'js');
        include_file('3rdparty', 'bootstrap/bootstrap.min', 'js');
        include_file('3rdparty', 'jquery.ui/jquery-ui.min', 'js');
        include_file('3rdparty', 'jquery.ui/jquery.ui.datepicker.fr', 'js');
        include_file('core', 'js.inc', 'php');
        include_file('3rdparty', 'bootbox/bootbox.min', 'js');
        include_file('3rdparty', 'highstock/highstock', 'js');
        include_file('3rdparty', 'highstock/highcharts-more', 'js');
        include_file('3rdparty', 'highstock/modules/solid-gauge', 'js');
        include_file('desktop', 'utils', 'js');
        include_file('3rdparty', 'jquery.toastr/jquery.toastr.min', 'js');
        include_file('3rdparty', 'jquery.at.caret/jquery.at.caret.min', 'js');

        if (isConnect() && $_SESSION['user']->getOptions('desktop_highcharts_theme') != '') {
            try {
                include_file('3rdparty', 'highstock/themes/' . $_SESSION['user']->getOptions('desktop_highcharts_theme'), 'js');
            } catch (Exception $e) {
                
            }
        }
        ?>
    </head>
    <body>
        <?php
        sendVarToJS('jeedom_langage', config::byKey('language'));
        if (!isConnect()) {
            include_file('desktop', 'connection', 'php');
        } else {

            sendVarToJS('userProfils', $_SESSION['user']->getOptions());
            sendVarToJS('user_id', $_SESSION['user']->getId());
            sendVarToJS('user_login', $_SESSION['user']->getLogin());
            sendVarToJS('nodeJsKey', config::byKey('nodeJsKey'));
            sendVarToJS('eqLogic_width_step', config::byKey('eqLogic::widget::stepWidth'));
            sendVarToJS('eqLogic_height_step', config::byKey('eqLogic::widget::stepHeight'));
            sendVarToJS('jeedom_firstUse', config::byKey('jeedom::firstUse', 'core', 1));
            ?>
            <div id="wrap">
                <header class="navbar navbar-fixed-top navbar-default">
                    <div class="container-fluid">
                        <div class="navbar-header">
                            <a class="navbar-brand" href="index.php?v=d">
                                <img src="core/img/logo-jeedom-grand-nom-couleur.svg" height="30" style="position: relative; top:-5px;"/>
                            </a>
                            <button class="navbar-toggle" type="button" data-toggle="collapse" data-target=".navbar-collapse">
                                <span class="sr-only">{{Toggle navigation}}</span>
                                <span class="icon-bar"></span>
                                <span class="icon-bar"></span>
                                <span class="icon-bar"></span>
                            </button>
                        </div>
                        <nav class="navbar-collapse collapse">

                            <ul class="nav navbar-nav">
                                <?php if (config::byKey('jeeNetwork::mode') == 'master') { ?>
                                    <li class="dropdown cursor">
                                        <a class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-home"></i> {{Accueil}} <b class="caret"></b></a>
                                        <ul class="dropdown-menu">
                                            <li><a href="index.php?v=d&p=dashboard"><i class="fa fa-dashboard"></i> {{Dashboard}}</a></li>
                                            <li><a href="index.php?v=d&p=view"><i class="fa fa-bars"></i> {{Vue}}</a></li>
                                            <li><a href="index.php?v=d&p=plan"><i class="fa fa-picture-o"></i> {{Designs}}</a></li>
                                            <?php
                                            echo $panel_menu;
                                            ?>
                                        </ul>
                                    </li>
                                    <li><a href="index.php?v=d&p=history"><i class="fa fa-bar-chart-o"></i> {{Historique}}</a></li>
                                <?php } ?>

                                <?php if (isConnect('admin')) { ?>
                                    <li class="dropdown cursor">
                                        <a data-toggle="dropdown"><i class="fa fa-qrcode"></i> {{Général}} <b class="caret"></b></a>
                                        <ul class="dropdown-menu" role="menu">
                                            <li class="dropdown-submenu">
                                                <a data-toggle="dropdown"><i class="fa fa-cogs"></i> {{Administration}}</a>
                                                <ul class="dropdown-menu">
                                                    <li><a href="index.php?v=d&p=administration" tabindex="0"><i class="fa fa-wrench"></i> {{Configuration}}</a></li>
                                                    <li><a href="index.php?v=d&p=user"><i class="fa fa-users"></i> {{Utilisateurs}}</a></li>
                                                    <?php if (config::byKey('jeedom::licence') > 9) { ?>
                                                        <li><a href="index.php?v=d&p=rights"><i class="fa fa-graduation-cap"></i> {{Gestion des droits avancés}}</a></li>
                                                    <?php } ?>
                                                    <li><a href="index.php?v=d&p=backup"><i class="fa fa-floppy-o"></i> {{Sauvegarde}}</a></li>
                                                    <li><a href="index.php?v=d&p=update"><i class="fa fa-refresh"></i> {{Centre de mise à jour}}</a></li>
                                                    <?php if (config::byKey('jeeNetwork::mode') == 'master') { ?>
                                                        <li class="expertModeVisible"><a href="index.php?v=d&p=timeline"><i class="fa fa-history"></i> {{Timeline}}</a></li>
                                                        <?php if (config::byKey('jeedom::licence') >= 5) { ?>
                                                            <li class="expertModeVisible"><a href="index.php?v=d&p=jeeNetwork"><i class="fa fa-sitemap"></i> {{Réseau Jeedom}}</a></li>
                                                        <?php } ?>
                                                    <?php } ?>
                                                    <li class="expertModeVisible"><a href="index.php?v=d&p=cron"><i class="fa fa-tasks"></i> {{Moteur de tâches}}</a></li>
                                                    <li class="expertModeVisible"><a href="index.php?v=d&p=security"><i class="fa fa-lock"></i> {{Sécurité}}</a></li>
                                                    <li class="expertModeVisible"><a href="index.php?v=d&p=log"><i class="fa fa-file-o"></i> {{Log}}</a></li>
                                                </ul>
                                            </li>
                                            <?php if (config::byKey('jeeNetwork::mode') == 'master') { ?>
                                                <li><a href="index.php?v=d&p=object"><i class="fa fa-picture-o"></i> {{Objet}}</a></li>
                                            <?php } ?>
                                            <li><a href="index.php?v=d&p=plugin"><i class="fa fa-tags"></i> {{Plugins}}</a></li>
                                            <?php if (config::byKey('jeeNetwork::mode') == 'master') { ?>
                                                <li><a href="index.php?v=d&p=interact"><i class="fa fa-comments-o"></i> {{Interaction}}</a></li>
                                                <li><a href="index.php?v=d&p=display"><i class="fa fa-th"></i> {{Affichage}}</a></li>
                                                <?php
                                            }
                                            if (isConnect('admin') && config::byKey('jeeNetwork::mode') == 'master') {
                                                ?>
                                                <li><a href="index.php?v=d&p=scenario"><i class="fa fa-cogs"></i> {{Scénario}}</a></li>
                                            <?php } ?>
                                        </ul>
                                    </li>
                                    <?php
                                }
                                if (isConnect('admin') && config::byKey('jeeNetwork::mode') == 'master') {
                                    ?>

                                    <li class="dropdown cursor">
                                        <a data-toggle="dropdown"><i class="fa fa-tasks"></i> {{Plugins}} <b class="caret"></b></a>
                                        <ul class="dropdown-menu" role="menu">
                                            <?php
                                            if (count($plugins_list) == 0) {
                                                echo '<li><a href="index.php?v=d&p=plugin"><i class="fa fa-tags"></i> {{Installer un plugin}}</a></li>';
                                            } else {
                                                echo $plugin_menu;
                                            }
                                            ?>
                                        </ul>
                                    </li>
                                <?php } ?>
                            </ul>

                            <ul class="nav navbar-nav navbar-right">
                                <?php $displayMessage = (message::nbMessage() > 0) ? '' : 'display : none;'; ?>
                                <li><a href="index.php?v=d&p=message">
                                        <span class="label label-warning" id="span_nbMessage" style="<?php echo $displayMessage; ?>">
                                            <i class="fa fa-envelope"></i> <?php echo message::nbMessage(); ?> {{message(s)}}
                                        </span>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <i class="fa fa-clock-o"></i> <span id="horloge"><?php echo date('H:i:s'); ?></span>
                                    </a>
                                </li>
                                <li class="dropdown">
                                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                                        <i class="fa fa-user"></i> <?php echo $_SESSION['user']->getLogin(); ?>
                                        <span class="caret"></span>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a href="index.php?v=d&p=profils"><i class="fa fa-briefcase"></i> {{Profil}}</a></li>
                                        <?php
                                        if (isConnect('admin')) {
                                            if ($_SESSION['user']->getOptions('expertMode') == 1) {
                                                echo '<li class="cursor"><a id="bt_expertMode" state="1"><i class="fa fa-check-square-o"></i> {{Mode expert}}</a></li>';
                                            } else {
                                                echo '<li class="cursor"><a id="bt_expertMode" state="0"><i class="fa fa-square-o"></i> {{Mode expert}}</a></li>';
                                            }
                                        }
                                        ?>
                                        <li><a href="index.php?v=m"><i class="fa fa-mobile"></i> {{Version mobile}}</a></li>
                                        <li class="divider"></li>
                                        <li><a href="index.php?v=d&logout=1"><i class="fa fa-sign-out"></i> {{Se déconnecter}}</a></li>
                                    </ul>
                                </li>
                                <li>
                                    <a class="bt_pageHelp cursor tooltips" title="{{Aide sur la page en cours}}"
                                    <?php
                                    echo 'data-name="' . $page . '"';
                                    if (isset($plugin) && is_object($plugin)) {
                                        echo ' data-plugin="' . $plugin->getId() . '"';
                                    }
                                    ?>>
                                        <i class="fa fa-question-circle" ></i>
                                    </a>
                                </li>
                                <li>
                                    <a class="bt_reportBug cursor tooltips" title="{{Envoyer un rapport de bug}}">
                                        <i class="fa fa-exclamation-circle" ></i>
                                    </a>
                                </li>
                            </ul>
                        </nav><!--/.nav-collapse -->
                    </div>
                </header>
                <main class="container-fluid" id="div_mainContainer">
                    <?php
                    if (!cron::ok()) {
                        echo '<div style="width : 100%" class="alert alert-warning">{{Erreur cron : il n\'y a pas eu de lancement depuis plus de 1h}}</div>';
                    }
                    if (config::byKey('enableCron', 'core', 1, true) == 0) {
                        echo '<div style="width : 100%" class="alert alert-warning">{{Erreur cron : les crons sont désactivés, aller dans Générale -> Administration -> Moteur de tache pour les réactiver}}</div>';
                    }
                    if (config::byKey('enableScenario') == 0) {
                        echo '<div style="width : 100%" class="alert alert-warning">{{Erreur scénario : tous les scénarios sont désactivés aller sur la page des scénarios pour les réactiver}}</div>';
                    }
                    ?>
                    <div style="display: none;width : 100%" id="div_alert"></div>
                    <?php
                    try {
                        if (isset($plugin) && is_object($plugin)) {
                            include_file('desktop', $page, 'php', $plugin->getId());
                        } else {
                            include_file('desktop', $page, 'php');
                        }
                    } catch (Exception $e) {
                        ob_end_clean();
                        echo '<div class="alert alert-danger div_alert">';
                        echo displayExeption($e);
                        echo '</div>';
                    }
                    ?>
                    <div id="md_modal"></div>
                    <div id="md_modal2"></div>
                    <div id="md_pageHelp" style="display: none;" title="Aide">
                        <ul class="nav nav-tabs">
                            <li class="active"><a href="#div_helpWebsite" data-toggle="tab">{{Générale}}</a></li>
                            <li><a href="#div_helpSpe" data-toggle="tab">{{Détaillée}}</a></li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane active" id="div_helpWebsite" ></div>
                            <div class="tab-pane" id="div_helpSpe" ></div>
                        </div>
                    </div>
                    <div id="md_reportBug" title="{{Ouverture d'un ticket}}"></div>
                </main>
            </div>
            <?php
        }
        if (isConnect()) {
            ?>
            <footer>
                <span class="pull-left">Node JS <span class="span_nodeJsState binary red tooltips"></span> - </span>
                <span class="pull-left">&copy; <a id="bt_jeedomAbout" class="cursor">Jeedom</a> (v<?php echo getVersion('jeedom') ?> 
                    <?php
                    $nbNeedUpdate = update::nbNeedUpdate();
                    if ($nbNeedUpdate > 0) {
                        echo '<span class="label label-danger"><a href="index.php?v=d&p=update" style="color : white;">' . $nbNeedUpdate . ' {{Mise(s) à jour disponible}}</a></span>';
                    }
                    echo ') ';
                    echo date('Y');
                    echo ' - {{Page générée en}} ' . round(getmicrotime() - $startLoadTime, 3) . 's';
                    ?>
                </span>
            </footer>
        <?php } ?>
    </body>
</html>