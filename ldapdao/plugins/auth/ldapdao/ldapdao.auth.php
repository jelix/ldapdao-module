<?php
/**
*/

/**
* LDAP authentification driver for authentification information stored in LDAP server
* and manage user locally with a dao
* @package    jelix
* @subpackage auth_driver
*/
class ldapdaoAuthDriver extends jAuthDriverBase implements jIAuthDriver {

    /**
    * default user attributes list
    * @var array
    */
    protected $_default_attributes = array("cn"=>"lastname",
                                           "name"=>"firstname");

    function __construct($params){

        if (!extension_loaded('ldap')) {
            throw new jException('ldapdao~errors.extension.unloaded');
        }

        parent::__construct($params);

        // default ldap parameters
        $_default_params = array(
            'hostname'      =>  'localhost',
            'port'          =>  389,
            'ldapAdminUserDn'      =>  null,
            'ldapAdminPassword'      =>  null,
            'protocolVersion'   =>  3,
            'uidProperty'       =>  'cn',
            'searchUserListReturnsUser' => 1,
            'searchUserListUserUidAttribute' => ''
        );

        // iterate each default parameter and apply it to actual params if missing in $params.
        foreach($_default_params as $name => $value) {
            if (!isset($this->_params[$name]) || $this->_params[$name] == '') {
                $this->_params[$name] = $value;
            }
        }

        if (!isset($this->_params['searchBaseDN']) || $this->_params['searchBaseDN'] == '') {
            throw new jException('ldapdao~errors.search.base.missing');
        }

        if (!isset($this->_params['searchUserListFilter']) || $this->_params['searchUserListFilter'] == '') {
            throw new jException('ldapdao~errors.search.filter.missing');
        }

        if (!isset($this->_params['searchAttributes']) || $this->_params['searchAttributes'] == '') {
            $this->_params['searchAttributes'] = $this->_default_attributes;
        }
        else {
            $attrs = explode(",", $this->_params['searchAttributes']);
            $this->_params['searchAttributes'] = array();
            foreach($attrs as $attr) {
                if (strpos($attr, ':') === false) {
                    $attr = trim($attr);
                    $this->_params['searchAttributes'][$attr] = $attr;
                }
                else {
                    $attr = explode(':', $attr);
                    $this->_params['searchAttributes'][trim($attr[0])] = trim($attr[1]);
                }
            }
            
        }

        if (!isset($this->_params['bindUserDN']) || $this->_params['bindUserDN'] == '') {
            throw new jException('ldapdao~errors.bindUserDN.missing');
        }
        if (!is_array($this->_params['bindUserDN'])) {
            $this->_params['bindUserDN'] = array($this->_params['bindUserDN']);
        }
    }

    public function saveNewUser($user){
        throw new jException("ldapdao~errors.unsupported.user.creation");
    }

    public function removeUser($login){
        $dao = jDao::get($this->_params['dao'], $this->_params['profile']);
        $dao->deleteByLogin($login);
        return true;
    }

    public function updateUser($user){

        if (!is_object($user)) {
            throw new jException('ldapdao~errors.object.user.unknown');
        }

        if ($user->login == '') {
            throw new jException('ldapdao~errors.user.login.unset');
        }

        $dao = jDao::get($this->_params['dao'], $this->_params['profile']);
        $dao->update($user);
        return true;
    }

    public function getUser($login){
        $dao = jDao::get($this->_params['dao'], $this->_params['profile']);
        $user = $dao->getByLogin($login);
        if ($user) {
            return $user;
        }

        $user = $this->createUserObject($login, '');
        $connect = $this->_bindLdapAdminUser();
        if ($connect === false) {
            return false;
        }
        $this->searchLdapUserAttributes($connect, $login, $user);
        ldap_close($connect);
        $dao->insert($user);
        return $user;
    }

    public function createUserObject($login, $password){
        $user = jDao::createRecord($this->_params['dao'], $this->_params['profile']);
        $user->login = $login;
        // should not be empty because of a jauth listener that prevent
        // user not having password to login.
        $user->password = 'no password'; 
        return $user;
    }

    public function getUserList($pattern){
        throw new jException('ldapdao~errors.unsupported.user.listing');
    }

    public function changePassword($login, $newpassword) {
        throw new jException('ldapdao~errors.unsupported.password.change');
    }

