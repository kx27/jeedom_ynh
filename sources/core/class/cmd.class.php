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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../core/php/core.inc.php';

class cmd {
    /*     * *************************Attributs****************************** */

    protected $id;
    protected $logicalId;
    protected $eqType;
    protected $name;
    protected $order;
    protected $type;
    protected $subType;
    protected $eqLogic_id;
    protected $isHistorized = 0;
    protected $unite = '';
    protected $cache;
    protected $eventOnly = 0;
    protected $configuration;
    protected $template;
    protected $display;
    protected $_collectDate = '';
    protected $value = null;
    protected $isVisible = 1;
    protected $_internalEvent = 0;
    protected $_eqLogic = null;
    private static $_templateArray = array();

    /*     * ***********************Methode static*************************** */

    private static function cast($_inputs) {
        if (is_object($_inputs) && class_exists($_inputs->getEqType() . 'Cmd')) {
            return cast($_inputs, $_inputs->getEqType() . 'Cmd');
        }
        if (is_array($_inputs)) {
            $return = array();
            foreach ($_inputs as $input) {
                $return[] = self::cast($input);
            }
            return $return;
        }
        return $_inputs;
    }

    public static function byId($_id) {
        $values = array(
            'id' => $_id
        );
        $sql = 'SELECT ' . DB::buildField(__CLASS__) . '
                FROM cmd
                WHERE id=:id';
        return self::cast(DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__));
    }

    public static function all() {
        $sql = 'SELECT ' . DB::buildField(__CLASS__) . '
                FROM cmd
                ORDER BY id';
        $results = DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);
        $return = array();
        foreach ($results as $result) {
            $return[] = self::byId($result['id']);
        }
        return $return;
    }

    public static function allHistoryCmd($_notEventOnly = false) {
        $sql = 'SELECT ' . DB::buildField(__CLASS__, 'c') . '
                FROM cmd c
                INNER JOIN eqLogic el ON c.eqLogic_id=el.id
                INNER JOIN object ob ON el.object_id=ob.id
                WHERE isHistorized=1
                    AND type=\'info\'';
        if ($_notEventOnly) {
            $sql .= ' AND eventOnly=0';
        }
        $sql .= ' ORDER BY ob.name,el.name,c.name';
        $result1 = self::cast(DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__));
        $sql = 'SELECT ' . DB::buildField(__CLASS__, 'c') . '
                FROM cmd c
                INNER JOIN eqLogic el ON c.eqLogic_id=el.id
                WHERE el.object_id IS NULL
                    AND isHistorized=1
                    AND type=\'info\'';
        if ($_notEventOnly) {
            $sql .= ' AND eventOnly=0';
        }
        $sql .= ' ORDER BY el.name,c.name';
        $result2 = self::cast(DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__));
        return array_merge($result1, $result2);
    }

    public static function byEqLogicId($_eqLogic_id, $_type = null, $_visible = null) {
        $values = array(
            'eqLogic_id' => $_eqLogic_id
        );
        $sql = 'SELECT ' . DB::buildField(__CLASS__) . '
                FROM cmd
                WHERE eqLogic_id=:eqLogic_id';
        if ($_type != null) {
            $values['type'] = $_type;
            $sql .= ' AND `type`=:type';
        }
        if ($_visible != null) {
            $sql .= ' AND `isVisible`=1';
        }
        $sql .= ' ORDER BY `order`,`name`';
        return self::cast(DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__));
    }

    public static function byLogicalId($_logical_id, $_type = null) {
        $values = array(
            'logicalId' => $_logical_id
        );
        $sql = 'SELECT ' . DB::buildField(__CLASS__) . '
                FROM cmd
                WHERE logicalId=:logicalId';
        if ($_type != null) {
            $values['type'] = $_type;
            $sql .= ' AND `type`=:type';
        }
        $sql .= ' ORDER BY `order`';
        return self::cast(DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__));
    }

    public static function searchConfiguration($_configuration, $_type = null) {
        $values = array(
            'configuration' => '%' . $_configuration . '%'
        );
        $sql = 'SELECT ' . DB::buildField(__CLASS__) . '
                FROM cmd
                WHERE configuration LIKE :configuration';
        if ($_type != null) {
            $values['eqType'] = $_type;
            $sql .= ' AND eqType=:eqType ';
        }
        $sql .= ' ORDER BY name';
        return self::cast(DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__));
    }

    public static function byEqLogicIdAndLogicalId($_eqLogic_id, $_logicalId) {
        $values = array(
            'eqLogic_id' => $_eqLogic_id,
            'logicalId' => $_logicalId
        );
        $sql = 'SELECT ' . DB::buildField(__CLASS__) . '
                FROM cmd
                WHERE eqLogic_id=:eqLogic_id
                    AND logicalId=:logicalId';
        return self::cast(DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__));
    }

    public static function byValue($_value) {
        $values = array(
            'value' => $_value,
            'search' => '%#' . $_value . '#%'
        );
        $sql = 'SELECT ' . DB::buildField(__CLASS__) . '
                FROM cmd
                WHERE ( value=:value OR value LIKE :search)
                    AND id!=:value';
        return self::cast(DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__));
    }

    public static function byTypeEqLogicNameCmdName($_eqType_name, $_eqLogic_name, $_cmd_name) {
        $values = array(
            'eqType_name' => $_eqType_name,
            'eqLogic_name' => $_eqLogic_name,
            'cmd_name' => $_cmd_name,
        );
        $sql = 'SELECT ' . DB::buildField(__CLASS__, 'c') . '
                FROM cmd c
                    INNER JOIN eqLogic el ON c.eqLogic_id=el.id
                WHERE c.name=:cmd_name
                    AND el.name=:eqLogic_name
                    AND el.eqType_name=:eqType_name';
        return self::cast(DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__));
    }

    public static function byEqLogicIdCmdName($_eqLogic_id, $_cmd_name) {
        $values = array(
            'eqLogic_id' => $_eqLogic_id,
            'cmd_name' => $_cmd_name,
        );
        $sql = 'SELECT ' . DB::buildField(__CLASS__, 'c') . '
                FROM cmd c
                WHERE c.name=:cmd_name
                    AND c.eqLogic_id=:eqLogic_id';
        return self::cast(DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__));
    }

    public static function byObjectNameEqLogicNameCmdName($_object_name, $_eqLogic_name, $_cmd_name) {
        $values = array(
            'eqLogic_name' => $_eqLogic_name,
            'cmd_name' => html_entity_decode($_cmd_name),
        );

        if ($_object_name == __('Aucun', __FILE__)) {
            $sql = 'SELECT ' . DB::buildField(__CLASS__, 'c') . '
                    FROM cmd c
                        INNER JOIN eqLogic el ON c.eqLogic_id=el.id
                    WHERE c.name=:cmd_name
                        AND el.name=:eqLogic_name
                        AND el.object_id IS NULL';
        } else {
            $values['object_name'] = $_object_name;
            $sql = 'SELECT ' . DB::buildField(__CLASS__, 'c') . '
                    FROM cmd c
                        INNER JOIN eqLogic el ON c.eqLogic_id=el.id
                        INNER JOIN object ob ON el.object_id=ob.id
                    WHERE c.name=:cmd_name
                        AND el.name=:eqLogic_name
                        AND ob.name=:object_name';
        }
        return self::cast(DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__));
    }

    public static function byObjectNameCmdName($_object_name, $_cmd_name) {
        $values = array(
            'object_name' => $_object_name,
            'cmd_name' => $_cmd_name,
        );
        $sql = 'SELECT ' . DB::buildField(__CLASS__, 'c') . '
                FROM cmd c
                    INNER JOIN eqLogic el ON c.eqLogic_id=el.id
                    INNER JOIN object ob ON el.object_id=ob.id
                WHERE c.name=:cmd_name
                    AND ob.name=:object_name';
        return self::cast(DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__));
    }

    public static function byTypeSubType($_type, $_subType = '') {
        $values = array(
            'type' => $_type,
        );
        $sql = 'SELECT ' . DB::buildField(__CLASS__, 'c') . '
                FROM cmd c
                WHERE c.type=:type';
        if ($_subType != '') {
            $values['subtype'] = $_subType;
            $sql .= ' AND c.subtype=:subtype';
        }
        return self::cast(DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__));
    }

    public static function collect() {
        $cmd = null;
        foreach (cache::search('collect') as $cache) {
            $cmd = self::byId($cache->getValue());
            if (is_object($cmd) && $cmd->getEqLogic()->getIsEnable() == 1 && $cmd->getEventOnly() == 0) {
                $cmd->execCmd(null, 0);
            }
        }
    }

    public static function cmdToHumanReadable($_input) {
        if (is_object($_input)) {
            $reflections = array();
            $uuid = spl_object_hash($_input);
            if (!isset($reflections[$uuid])) {
                $reflections[$uuid] = new ReflectionClass($_input);
            }
            $reflection = $reflections[$uuid];
            $properties = $reflection->getProperties();
            foreach ($properties as $property) {
                $property->setAccessible(true);
                $value = $property->getValue($_input);
                $property->setValue($_input, self::cmdToHumanReadable($value));
                $property->setAccessible(false);
            }
            return $_input;
        }
        if (is_array($_input)) {
            foreach ($_input as $key => $value) {
                $_input[$key] = self::cmdToHumanReadable($value);
            }
            return $_input;
        }
        $text = $_input;
        preg_match_all("/#([0-9]*)#/", $text, $matches);
        foreach ($matches[1] as $cmd_id) {
            if (is_numeric($cmd_id)) {
                $cmd = self::byId($cmd_id);
                if (is_object($cmd)) {
                    $text = str_replace('#' . $cmd_id . '#', '#' . $cmd->getHumanName() . '#', $text);
                }
            }
        }
        return $text;
    }

    public static function humanReadableToCmd($_input) {
        $isJson = false;
        if (is_json($_input)) {
            $isJson = true;
            $_input = json_decode($_input, true);
        }
        if (is_object($_input)) {
            $reflections = array();
            $uuid = spl_object_hash($_input);
            if (!isset($reflections[$uuid])) {
                $reflections[$uuid] = new ReflectionClass($_input);
            }
            $reflection = $reflections[$uuid];
            $properties = $reflection->getProperties();
            foreach ($properties as $property) {
                $property->setAccessible(true);
                $value = $property->getValue($_input);
                $property->setValue($_input, self::humanReadableToCmd($value));
                $property->setAccessible(false);
            }
            return $_input;
        }
        if (is_array($_input)) {
            foreach ($_input as $key => $value) {
                $_input[$key] = self::humanReadableToCmd($value);
            }
            if ($isJson) {
                return json_encode($_input, JSON_UNESCAPED_UNICODE);
            }
            return $_input;
        }
        $text = $_input;

        preg_match_all("/#\[(.*?)\]\[(.*?)\]\[(.*?)\]#/", $text, $matches);
        if (count($matches) == 4) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                if (isset($matches[1][$i]) && isset($matches[2][$i]) && isset($matches[3][$i])) {
                    $cmd = self::byObjectNameEqLogicNameCmdName($matches[1][$i], $matches[2][$i], $matches[3][$i]);
                    if (is_object($cmd)) {
                        $text = str_replace($matches[0][$i], '#' . $cmd->getId() . '#', $text);
                    }
                }
            }
        }

        return $text;
    }

    public static function byString($_string) {
        $cmd = self::byId(str_replace('#', '', self::humanReadableToCmd($_string)));
        if (!is_object($cmd)) {
            throw new Exception(__('La commande n\'a pu être trouvée : ', __FILE__) . $_string . __(' => ', __FILE__) . self::humanReadableToCmd($_string));
        }
        return $cmd;
    }

    public static function cmdToValue($_input) {
        if (is_object($_input)) {
            $reflections = array();
            $uuid = spl_object_hash($_input);
            if (!isset($reflections[$uuid])) {
                $reflections[$uuid] = new ReflectionClass($_input);
            }
            $reflection = $reflections[$uuid];
            $properties = $reflection->getProperties();
            foreach ($properties as $property) {
                $property->setAccessible(true);
                $value = $property->getValue($_input);
                $property->setValue($_input, self::cmdToValue($value));
                $property->setAccessible(false);
            }
            return $_input;
        }
        if (is_array($_input)) {
            foreach ($_input as $key => $value) {
                $_input[$key] = self::cmdToValue($value);
            }
            return $_input;
        }
        $text = $_input;
        preg_match_all("/#([0-9]*)#/", $text, $matches);
        foreach ($matches[1] as $cmd_id) {
            if (is_numeric($cmd_id)) {
                $cmd = self::byId($cmd_id);
                if (is_object($cmd) && $cmd->getType() == 'info') {
                    $cmd_value = $cmd->execCmd();
                    if ($cmd->getSubtype() == "string" && substr($cmd_value, 0, 1) != '"' && substr($cmd_value, -1) != '"') {
                        $cmd_value = '"' . $cmd_value . '"';
                    }
                    $text = str_replace('#' . $cmd_id . '#', $cmd_value, $text);
                }
            }
        }
        return $text;
    }

    public static function allType() {
        $sql = 'SELECT distinct(type) as type
                FROM cmd';
        return DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);
    }

    public static function allSubType($_type = '') {
        $values = array();
        $sql = 'SELECT distinct(subType) as subtype';
        if ($_type != '') {
            $values['type'] = $_type;
            $sql .= ' WHERE type=:type';
        }
        $sql .= ' FROM cmd';
        return DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL);
    }

    public static function allUnite() {
        $sql = 'SELECT distinct(unite) as unite
                FROM cmd';
        return DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);
    }

    public static function convertColor($_color) {
        $colors = config::byKey('convertColor');
        if (isset($colors[$_color])) {
            return $colors[$_color];
        }
        throw new Exception(__('Impossible de traduire la couleur en code hexadecimal :', __FILE__) . $_color);
    }

    public static function availableWidget($_version) {
        $path = dirname(__FILE__) . '/../template/' . $_version;
        $files = ls($path, 'cmd.*', false, array('files', 'quiet'));
        $return = array();
        foreach ($files as $file) {
            $informations = explode('.', $file);
            if (!isset($return[$informations[1]])) {
                $return[$informations[1]] = array();
            }
            if (!isset($return[$informations[1]][$informations[2]])) {
                $return[$informations[1]][$informations[2]] = array();
            }
            $return[$informations[1]][$informations[2]][] = array('name' => $informations[3]);
        }
        foreach (plugin::listPlugin(true) as $plugin) {
            $path = dirname(__FILE__) . '/../../plugins/' . $plugin->getId() . '/core/template/' . $_version;
            $files = ls($path, 'cmd.*', false, array('files', 'quiet'));
            foreach ($files as $file) {
                $informations = explode('.', $file);
                if (count($informations) > 3) {
                    if (!isset($return[$informations[1]])) {
                        $return[$informations[1]] = array();
                    }
                    if (!isset($return[$informations[1]][$informations[2]])) {
                        $return[$informations[1]][$informations[2]] = array();
                    }
                    $return[$informations[1]][$informations[2]][] = array('name' => $informations[3]);
                }
            }
        }
        return $return;
    }

    public static function returnState($_options) {
        $cmd = cmd::byId($_options['cmd_id']);
        if (is_object($cmd)) {
            $cmd->event($cmd->getConfiguration('returnStateValue', 0));
        }
    }

    /*     * *********************Methode d'instance************************* */

    public function formatValue($_value) {
        if (trim($_value) == '') {
            return '';
        }
        if (strpos('error', $_value) !== false) {
            return $_value;
        }
        if ($this->getType() == 'info') {
            switch ($this->getSubType()) {
                case 'binary':
                    $value = strtolower($_value);
                    if ($value == 'on' || $value == 'high') {
                        return 1;
                    }
                    if ($value == 'off' || $value == 'low') {
                        return 0;
                    }
                    if ((is_numeric(intval($_value)) && intval($_value) > 1) || $_value || $_value == 1) {
                        return 1;
                    }
                    return 0;
                case 'numeric':
                    if ($this->getConfiguration('calculValueOffset') != '') {
                        try {
                            $test = new evaluate();
                            $_value = $test->Evaluer(str_replace('#value#', $_value, $this->getConfiguration('calculValueOffset')));
                        } catch (Exception $ex) {
                            
                        }
                    }
                    return floatval($_value);
            }
        }
        return $_value;
    }

    public function getLastValue() {
        return $this->getConfiguration('lastCmdValue', null);
    }

    public function dontRemoveCmd() {
        return false;
    }

    public function getTableName() {
        return 'cmd';
    }

    public function save() {
        if ($this->getName() == '') {
            throw new Exception(__('Le nom de la commande ne peut être vide :', __FILE__) . print_r($this, true));
        }
        if ($this->getType() == '') {
            throw new Exception(__('Le type de la commande ne peut être vide :', __FILE__) . print_r($this, true));
        }
        if ($this->getSubType() == '') {
            throw new Exception(__('Le sous-type de la commande ne peut être vide :', __FILE__) . print_r($this, true));
        }
        if ($this->getEqLogic_id() == '') {
            throw new Exception(__('Vous ne pouvez créer une commande sans la rattacher à un équipement', __FILE__));
        }
        if ($this->getEqType() == '') {
            $this->setEqType($this->getEqLogic()->getEqType_name());
        }
        if ($this->getInternalEvent() == 1) {
            $internalEvent = new internalEvent();
            if ($this->getId() == '') {
                $internalEvent->setEvent('create::cmd');
            } else {
                $internalEvent->setEvent('update::cmd');
            }
        }
        DB::save($this);
        if (isset($internalEvent)) {
            $internalEvent->setOptions('id', $this->getId());
            $internalEvent->save();
        }

        $mc = cache::byKey('cmd' . $this->getId());
        if ($mc->getLifetime() != $this->getCacheLifetime()) {
            $mc->remove();
        }
        return true;
    }

    public function refresh() {
        DB::refresh($this);
    }

    public function remove() {
        viewData::removeByTypeLinkId('cmd', $this->getId());
        dataStore::removeByTypeLinkId('cmd', $this->getId());
        $internalEvent = new internalEvent();
        $internalEvent->setEvent('remove::cmd');
        $internalEvent->setOptions('id', $this->getId());
        DB::remove($this);
        $internalEvent->save();
    }

    public function execute($_options = array()) {
        return false;
    }

    /**
     * 
     * @param type $_options
     * @param type $cache 0 = ignorer le cache , 1 = mode normale, 2 = cache utilisé meme si expiré (puis marqué à recollecter)
     * @return command result
     * @throws Exception
     */
    public function execCmd($_options = null, $cache = 1, $_sendNodeJsEvent = true) {
        if ($this->getEventOnly() == 1) {
            $cache = 2;
        }
        if ($this->getType() == 'info' && $cache != 0) {
            $mc = cache::byKey('cmd' . $this->getId(), ($cache == 2) ? true : false);
            if ($cache == 2 || !$mc->hasExpired()) {
                if ($mc->hasExpired()) {
                    $this->setCollect(1);
                }
                $this->setCollectDate($mc->getOptions('collectDate', $mc->getDatetime()));
                return $mc->getValue();
            }
        }
        $eqLogic = $this->getEqLogic();
        if (!is_object($eqLogic) || $eqLogic->getIsEnable() != 1) {
            throw new Exception(__('Equipement desactivé impossible d\éxecuter la commande : ' . $this->getHumanName(), __FILE__));
        }
        try {
            if ($_options !== null && $_options !== '') {
                $options = self::cmdToValue($_options);
                if (is_json($_options)) {
                    $options = json_decode($_options, true);
                }
            } else {
                $options = null;
            }
            if (isset($options['color'])) {
                $options['color'] = str_replace('"', '', $options['color']);
            }
            if ($this->getSubType() == 'color' && isset($options['color']) && substr($options['color'], 0, 1) != '#') {
                $options['color'] = cmd::convertColor($options['color']);
            }
            $value = $this->formatValue($this->execute($options));
        } catch (Exception $e) {
            //Si impossible de contacter l'équipement
            $type = $eqLogic->getEqType_name();
            if ($eqLogic->getConfiguration('nerverFail') != 1) {
                $numberTryWithoutSuccess = $eqLogic->getStatus('numberTryWithoutSuccess', 0);
                $eqLogic->setStatus('numberTryWithoutSuccess', $numberTryWithoutSuccess);
                if ($numberTryWithoutSuccess >= config::byKey('numberOfTryBeforeEqLogicDisable')) {
                    $message = 'Désactivation de <a href="' . $eqLogic->getLinkToConfiguration() . '">' . $eqLogic->getName();
                    $message .= ($eqLogic->getEqReal_id() != '') ? ' (' . $eqLogic->getEqReal()->getName() . ') ' : '';
                    $message .= '</a> car il n\'a pas répondu ou mal répondu lors des 3 derniers essais';
                    message::add($type, $message);
                    $eqLogic->setIsEnable(0);
                    $eqLogic->save();
                }
            }
            log::add($type, 'error', __('Erreur sur ', __FILE__) . $eqLogic->getName() . ' : ' . $e->getMessage());
            throw $e;
        }
        if ($this->getType() == 'info' && $value !== false) {
            cache::set('cmd' . $this->getId(), $value, $this->getCacheLifetime(), array('collectDate' => $this->getCollectDate()));
            if ($this->getCollectDate() == '') {
                $this->setCollectDate(date('Y-m-d H:i:s'));
            }
            $this->setCollect(0);
            $nodeJs = array(
                array(
                    'cmd_id' => $this->getId(),
                )
            );
            foreach (self::byValue($this->getId()) as $cmd) {
                $nodeJs[] = array('cmd_id' => $cmd->getId());
            }
            nodejs::pushUpdate('eventCmd', $nodeJs);
        }
        if (!is_array($value) && strpos($value, 'error') === false) {
            if ($eqLogic->getStatus('numberTryWithoutSuccess') != 0) {
                $eqLogic->setStatus('numberTryWithoutSuccess', 0);
            }
            $eqLogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
        }
        if ($this->getType() == 'action' && $options !== null) {
            if (isset($options['slider'])) {
                $this->setConfiguration('lastCmdValue', $options['slider']);
                $this->save();
            }
            if (isset($options['color'])) {
                $this->setConfiguration('lastCmdValue', $options['color']);
                $this->save();
            }
        }
        if ($this->getType() == 'action' && $this->getConfiguration('updateCmdId') != '') {
            $cmd = cmd::byId($this->getConfiguration('updateCmdId'));
            if (is_object($cmd)) {
                $value = $this->getConfiguration('updateCmdToValue');
                switch ($this->getSubType()) {
                    case 'slider':
                        $value = str_replace('#slider#', $_options['slider'], $value);
                        break;
                    case 'color':
                        $value = str_replace('#color#', $_options['color'], $value);
                        break;
                }
                $cmd->event($value);
            }
        }
        return $value;
    }

    public function toHtml($_version = 'dashboard', $options = '', $_cmdColor = null, $_cache = 2) {
        $version = jeedom::versionAlias($_version);
        $html = '';
        $template_name = 'cmd.' . $this->getType() . '.' . $this->getSubType() . '.' . $this->getTemplate($version, 'default');
        if (!isset(self::$_templateArray[$version . '::' . $template_name])) {
            if ($this->getTemplate($version, 'default') != 'default') {
                $template = getTemplate('core', $version, $template_name, 'widget');
                $findWidgetPlugin = false;
                if ($template == '') {
                    foreach (plugin::listPlugin(true) as $plugin) {
                        $template = getTemplate('core', $version, $template_name, $plugin->getId());
                        if ($template != '') {
                            break;
                        }
                        if ($plugin->getId() == 'widget') {
                            $findWidgetPlugin = true;
                        }
                    }
                }
                if ($findWidgetPlugin && $template == '' && config::byKey('market::autoInstallMissingWidget') == 1) {
                    try {
                        $market = market::byLogicalId(str_replace('.cmd', '', $version . '.' . $template_name));
                        if (is_object($market)) {
                            $market->install();
                            $template = getTemplate('core', $version, $template_name, 'widget');
                        }
                    } catch (Exception $e) {
                        $this->setTemplate($version, 'default');
                        $this->save();
                    }
                }
                if ($template == '') {
                    $template_name = 'cmd.' . $this->getType() . '.' . $this->getSubType() . '.default';
                    $template = getTemplate('core', $version, $template_name);
                }
            } else {
                $template = getTemplate('core', $version, $template_name);
            }
            self::$_templateArray[$version . '::' . $template_name] = $template;
        } else {
            $template = self::$_templateArray[$version . '::' . $template_name];
        }
        $replace = array(
            '#id#' => $this->getId(),
            '#name#' => ($this->getDisplay('icon') != '') ? $this->getDisplay('icon') : $this->getName(),
            '#history#' => '',
            '#displayHistory#' => 'display : none;',
            '#unite#' => $this->getUnite(),
            '#minValue#' => $this->getConfiguration('minValue', 0),
            '#maxValue#' => $this->getConfiguration('maxValue', 100)
        );
        if (($_version == 'dview' || $_version == 'mview') && $this->getDisplay('doNotShowNameOnView') == 1) {
            $replace['#name#'] = '';
        }
        if (($_version == 'mobile' || $_version == 'dashboard') && $this->getDisplay('doNotShowNameOnDashboard') == 1) {
            $replace['#name#'] = '';
        }
        if ($_cmdColor == null && $version != 'scenario') {
            $eqLogic = $this->getEqLogic();
            $vcolor = ($version == 'mobile') ? 'mcmdColor' : 'cmdColor';
            if ($eqLogic->getPrimaryCategory() == '') {
                $replace['#cmdColor#'] = '';
            } else {
                $replace['#cmdColor#'] = jeedom::getConfiguration('eqLogic:category:' . $eqLogic->getPrimaryCategory() . ':' . $vcolor);
            }
        } else {
            $replace['#cmdColor#'] = $_cmdColor;
        }
        if ($this->getType() == 'info') {
            $replace['#state#'] = '';
            $replace['#tendance#'] = '';
            $replace['#state#'] = trim($this->execCmd(null, $_cache));
            if ($this->getSubType() == 'binary' && $this->getDisplay('invertBinary') == 1) {
                $replace['#state#'] = ($replace['#state#'] == 1) ? 0 : 1;
            }
            $replace['#collectDate#'] = $this->getCollectDate();
            if ($this->getIsHistorized() == 1) {
                $replace['#history#'] = 'history cursor';
                if (config::byKey('displayStatsWidget') == 1 && strpos($template, '#displayHistory#') !== false) {
                    $startHist = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' -' . config::byKey('historyCalculPeriod') . ' hour'));
                    $replace['#displayHistory#'] = '';
                    $historyStatistique = $this->getStatistique($startHist, date('Y-m-d H:i:s'));
                    $replace['#averageHistoryValue#'] = round($historyStatistique['avg'], 1);
                    $replace['#minHistoryValue#'] = round($historyStatistique['min'], 1);
                    $replace['#maxHistoryValue#'] = round($historyStatistique['max'], 1);
                    $tendance = $this->getTendance($startHist, date('Y-m-d H:i:s'));
                    $replace['#tendance#'] = 'fa fa-minus';
                    if ($tendance > config::byKey('historyCalculTendanceThresholddMax')) {
                        $replace['#tendance#'] = 'fa fa-arrow-up';
                    }
                    if ($tendance < config::byKey('historyCalculTendanceThresholddMin')) {
                        $replace['#tendance#'] = 'fa fa-arrow-down';
                    }
                }
            }
            $parameters = $this->getDisplay('parameters');
            if (is_array($parameters)) {
                foreach ($parameters as $key => $value) {
                    $replace['#' . $key . '#'] = $value;
                }
            }
            return template_replace($replace, $template);
        } else {
            $cmdValue = $this->getCmdValue();
            if (is_object($cmdValue) && $cmdValue->getType() == 'info') {
                $replace['#state#'] = $cmdValue->execCmd(null, 2);
                $replace['#valueName#'] = $cmdValue->getName();
            } else {
                $replace['#state#'] = ($this->getLastValue() != null) ? $this->getLastValue() : '';
                $replace['#valueName#'] = $this->getName();
            }
            $parameters = $this->getDisplay('parameters');
            if (is_array($parameters)) {
                foreach ($parameters as $key => $value) {
                    $replace['#' . $key . '#'] = $value;
                }
            }
            $html .= template_replace($replace, $template);
            if (trim($html) == '') {
                return $html;
            }
            if ($options != '') {
                $options = self::cmdToHumanReadable($options);
                if (is_json($options)) {
                    $options = json_decode($options, true);
                }
                if (is_array($options)) {
                    foreach ($options as $key => $value) {
                        $replace['#' . $key . '#'] = $value;
                    }
                    $html = template_replace($replace, $html);
                }
            }
            return $html;
        }
    }

    public function event($_value, $_loop = 0) {
        if (trim($_value) === '') {
            return;
        }
        $eqLogic = $this->getEqLogic();
        if (!is_object($eqLogic) || $eqLogic->getIsEnable() == 0) {
            return;
        }
        if ($this->getType() != 'info' || $_loop > 5) {
            return;
        }

        $_loop++;
        $collectDate = ($this->getCollectDate() != '' ) ? strtotime($this->getCollectDate()) : '';
        $nowtime = strtotime('now');
        if ($this->getCollectDate() != '' && (($nowtime - $collectDate) > 3600 || ($nowtime + 300 ) < $collectDate)) {
            return;
        }
        $value = $this->formatValue($_value);
        log::add('cmd', 'event', 'Evènement sur la commande : ' . $this->getHumanName() . ' (' . $this->getId() . ') => ' . $value . '(' . $_value . ')');
        cache::set('cmd' . $this->getId(), $value, $this->getCacheLifetime(), array('collectDate' => $this->getCollectDate()));
        $this->setCollect(0);
        $nodeJs = array(array('cmd_id' => $this->getId()));
        foreach (self::byValue($this->getId()) as $cmd) {
            if ($cmd->getType() == 'action') {
                $nodeJs[] = array('cmd_id' => $cmd->getId());
            } else {
                $cmd->event($cmd->execute(), $_loop);
            }
        }
        nodejs::pushUpdate('eventCmd', $nodeJs);
        scenario::check($this);
        listener::check($this->getId(), $value);
        if (strpos($_value, 'error') === false) {
            $eqLogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
            $this->addHistoryValue($value, $this->getCollectDate());
        }
        $internalEvent = new internalEvent();
        $internalEvent->setEvent('event::cmd');
        $internalEvent->setOptions('id', $this->getId());
        $internalEvent->setOptions('value', $value);
        if ($this->getCollectDate() != '') {
            $internalEvent->setDatetime($this->getCollectDate());
        }
        $internalEvent->save();
        $this->checkReturnState($value);
    }

    public function checkReturnState($_value) {
        if (is_numeric($this->getConfiguration('returnStateTime')) && $this->getConfiguration('returnStateTime') > 0 && $_value != $this->getConfiguration('returnStateValue') && trim($this->getConfiguration('returnStateValue')) != '') {
            $cron = cron::byClassAndFunction('cmd', 'returnState', array('cmd_id' => intval($this->getId())));
            if (!is_object($cron)) {
                $cron = new cron();
            }
            $cron->setClass('cmd');
            $cron->setFunction('returnState');
            $cron->setOnce(1);
            $cron->setOption(array('cmd_id' => intval($this->getId())));
            $next = strtotime('+ ' . ($this->getConfiguration('returnStateTime') + 1) . ' minutes ' . date('Y-m-d H:i:s'));
            $schedule = date('i', $next) . ' ' . date('H', $next) . ' ' . date('d', $next) . ' ' . date('m', $next) . ' * ' . date('Y', $next);
            $cron->setSchedule($schedule);
            $cron->setLastRun(date('Y-m-d H:i:s'));
            $cron->save();
        }
    }

    public function invalidCache() {
        $mc = cache::byKey('cmd' . $this->getId());
        $mc->invalid();
    }

    public function emptyHistory() {
        return history::emptyHistory($this->getId());
    }

    public function addHistoryValue($_value, $_datetime = '') {
        if ($_value !== '' && $this->getIsHistorized() == 1 && $this->getType() == 'info' && $_value <= $this->getConfiguration('maxValue', $_value) && $_value >= $this->getConfiguration('minValue', $_value)) {
            $hitory = new history();
            $hitory->setCmd_id($this->getId());
            $hitory->setValue($_value);
            $hitory->setDatetime($_datetime);
            return $hitory->save($this);
        }
    }

    public function getUsedBy() {
        $return = array();
        $return['cmd'] = self::searchConfiguration('#' . $this->getId() . '#');
        $return['eqLogic'] = eqLogic::searchConfiguration('#' . $this->getId() . '#');
        $return['scenario'] = scenario::byUsedCommand($this->getId());
        $return['interact'] = interactDef::byUsedCommand($this->getId());
        return $return;
    }

    public function getStatistique($_startTime, $_endTime) {
        return history::getStatistique($this->getId(), $_startTime, $_endTime);
    }

    public function getTendance($_startTime, $_endTime) {
        return history::getTendance($this->getId(), $_startTime, $_endTime);
    }

    public function getCacheLifetime() {
        if ($this->getEventOnly() == 1) {
            return 0;
        }
        if ($this->getCache('enable', 0) == 0 && $this->getCache('lifetime') == '') {
            return 10;
        }
        $lifetime = $this->getCache('lifetime', config::byKey('lifeTimeMemCache'));
        return ($lifetime < 10) ? 10 : $lifetime;
    }

    public function getCmdValue() {
        if (is_numeric($this->getValue())) {
            return self::byId($this->getValue());
        }
        return false;
    }

    public function getHumanName() {
        $name = '';
        $eqLogic = $this->getEqLogic();
        if (is_object($eqLogic)) {
            $name .= $eqLogic->getHumanName();
        }
        $name .= '[' . $this->getName() . ']';
        return $name;
    }

    public function getHistory($_dateStart = null, $_dateEnd = null) {
        return history::all($this->id, $_dateStart, $_dateEnd);
    }

    public function getPluralityHistory($_dateStart = null, $_dateEnd = null, $_period = 'day') {
        return history::getPlurality($this->id, $_dateStart, $_dateEnd, $_period);
    }

    public function setCollect($collect) {
        if ($collect == 1) {
            cache::set('collect' . $this->getId(), $this->getId());
        } else {
            $cache = cache::byKey('collect' . $this->getId());
            $cache->remove();
        }
    }

    /*     * **********************Getteur Setteur*************************** */

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getType() {
        return $this->type;
    }

    public function getSubType() {
        return $this->subType;
    }

    public function getEqType_name() {
        return $this->eqType;
    }

    public function getEqLogic_id() {
        return $this->eqLogic_id;
    }

    public function getIsHistorized() {
        return $this->isHistorized;
    }

    public function getUnite() {
        return $this->unite;
    }

    public function getEqLogic() {
        if ($this->_eqLogic == null) {
            $this->_eqLogic = eqLogic::byId($this->eqLogic_id);
        }
        return $this->_eqLogic;
    }

    public function getEventOnly() {
        return $this->eventOnly;
    }

    public function setId($id = '') {
        if ($id != $this->getId()) {
            $this->setInternalEvent(1);
        }
        $this->id = $id;
    }

    public function setName($name) {
        $name = str_replace(array('&', '#', ']', '[', '%', "'", "+"), '', $name);
        if ($name != $this->getName()) {
            $this->setInternalEvent(1);
        }
        $this->name = $name;
    }

    public function setType($type) {
        if ($type != $this->getType()) {
            $this->setInternalEvent(1);
        }
        $this->type = $type;
    }

    public function setSubType($subType) {
        if ($subType != $this->getSubType()) {
            $this->setInternalEvent(1);
        }
        $this->subType = $subType;
    }

    public function setEqLogic_id($eqLogic_id) {
        if ($eqLogic_id != $this->getEqLogic_id()) {
            $this->setInternalEvent(1);
        }
        $this->eqLogic_id = $eqLogic_id;
    }

    public function setIsHistorized($isHistorized) {
        if ($isHistorized != $this->getIsHistorized()) {
            $this->setInternalEvent(1);
        }
        $this->isHistorized = $isHistorized;
    }

    public function setUnite($unite) {
        if ($unite != $this->getUnite()) {
            $this->setInternalEvent(1);
        }
        $this->unite = $unite;
    }

    public function setEventOnly($eventOnly) {
        if ($eventOnly != $this->getEventOnly()) {
            $this->setInternalEvent(1);
        }
        $this->eventOnly = $eventOnly;
    }

    public function getCache($_key = '', $_default = '') {
        return utils::getJsonAttr($this->cache, $_key, $_default);
    }

    public function setCache($_key, $_value) {
        $this->cache = utils::setJsonAttr($this->cache, $_key, $_value);
    }

    public function getTemplate($_key = '', $_default = '') {
        return utils::getJsonAttr($this->template, $_key, $_default);
    }

    public function setTemplate($_key, $_value) {
        $this->template = utils::setJsonAttr($this->template, $_key, $_value);
    }

    public function getConfiguration($_key = '', $_default = '') {
        return utils::getJsonAttr($this->configuration, $_key, $_default);
    }

    public function setConfiguration($_key, $_value) {
        $this->configuration = utils::setJsonAttr($this->configuration, $_key, $_value);
    }

    public function getDisplay($_key = '', $_default = '') {
        return utils::getJsonAttr($this->display, $_key, $_default);
    }

    public function setDisplay($_key, $_value) {
        $this->display = utils::setJsonAttr($this->display, $_key, $_value);
    }

    public function getCollectDate() {
        return $this->_collectDate;
    }

    public function setCollectDate($_collectDate) {
        $this->_collectDate = $_collectDate;
    }

    public function getValue() {
        return $this->value;
    }

    public function setValue($value) {
        $this->value = $value;
    }

    public function getIsVisible() {
        return $this->isVisible;
    }

    public function setIsVisible($isVisible) {
        $this->isVisible = $isVisible;
    }

    public function getInternalEvent() {
        return $this->_internalEvent;
    }

    public function setInternalEvent($_internalEvent) {
        $this->_internalEvent = $_internalEvent;
    }

    public function getOrder() {
        return $this->order;
    }

    public function setOrder($order) {
        $this->order = $order;
    }

    public function getLogicalId() {
        return $this->logicalId;
    }

    public function setLogicalId($logicalId) {
        $this->logicalId = $logicalId;
    }

    public function getEqType() {
        return $this->eqType;
    }

    public function setEqType($eqType) {
        $this->eqType = $eqType;
    }

}

?>
