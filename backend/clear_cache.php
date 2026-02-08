<?php
// Clear opcache if enabled
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo json_encode(['success' => true, 'message' => 'Opcache cleared']);
} else {
    echo json_encode(['success' => false, 'message' => 'Opcache not enabled']);
}
?>
