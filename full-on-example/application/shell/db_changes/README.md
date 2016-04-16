## You're going to need these tables

Run the queries in this directory to add required tables to your app database.

### Setup data tables for OAuth2

Be sure to checkout the notes on complying with the setup when storing your records like user tables. It will also 
give you hints on where to go when you need to customize stuff. 

```sql

# Create oauth tables
CREATE TABLE oauth_clients (client_id VARCHAR(80) NOT NULL, client_secret VARCHAR(80), redirect_uri VARCHAR(2000) NOT NULL, grant_types VARCHAR(80), scope VARCHAR(100), user_id VARCHAR(80), CONSTRAINT clients_client_id_pk PRIMARY KEY (client_id));
CREATE TABLE oauth_access_tokens (access_token VARCHAR(40) NOT NULL, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(255), expires TIMESTAMP NOT NULL, scope VARCHAR(2000), CONSTRAINT access_token_pk PRIMARY KEY (access_token));
CREATE TABLE oauth_authorization_codes (authorization_code VARCHAR(40) NOT NULL, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(255), redirect_uri VARCHAR(2000), expires TIMESTAMP NOT NULL, scope VARCHAR(2000), CONSTRAINT auth_code_pk PRIMARY KEY (authorization_code));
CREATE TABLE oauth_refresh_tokens (refresh_token VARCHAR(40) NOT NULL, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(255), expires TIMESTAMP NOT NULL, scope VARCHAR(2000), CONSTRAINT refresh_token_pk PRIMARY KEY (refresh_token));
CREATE TABLE oauth_users (username VARCHAR(255) NOT NULL, password VARCHAR(2000), first_name VARCHAR(255), last_name VARCHAR(255), CONSTRAINT username_pk PRIMARY KEY (username));
CREATE TABLE oauth_scopes (scope TEXT, is_default BOOLEAN);
CREATE TABLE oauth_jwt (client_id VARCHAR(80) NOT NULL, subject VARCHAR(80), public_key VARCHAR(2000), CONSTRAINT jwt_client_id_pk PRIMARY KEY (client_id));

# Make some tweaks
alter table oauth_access_tokens 
add column user_id INT(6), 
add column created_at varchar(30) comment 'ISO8601 format date from php date("c") method call';

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL DEFAULT '',
  `email` varchar(255) DEFAULT NULL,
  `phone_number` varchar(15) DEFAULT NULL COMMENT 'e.g. +1 000 000 0000',
  `email_opt_in` char(1) DEFAULT 'Y',
  `phone_opt_in` char(1) DEFAULT 'Y',
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL DEFAULT '',
  `scopes` varchar(100) DEFAULT 'user customer',
  `created_at` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8;

DROP TABLE if exists oauth_users;

CREATE VIEW oauth_users AS 
select username, first_name, last_name, password from users;

# Store your passwords in the database table using the App::hash(<some password>) method from the App library

```