<?php
$config = [];

include("/etc/roundcube/debian-db-roundcube.php");

// IMAP - leer correo
$config['imap_host'] = 'localhost:143';

// SMTP - enviar correo (sin TLS en localhost)
$config['smtp_host'] = 'localhost:25';
$config['smtp_user'] = '';
$config['smtp_pass'] = '';

// General
$config['product_name'] = 'Correo imad.local';
$config['des_key'] = 'hYCShNp33gvGCVfwoOzZzPQJ';
$config['skin'] = 'elastic';
$config['support_url'] = '';
$config['enable_spellcheck'] = false;
$config['plugins'] = [];
