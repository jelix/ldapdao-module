Changes
=======

Version 2.0.0 (2017-04-05)
--------------------------------

The login process has changed, to take care of various ldap structure and 
server configuration.

- support of multiple search filters for users
- support of multiple dn templates to connect users
- move ldap connection parameters (hostname, port, admin login/password)
  to profiles.ini.php
- Jelix admin login is configurable
- synchronize all ldap groups into jAcl2 rights, if configured

Version 1.1.1 (2017-02-08)
--------------------------

- Fix mispelling variable names


Version 1.1.0 (2015-06-03)
--------------------------

Initial public release.