    public function verifyPassword($login, $password) {
        $dao = jDao::get($this->_params['dao'], $this->_params['profile']);
        $user = $dao->getByLogin($login);

        if ($login == 'admin') {
            if (!$user) {
                return false;
            }

            $result = $this->checkPassword($password, $user->password);

            if ($result === false)
                return false;

            if ($result !== true) {
                // it is a new hash for the password, let's update it persistently
                $user->password = $result;
                $dao->updatePassword($login, $result);
            }
            return $user;
        }

        $connect = $this->_getLinkId();

        if (!$connect) {
            jLog::log('ldapdao: impossible to connect to ldap', 'auth');
            return false;
        }

        $bind = null;

        //authenticate user; let's try will all configured DN
        foreach($this->_params['bindUserDN'] as $dn) {
            $realDn = str_replace('%%USERNAME%%', $login, $dn);
            $bind = @ldap_bind($connect, $realDn, $password);
            if ($bind) {
                break;
            }
        }
        ldap_close($connect);

        // First pass with direct login has not worked
        // Connect as admin, to get more information on the user
        // This is necessary in ActiveDirectory to get the user by full DN
        if(!$bind and $this->_params['bindUserDnProperty'] != ''){
            $aconnect = $this->_bindLdapAdminUser();
            $dnProp = $this->_params['bindUserDnProperty'];

            // Create user instance, but do not insert it into database
            $user = $this->createUserObject($login, '');

            //get ldap user infos: name, email etc...
            if($checkUser = $this->searchLdapUserAttributes($aconnect, $login, $user)){
                $connect = $this->_getLinkId();
                $bind = @ldap_bind($connect, $user->$dnProp, $password);
                ldap_close($connect);
            }
            ldap_close($aconnect);
        }

        if (!$bind) {
            jLog::log('ldapdao: cannot bind to any configured path with the login '.$login, 'auth');
            return false;
        }

        $connect = $this->_bindLdapAdminUser();

        // check if he is in our database
        $dao = jDao::get($this->_params['dao'], $this->_params['profile']);
        $user = $dao->getByLogin($login);
        if (!$user) {

            // it's a new user, let's create it
            $user = $this->createUserObject($login, '');

            //get ldap user infos: name, email etc...
            $this->searchLdapUserAttributes($connect, $login, $user);
            $dao->insert($user);
            jEvent::notify ('AuthNewUser', array('user'=>$user));
        }

        // retrieve the user group (if relevant)
        $userGroup = $this->searchUserGroup($connect, $login);
        ldap_close($connect);

        if ($userGroup === false) {
            // no group filter given by ldap, let's use Lizmap groups:
            // default group if it's new user or Lizmap groups if the user already exists
            // and do not sync LDAP and lizmap, which would lead to keep only the LDAP group
            return $user;
        }

        // we know the user group: we should be sure it is the same in jAcl2
        $gplist = jDao::get('jacl2db~jacl2groupsofuser', 'jacl2_profile')
                        ->getGroupsUser($login);
        $groupsToRemove = array();
        $hasRightGroup = false;
        foreach($gplist as $group) {
            if ($group->grouptype == 2 ) // private group
                continue;
            if ($group->name === $userGroup) {
                $hasRightGroup = true;
            }
            else {
                $groupsToRemove[] = $group->name;
            }
        }
        foreach($groupsToRemove as $group) {
            jAcl2DbUserGroup::removeUserFromGroup($login, $group);
        }
        if(!$hasRightGroup && jAcl2DbUserGroup::getGroup($userGroup)) {
            jAcl2DbUserGroup::addUserToGroup($login, $userGroup);
        }
        return $user;
    }

    protected function searchLdapUserAttributes($connect, $login, $user) {
        $filter = str_replace('%%USERNAME%%', $login, $this->_params['searchUserFilter']);
        if (($search = ldap_search($connect,
                                   $this->_params['searchBaseDN'],
                                   $filter,
                                   array_keys($this->_params['searchAttributes'])))) {
            if (($entry = ldap_first_entry($connect, $search))) {
                $attributes = ldap_get_attributes($connect, $entry);
                $this->readLdapAttributes($attributes, $user);
                return true;
            }
        }
        return false;
    }

    protected function readLdapAttributes($attributes, $user) {
        foreach($this->_params['searchAttributes'] as $ldapAttr => $objAttr) {

            if (isset($attributes[$ldapAttr]) && is_array($attributes[$ldapAttr])) {
                $attr = $attributes[$ldapAttr];
                if (isset($attr['count']) && $attr['count'] > 0) {
                    if ($attr['count'] > 1) {
                        $user->$objAttr = array_shift($attr);
                    }
                    else {
                        $user->$objAttr = $attr[0];
                    }
                }
            }
            if (!isset($user->$objAttr)) {
                $user->$objAttr = '';
            }
        }
        return $user;
    }

    protected function searchUserGroup($connect, $login) {
        // Do not search for groups if no group filter passed
        // Usefull to forbid Lizmap to sync groups from LDAP and loose all related groups for the user
        if ($this->_params['searchGroupFilter'] == '') {
            return false;
        }
        $filter = str_replace('%%USERNAME%%', $login, $this->_params['searchGroupFilter']);
        $grpProp = $this->_params['searchGroupProperty'];

        if (($search = ldap_search($connect,
                                   $this->_params['searchBaseDN'],
                                   $filter,
                                   array($grpProp)))) {
            if (($entry = ldap_first_entry($connect, $search))) {
                $attributes = ldap_get_attributes($connect, $entry);
                if (isset($attributes[$grpProp]) && $attributes[$grpProp]['count'] > 0 ) {

                    return $attributes[$grpProp][0];
                }
            }
        }
        return false;
    }

    /**
     * open the connection to the ldap server
     */
    protected function _getLinkId() {
        if ($connect = ldap_connect($this->_params['hostname'], $this->_params['port'])) {
            ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, $this->_params['protocolVersion']);
            ldap_set_option($connect, LDAP_OPT_REFERRALS, 0);
            return $connect;
        }
        return false;
    }

    /**
     * open the connection to the ldap server
     * and bind to the admin user
     * @return resource the ldap connection
     */
    protected function _bindLdapAdminUser() {
        $connect = $this->_getLinkId();
        if (!$connect)
            return false;
        if ($this->_params['ldapAdminUserDn'] == '') {
            $bind = ldap_bind($connect);
        }
        else {
            $bind = ldap_bind($connect, $this->_params['ldapAdminUserDn'], $this->_params['ldapAdminPassword']);
        }
        if (!$bind) {
            ldap_close($connect);
            return false;
        }
        return $connect;
    }

}
