<?php
require_once __DIR__ . '/includes/auth.php';
logout_session();
header('Location: /login.php');
