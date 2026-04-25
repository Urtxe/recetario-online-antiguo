<?php

require_once 'config/helpers.php';
require_once 'controllers/BaseController.php';

class FileController extends BaseController {
    public function imagen($name = '') {
        $this->requireLogin();

        if ($name === '' && isset($_GET['name'])) {
            $name = $_GET['name'];
        }

        $filename = basename(rawurldecode((string)$name));
        if ($filename === '') {
            http_response_code(404);
            exit('Imagen no encontrada');
        }

        $paths = [
            upload_storage_path($filename),
            upload_legacy_public_path($filename)
        ];

        $path = null;
        foreach ($paths as $candidate) {
            if (is_file($candidate)) {
                $path = $candidate;
                break;
            }
        }

        if ($path === null) {
            http_response_code(404);
            exit('Imagen no encontrada');
        }

        $mime = 'application/octet-stream';
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $detected = $finfo->file($path);
            if (!empty($detected)) {
                $mime = $detected;
            }
        }

        header('Content-Type: ' . $mime);
        header('Cache-Control: private, max-age=31536000, immutable');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit();
    }
}
