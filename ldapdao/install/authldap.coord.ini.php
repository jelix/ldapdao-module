;<?php die(''); ?>
;for security reasons , don't remove or modify the first line

;============= Main parameters

; driver name : "ldap", "Db", "Class" or "LDS" (respect the case of characters)
driver = "ldapdao"

;============ Parameters for the plugin
; session variable name
session_name = "JELIX_USER"

; Says if there is a check on the ip address : verify if the ip
; is the same when the user has been connected
secure_with_ip = 0

;Timeout. After the given time (in minutes) without activity, the user is disconnected.
; If the value is 0 : no timeout
timeout = 0

; If the value is "on", the user must be authentificated for all actions, except those
; for which a plugin parameter  auth.required is false
; If the value is "off", the authentification is not required for all actions, except those
; for which a plugin parameter  auth.required is true
auth_required = off

; What to do if an authentification is required but the user is not authentificated
; 1 = generate an error. This value should be set for web services (xmlrpc, jsonrpc...)
; 2 = redirect to an action
on_error = 2

; locale key for the error message when on_error=1
error_message = "jauth~autherror.notlogged"

; action to execute on a missing authentification when on_error=2
on_error_action = "jauth~login:form"

; action to execute when a bad ip is checked with secure_with_ip=1 and on_error=2
bad_ip_action = "jauth~login:out"


;=========== Parameters for jauth module

; number of second to wait after a bad authentification
on_error_sleep = 0

; action to redirect after the login
after_login = "jauth~login:form"

; action to redirect after a logout
after_logout = "jauth~login:form"

; says if after_login can be overloaded by a "auth_url_return" parameter in the url/form for the login
enable_after_login_override = on

; says if after_logout can be overloaded by a "auth_url_return" parameter in the url/form for the login
enable_after_logout_override = on

;============ Parameters for the persistance of the authentification

; enable the persistance of the authentification between two sessions
persistant_enable=on

; key to use to crypt the password in the cookie. replace it by your own words !
persistant_crypt_key= exampleOfCryptKey

; the name of the cookie which is used to store data for the authentification
persistant_cookie_name=jelixAuthentificationCookie

; duration of the validity of the cookie (in days). default is 1 day.
persistant_duration = 1

;=========== parameters for password hashing

; method of the hash. 0 or "" means old hashing behavior of jAuth
; (using password_* parameters in drivers ).
; Prefer to choose 1, which is the default hash method (bcrypt).
password_hash_method = 1

; options for the hash method. list of "name:value" separated by a ";"
password_hash_options = 

;=========== Parameters for drivers

[ldapdao]

compatiblewithdb = on

; name of the dao to get user data
dao = "jauthdb~jelixuser"

; profile to use for jDb 
profile = "jauth"

; ldap needs clear password to connect, this is useless for our plugin
; except for the admin user.
; even if password_hash_method is activated, we set it to allow
; password storage migration
password_crypt_function = sha1

; name of the form for the jauthdb_admin module
form = "jauthdb_admin~jelixuser"

; path of the directory where to store files uploaded by the form (jauthdb_admin module)
; should be related to the var directory of the application
uploadsDirectory= ""

;--- ldap parameters
; default "localhost"
hostname="ldap://127.0.0.1:389"
;hostname="ldaps://127.0.0.1:636"
port=389
;port=636 ; tls port

; dn of the user to connect with LDAP (user who has right to list user and see their attributes)
; other example: ldapAdminUserDn="CN=SOMELDAPUSER,OU=Some group,OU=Some other group,DC=my-city,DC=com"
ldapAdminUserDn="uid=MYUID,ou=users,dc=XY,dc=fr"

; password used to connect with LDAP
ldapAdminPassword="a_password"

; this is the jelix user that have admin rights. It will not be verified in the
; ldap
jelixAdminLogin="admin"

; the dn to bind the user to login. It can be a list of DN:
;bindUserDN[]= ...
;bindUserDN[]= ...
bindUserDN="uid=%%LOGIN%%,ou=users,dc=XY,dc=fr"
; Some LDAP server like Active Directory cannot use this but need a full DN specific for each user
; in this case use the next bindUserDnProperty variable

; the property containing the full user DN. It is needed e.g. for Active Directory
; because the user must use its full unique DN to login
; leave empty to only use the above DN configured with bindUserDN
;bindUserDnProperty = "dn"
bindUserDnProperty = ""

; search base dn. Used to search a user using the filter from searchUserFilter
; example for Active Directory: "ou=ADAM users,o=Microsoft,c=US", or "OU=Town,DC=my-town,DC=com"
searchBaseDN="dc=XY,dc=fr"

; filter to get user information, with the given login name
; example for Active Directory: "(sAMAccountName=%%LOGIN%%)"
searchUserFilter="(&(objectClass=posixAccount)(uid=%%LOGIN%%))"


; attributes to retrieve for a user
; for dao mapping: "ldap attribute:dao attribute"
; ex: "uid:login,givenName:firstname,mail:email" : uid goes into the login property,
; ldap attribute givenName goes to the property firstname etc..
; example for Active Directory: "cn,distinguishedName,name"
; or "sAMAccountName:login,givenName:firstname,sn:lastname,mail:email,distinguishedName,name,dn"
searchAttributes="uid:login,givenName:firstname,sn:lastname,mail:email"

; attributes to retrieve the group of a user for jAcl2. leave empty if you don't use it
; !!! IMPORTANT !!! : if searchGroupFilter is not empty,
; the plugin will remove the user from all existing jelix groups
; and only keep the relation between the user and the group retrieved from LDAP
;searchGroupFilter="(&(objectClass=posixGroup)(cn=XYZ*)(memberUid=%%LOGIN%%))"
searchGroupFilter=
searchGroupProperty="cn"
