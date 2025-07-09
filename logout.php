<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Clear any other authentication cookies if you have them
// setcookie('remember_me', '', time()-3600, '/');

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login with cache-busting parameter
header("Location: login.php?logout=1&t=" . time());
exit();
?>
