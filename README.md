This is a module for Jelix, providing a plugin for jAuth allowing to authenticate
with an ldap server, and register them in the app database, using a dao.

This module is for Jelix 1.6.x and higher. 


Installation
============

Install files with Jelix 1.7 (experimental)
-----------------------------
You should use Composer to install the module. Run this commands in a shell:
                                               
```
composer require "jelix/ldapdao-module"
```

Install files with Jelix 1.6
-----------------------------

Copy the `ldapdao` directory into the modules/ directory of your application.


Declare the module
-------------------

Next you must say to Jelix that you want to use the module. Declare
it into the mainconfig.ini.php file (into yourapp/var/config/ for Jelix 1.6,
or into yourapp/app/config/ for Jelix 1.7).

In the `[modules]` section, add:

```ini
ldapdao.access=1
```

Following modules are required: jacl2, jauth, jauthdb. In this same section 
verify that they are activated:

```ini
jacl2.access=1
jauth.access=2
jauthdb.access=1
```

Launch the installer
--------------------

In the command line, launch:

```
php yourapp/cmd.php install
```

Configuration
=============

This module provides two things:

1. a plugin, ```ldapdao```, for jAuth
2. a configuration file for the ```auth``` plugin for jCoordinator.

The ```ldapdao``` plugin replaces the `db` or `ldap` plugin for jAuth. The 
installer of the module deactivates some jAcl2 rights, and copy an example 
of the configuration file `authldap.coord.ini.php` into the configuration directory  
(`var/config` in Jelix 1.6, `app/config` in Jelix 1.7).

You should edit the new file `authldap.coord.ini.php`. Many properties
should be changed to match your ldap structure.

Second, you should indicate this new configuration file into the mainconfig.ini.php file,
in the `coordplugins` section:

```
[coordplugins]
auth="authldap.coord.ini.php"
```

