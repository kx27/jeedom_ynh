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

class user {
    /*     * *************************Attributs****************************** */

    private $id;
    private $login;
    private $password;
    private $options;
    private $hash;
    private $rights;
    private $enable = 1;

    /*     * ***********************Methode static*************************** */

    public static function byId($_id) {
        $values = array(
            'id' => $_id,
        );
        $sql = 'SELECT ' . DB::buildField(__CLASS__) . '
                FROM user 
                WHERE id=:id';
        return DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
    }

    /**
     * Retourne un object utilisateur (si les information de connection sont valide)
     * @param string $_login nom d'utilisateur 
     * @param string $_mdp motsz de passe en sha1
     * @return user object user 
     */
    public static function connect($_login, $_mdp) {
        if (config::byKey('ldap:enable') == '1') {
            log::add("connection", "debug", __('Authentification par LDAP', __FILE__));
            $ad = self::connectToLDAP();
            if ($ad !== false) {
                log::add("connection", "debug", __('Connection au LDAP OK', __FILE__));
                $ad = ldap_connect(config::byKey('ldap:host'), config::byKey('ldap:port'));
                ldap_set_option($ad, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ad, LDAP_OPT_REFERRALS, 0);
                if (!ldap_bind($ad, 'uid=' . $_login . ',' . config::byKey('ldap:basedn'), $_mdp)) {
                    log::add("connection", "info", __('Mot de passe erroné (', __FILE__) . $_login . ')');
                    return false;
                }
                log::add("connection", "debug", __('Bind user OK', __FILE__));
                $result = ldap_search($ad, 'uid=' . $_login . ',' . config::byKey('ldap:basedn'), config::byKey('ldap:filter'));
                log::add("connection", "info", __('Recherche LDAP (', __FILE__) . $_login . ')');
                if ($result) {
                    $entries = ldap_get_entries($ad, $result);
                    if ($entries['count'] > 0) {
                        $user = self::byLogin($_login);
                        if (is_object($user)) {
                            $user->setPassword(sha1($_mdp));
                            $user->save();
                            return $user;
                        }
                        $user = new user;
                        $user->setLogin($_login);
                        $user->setPassword(sha1($_mdp));
                        $user->save();
                        log::add("connection", "info", __('Utilisateur creer depuis le LDAP : ', __FILE__) . $_login);
                        jeedom::event('user_connect');
                        return $user;
                    } else {
                        $user = self::byLogin($_login);
                        if (is_object($user)) {
                            $user->remove();
                        }
                        log::add("connection", "info", __('Utilisateur non autorisé à acceder à Jeedom (', __FILE__) . $_login . ')');
                        return false;
                    }
                } else {
                    $user = self::byLogin($_login);
                    if (is_object($user)) {
                        $user->remove();
                    }
                    log::add("connection", "info", __('Utilisateur non autorisé à acceder à Jeedom (', __FILE__) . $_login . ')');
                    return false;
                }
                return false;
            }else{
                 log::add("connection", "info", __('Impossible de se connecter au LDAP', __FILE__));
            }
        }
        $values = array(
            'login' => $_login,
            'password' => sha1($_mdp),
        );
        $sql = 'SELECT ' . DB::buildField(__CLASS__) . '
                FROM user 
                WHERE login=:login 
                    AND password=:password';
        $user = DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
        if(is_object($user)){
            jeedom::event('user_connect');
        }
        return $user;
    }

    public static function connectToLDAP() {
        $ad = ldap_connect(config::byKey('ldap:host'), config::byKey('ldap:port'));
        ldap_set_option($ad, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ad, LDAP_OPT_REFERRALS, 0);
        if (ldap_bind($ad, config::byKey('ldap:username'), config::byKey('ldap:password'))) {
            return $ad;
        }
        return false;
    }

    public static function byLogin($_login) {
        $values = array(
            'login' => $_login,
        );
        $sql = 'SELECT ' . DB::buildField(__CLASS__) . '
                FROM user 
                WHERE login=:login';
        return DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
    }

    public static function byKey($_key) {
        $values = array(
            'key' => '%' . $_key . '%',
        );
        $sql = 'SELECT ' . DB::buildField(__CLASS__) . '
                FROM user 
                WHERE options LIKE :key';
        $result = DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
        if (is_object($result)) {
            
            if ($result->getOptions('registerDevice') == $_key || $result->getOptions('registerDesktop') == $_key) {
                return $result;
            }
        }
        return null;
    }

    /**
     *
     * @return array de tous les utilisateurs 
     */
    public static function all() {
        $sql = 'SELECT ' . DB::buildField(__CLASS__) . ' 
                FROM user';
        return DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
    }

    public static function searchByRight($_rights) {
        $values = array(
            'rights' => '%"' . $_rights . '":1%',
        );
        $sql = 'SELECT ' . DB::buildField(__CLASS__) . '
                FROM user 
                WHERE rights LIKE :rights';
        return DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
    }

    /*     * *********************Methode d'instance************************* */

    public function presave() {
        if ($this->getLogin() == '') {
            throw new Exception(__('Le nom d\'utilisateur ne peut être vide', __FILE__));
        }
    }

    public function save() {
        return DB::save($this);
    }

    public function remove() {
        return DB::remove($this);
    }

    public function refresh() {
        DB::refresh($this);
    }

    /**
     *
     * @return boolean vrai si l'utilisateur est valide
     */
    public function is_Connected() {
        return (is_numeric($this->id) && $this->login != '');
    }

    /*     * **********************Getteur Setteur*************************** */

    public function getId() {
        return $this->id;
    }

    public function getLogin() {
        return $this->login;
    }

    public function getPassword() {
        return $this->password;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setLogin($login) {
        $this->login = $login;
    }

    public function setPassword($password) {
        $this->password = $password;
    }

    public function getOptions($_key = '', $_default = '') {
        return utils::getJsonAttr($this->options, $_key, $_default);
    }

    public function setOptions($_key, $_value) {
        $this->options = utils::setJsonAttr($this->options, $_key, $_value);
    }

    public function getRights($_key = '', $_default = '') {
        return utils::getJsonAttr($this->rights, $_key, $_default);
    }

    public function setRights($_key, $_value) {
        $this->rights = utils::setJsonAttr($this->rights, $_key, $_value);
    }
    
    function getEnable() {
        return $this->enable;
    }

    function setEnable($enable) {
        $this->enable = $enable;
    }

}

?>
