<?php

// Front controller entry point so the app can be served directly from
// this directory (e.g. XAMPP virtual host pointed at gullakpe-laravel/
// instead of gullakpe-laravel/public/). __DIR__ inside public/index.php
// still resolves to the public/ folder, so paths there remain correct.

// Serve real static files out of public/ (built assets, lang/*.json, etc.)
// directly from PHP. The .htaccess rewrite tried to do this via
// %{DOCUMENT_ROOT}/public%{REQUEST_URI}, but that only works when this
// directory IS the vhost document root — here it's nested under
// htdocs/gullakpe/, so DOCUMENT_ROOT points two levels too high and the
// condition never matched (assets 404'd). __DIR__ is always correct
// regardless of nesting depth, so resolve the static path in PHP instead.
//
// REQUEST_URI also includes whatever mount prefix got us here (e.g.
// /gullakpe/gullakpe-laravel/lang/en.json when reached via XAMPP's shared
// htdocs, or just /lang/en.json under a dedicated vhost) — strip it using
// SCRIPT_NAME's directory so this works at any mount depth.
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($scriptDir !== '' && str_starts_with($requestPath, $scriptDir)) {
    $requestPath = substr($requestPath, strlen($scriptDir)) ?: '/';
}
$publicFile = realpath(__DIR__.'/public'.$requestPath);
if ($publicFile !== false
    && is_file($publicFile)
    && str_starts_with($publicFile, realpath(__DIR__.'/public').DIRECTORY_SEPARATOR)) {
    // mime_content_type() sniffs content and misidentifies plain-text assets
    // like .js/.css/.json as text/plain — browsers reject that MIME type for
    // <script type="module"> and can ignore it for stylesheets. Extension
    // takes priority; only fall back to sniffing for unlisted types.
    $extensionMimes = [
        'js' => 'application/javascript', 'mjs' => 'application/javascript',
        'css' => 'text/css', 'json' => 'application/json',
        'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
        'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf',
        'map' => 'application/json',
    ];
    $ext = strtolower(pathinfo($publicFile, PATHINFO_EXTENSION));
    $mime = $extensionMimes[$ext] ?? (mime_content_type($publicFile) ?: 'application/octet-stream');
    header('Content-Type: '.$mime);
    header('Content-Length: '.filesize($publicFile));
    readfile($publicFile);
    exit;
}

require __DIR__.'/public/index.php';
