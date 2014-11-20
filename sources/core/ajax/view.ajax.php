<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

try {
    require_once(dirname(__FILE__) . '/../../core/php/core.inc.php');
    include_file('core', 'authentification', 'php');

    if (!isConnect()) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    if (init('action') == 'edit') {
        $view = view::byId(init('id'));
        if (!is_object($view)) {
            $view = new view();
        }
        $view->setName(init('name'));
        $view->save();
        ajax::success(array('id' => $view->getId()));
    }

    if (init('action') == 'remove') {
        $view = view::byId(init('id'));
        if (!is_object($view)) {
            throw new Exception(__('Vue non trouvé. Vérifier l\'id', __FILE__));
        }
        $view->remove();
        ajax::success();
    }

    if (init('action') == 'all') {
        ajax::success(utils::o2a(view::all()));
    }

    if (init('action') == 'get') {
        if (init('id') == 'all' || is_json(init('id'))) {
            if (is_json(init('id'))) {
                $view_ajax = json_decode(init('id'), true);
                $views = array();
                foreach ($view_ajax as $id) {
                    $views[] = view::byId($id);
                }
            } else {
                $views = view::all();
            }
            $return = array();
            foreach (view::all() as $view) {
                $return[$view->getId()] = $view->toAjax();
            }
            ajax::success($return);
        } else {
            $view = view::byId(init('id'));
            if (!is_object($view)) {
                throw new Exception(__('Vue non trouvé. Vérifier l\'id', __FILE__));
            }
            ajax::success($view->toAjax());
        }
    }


    if (init('action') == 'save') {
        $view = view::byId(init('view_id'));
        if (!is_object($view)) {
            throw new Exception(__('Vue non trouvé. Vérifier l\'id', __FILE__));
        }
        $view->removeviewZone();
        $viewZones = json_decode(init('viewZones'), true);

        foreach ($viewZones as $viewZone_info) {
            $viewZone = new viewZone();
            $viewZone->setView_id($view->getId());
            utils::a2o($viewZone, $viewZone_info);
            $viewZone->save();
            if (isset($viewZone_info['viewData'])) {
                $order = 0;
                foreach ($viewZone_info['viewData'] as $viewData_info) {
                    $viewData = new viewData();
                    $viewData->setviewZone_id($viewZone->getId());
                    $viewData->setOrder($order);
                    utils::a2o($viewData, $viewData_info);
                    $viewData->save();
                    $order++;
                }
            }
        }
        ajax::success();
    }

    if (init('action') == 'getEqLogicviewZone') {
        $viewZone = viewZone::byId(init('viewZone_id'));
        if (!is_object($viewZone)) {
            throw new Exception(__('Vue non trouvé. Vérifier l\'id', __FILE__));
        }
        $return = utils::o2a($viewZone);
        $return['eqLogic'] = array();
        foreach ($viewZone->getviewData() as $viewData) {
            $infoViewDatat = utils::o2a($viewData->getLinkObject());
            $infoViewDatat['html'] = $viewData->getLinkObject()->toHtml(init('version'));
            $return['viewData'][] = $infoViewDatat;
        }
        ajax::success($return);
    }

    throw new Exception(__('Aucune methode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}
?>
