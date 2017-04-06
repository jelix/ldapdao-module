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

General configuration properties
---------------------------------

First you should set the `dao`, `profile` and `form` properties, to indicate
the dao (for the table), the form (for the administration module) and 
the profile to access to the database.

You should then set some ldap parameters to access to the ldap server: `hostname`
and `port`. 

You must also indicate in `ldapAdminUserDn` and `ldapAdminPassword`, the DN 
(Distinguished Name) and the password of the user that have enough rights to 
query the directory (to search a user, to get his attributes, his groups etc...).

Configuration properties for login checking
-------------------------------------------

Starting from 1.2, the login process has changed, to take care of various
ldap structure and server configuration.

To verify the password, the plugin needs the DN (Distinguished Name) corresponding 
to the user. It builds the DN from a "template" indicated into the `bindUserDN`
property, and from various data:

- From the login given by the user. Example: `bindUserDN="uid=%%LOGIN%%,ou=users,dc=XY,dc=fr"`
  where `%%LOGIN%%` is replaced by the login given by the user.
- Or from one of the ldap attributes of the user. In this case, the plugin query
  the ldap directory with the `searchUserFilter` filter, to retrieve the user's
  ldap attributes.
   - Example with one attribute value which is part of the DN:
     `bindUserDN="uid=%?%,ou=users,dc=XY,dc=fr"`. Here it replaces the `%?%` by the
     value of the `uid` attribute readed from the search result.
     Note: the attribute name should be present into the `searchAttributes`
     configuration property, even with no field mapping. Ex: `...,uid:,...`
   - Example with an attribute that contains the full DN:
     `bindUserDN="$dn"`. Here it takes the `dn` attribute readed from the search
     result, and use its full value as the DN to login against the ldap server.
     It is usefull for some LDAP server like Active Directory that need a 
     full DN specific for each user.
     Note: the attribute name should be present into the `searchAttributes`
     configuration property, even with no field mapping. Ex: `...,dn:,...`
     
The `searchUserFilter` should contain the ldap query, and a `%%LOGIN%%` placeholder
that will be replaced by the login given by the user.

Example: `searchUserFilter="(&(objectClass=posixAccount)(uid=%%LOGIN%%))"`

You may also indicate the base DN for the search, into `searchBaseDN`. Example:
`searchBaseDN="ou=ADAM users,o=Microsoft,c=US"`.

Note that you can indicate several search filter or dn templates, if you have
complex ldap structure. Use `[]` to indicate an item list

```
searchUserFilter[]="(&(objectClass=posixAccount)(uid=%%LOGIN%%))"
searchUserFilter[]="(&(objectClass=posixAccount)(cn=%%LOGIN%%))"

bindUserDN[]="uid=%?%,ou=users,dc=XY,dc=fr"
bindUserDN[]="cn=%?%,ou=users,dc=XY,dc=fr"
```

Configuration properties for user data
--------------------------------------

When a user is authenticated with the ldap, but it is not registered into
the list of users of Jelix, the plugin creates automatically an account into
the application.

To achieve this goal, it needs which ldap attributes to take, and in which
database field to store these attribute values. You indicate such informations
into the `searchAttributes` property. It is a pair of names, 
`<ldap attribute>:<table field>`, separated by a comma.

In this example, `searchAttributes="uid:login,givenName,sn:lastname,mail:email,dn:"`:

- the value of the `uid` ldap attribute will be stored into the `login` field 
- the value of the `sn` ldap attribute will be stored into the `lastname` field
- the value of the `givenName` ldap attribute will be stored into a field that
  have the same name, as there is no field name nor `:`.
- there will not be mapping for the `dn` property. There is a `:` without field name.
  It will be readed from ldap, and can be used into the `bindUserDN` DN template.

The possible list of possible fields is indicated into the dao file, whose name
is indicated into the `dao` configuration property.

Configuration properties for user rights
----------------------------------------

If you have configured groups rights into your application, and if these
groups match your ldap groups, you can indicate to the plugin to automatically
put the user into the application groups, according to the user ldap groups.

You should then indicate into `searchGroupFilter` the ldap query that will
retrieve the groups of the user.

Example: `searchGroupFilter="(&(objectClass=posixGroup)(cn=XYZ*)(memberUid=%%LOGIN%%))"`

Warning : setting `searchGroupFilter` will remove the user from any other
application groups that don't match the ldap group. If you don't want
a groups synchronization, leave `searchGroupFilter` empty.

With `searchGroupProperty`, you must indicate the ldap attribute that
contains the group name. Ex: `searchGroupProperty="cn"`.


