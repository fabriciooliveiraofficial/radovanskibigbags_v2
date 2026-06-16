<?php
if (function_exists('opcache_reset')) {
    $result = opcache_reset();
    echo "OPcache reset: " . ($result ? 'success' : 'failure');
} else {
    echo "OPcache function not available.";
}
unlink(__FILE__); // Self-delete after running so it doesn't stay on the server
