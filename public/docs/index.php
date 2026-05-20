<?php
// TACLOUD Nginx Fallback
// Meneruskan request /docs/ ke /docs/index.html
if (file_exists(__DIR__ . '/index.html')) {
    header('Content-Type: text/html');
    echo file_get_contents(__DIR__ . '/index.html');
    exit;
}
