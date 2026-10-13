<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    audit_log('LOGOUT', 'Auth', current_user()['name'] . ' logged out.');
}
session_unset();
session_destroy();
header('Location: login.php');
exit;
