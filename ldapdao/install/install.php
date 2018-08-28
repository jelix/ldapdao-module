<?php
/**
* @author    Laurent Jouanneau
*/

/**
 * Installer for Jelix 1.7
 */
class ldapdaoModuleInstaller extends \Jelix\Installer\Module\Installer {

    function install() {

        // we should disable some rights
        $daoright = jDao::get('jacl2db~jacl2rights', 'jacl2_profile');
        $daoright->deleteBySubject('auth.users.create');
        $daoright->deleteBySubject('auth.users.change.password');
        $daoright->deleteBySubject('auth.user.change.password');
        //$daoright->deleteBySubject('auth.users.delete');

        // allow the admin user to change his right
        $confIni = parse_ini_file($this->getAuthConfFile(), true);
        $authConfig = jAuth::loadConfig($confIni);
        $jelixAdminUser = $authConfig['ldapdao']['jelixAdminLogin'];
        $userGroup = jAcl2DbUserGroup::getPrivateGroup($jelixAdminUser);
        jAcl2DbManager::addRight($userGroup, 'auth.user.change.password');
    }

    protected function getAuthConfFile() {
        $authconfig = $this->config->getValue('auth','coordplugins');
        return jApp::appConfigPath($authconfig);
    }
}
