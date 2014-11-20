INSERT INTO config (`plugin`, `key`, `value`) VALUES 
('core', 'ldap:basedn', 'ou=users,dc=yunohost,dc=org'),
('core', 'ldap:domain', 'dc=yunohost,dc=org'),
('core', 'ldap:enable', '1'),
('core', 'ldap:filter', 'objectClass=mailAccount'),
('core', 'ldap:host', 'localhost'),
('core', 'ldap:port', '389'),
('core', 'sso:allowRemoteUser', '1');
