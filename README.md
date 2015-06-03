This is a module for Jelix, providing a plugin for jAuth allowing to authenticate
with an ldap server, and register them in the app database, using a dao.

This module is for Jelix 1.6.x and higher. 


Installation
============

Install it by hands like any other Jelix modules, or use Composer if you installed
Jelix 1.7+ with Composer:

```
composer require "jelix/ldapdao-module"
```

Then declare the module into the configuration of your application

```ini
[modules]

ldapdao.access=1
```

With Jelix 1.6, you should declare the path to ldapdao-module in the modulesPath
properties in the main configuration files.

Following modules are required: jacl2, jauth, jauthdb.

And then:

```
php yourapp/cmd.php install
```

Configuration
=============

This module provides two things:

1. a plugin, ```ldapdao```, for jAuth
2. a configuration file for the ```auth``` plugin for jCoordinator.

The ldapdao plugin replaces the auth plugin for jAuth. The installer of the module
deactivates some jAcl2 rights, and copy an example of the configuration file into ```var/config```

Here steps to do after the installation.

First you should edit the new file var/config/authldap.coord.ini.php. Many properties
should be changed to match your ldap structure.

Second, you should indicate this new configuration file.

```
[coordplugins]
auth="authldap.coord.ini.php"
```

