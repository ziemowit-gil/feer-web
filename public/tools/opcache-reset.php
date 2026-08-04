<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo 'OK';
} else {
    echo 'opcache not enabled';
}
unlink(__FILE__);
