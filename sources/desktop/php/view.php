<?php
if (!isConnect()) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
include_file('3rdparty', 'jquery.masonry/jquery.masonry', 'js');
if (init('id') == '') {
    if ($_SESSION['user']->getOptions('defaultDesktopView') != '') {
        $view = view::byId($_SESSION['user']->getOptions('defaultDesktopView'));
        if (is_object($view)) {
            redirect('index.php?v=d&p=view&id=' . $view->getId());
        }
    }
    $list_view = view::all();
    if (isset($list_view[0]) && is_object($list_view[0])) {
        redirect('index.php?v=d&p=view&id=' . $list_view[0]->getId());
    }
}
if (init('id') != '') {
    $view = view::byId(init('id'));
    if (!is_object($view)) {
        throw new Exception('{{Vue inconnue. Verifier l\'id}}');
    }
} else {
    redirect('index.php?v=d&p=view_edit');
}
include_file('3rdparty', 'jquery.masonry/jquery.masonry', 'js');
sendVarToJS('view_id', $view->getId());
?>

<div class="row row-overflow">
    <div class="col-lg-2">
        <div class="bs-sidebar">
            <ul id="ul_view" class="nav nav-list bs-sidenav">
                <a class="btn btn-default" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" href="index.php?v=d&p=view_edit"><i class="fa fa-plus-circle"></i> {{Ajouter une vue}}</a>
                <li class="filter"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
                <?php
                foreach (view::all() as $view_info) {
                    if ($view->getId() == $view_info->getId()) {
                        echo '<li class="cursor li_view active"><a href="index.php?v=d&p=view&id=' . $view_info->getId() . '">' . $view_info->getName() . '</a></li>';
                    } else {
                        echo '<li class="cursor li_view"><a href="index.php?v=d&p=view&id=' . $view_info->getId() . '">' . $view_info->getName() . '</a></li>';
                    }
                }
                ?>
            </ul>
        </div>
    </div>

    <div class="col-lg-10">
        <legend style="height: 35px;color : #563d7c;">Vue <?php echo $view->getName() ?> <a href="index.php?v=d&p=view_edit&id=<?php echo $view->getId(); ?>" class="btn btn-warning btn-xs pull-right" id="bt_addviewZone"><i class="fa fa-pencil"></i> {{Editer}}</a></legend>
        <div id="div_displayView"></div>
    </div>

</div>

<?php include_file('desktop', 'view', 'js'); ?>