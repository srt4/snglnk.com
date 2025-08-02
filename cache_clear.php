<?php
// Force opcache clear
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "Opcache cleared\n";
} else {
    echo "Opcache not available\n";
}

// Also try to clear any file cache
if (function_exists('apc_clear_cache')) {
    apc_clear_cache();
    echo "APC cache cleared\n";
}

echo "Cache clear attempt completed\n";
?>