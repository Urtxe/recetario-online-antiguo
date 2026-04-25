<?php

require_once 'config/helpers.php';

class ImageStorageService {
    private $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif'
    ];

    private $maxBytes;
    private $maxDimension;

    public function __construct($maxBytes = null, $maxDimension = 4096) {
        $this->maxBytes = $maxBytes ?: 2 * 1024 * 1024;
        $this->maxDimension = $maxDimension;
    }

    public function storeUploadedImage($archivo) {
        if (!isset($archivo) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
            return [true, '', null];
        }

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            security_log('upload_failed', ['reason' => 'upload_error', 'code' => $archivo['error']], 'warning');
            return [false, '', 'Error al subir la imagen'];
        }

        if ($archivo['size'] > $this->maxBytes) {
            security_log('upload_failed', ['reason' => 'size_limit', 'size' => $archivo['size']], 'warning');
            return [false, '', 'La imagen supera el tamaño máximo permitido'];
        }

        $mimeReal = $this->detectMimeType($archivo['tmp_name']);
        if (!isset($this->allowedMimeTypes[$mimeReal])) {
            security_log('upload_failed', ['reason' => 'mime', 'mime' => $mimeReal], 'warning');
            return [false, '', 'Formato de imagen no válido'];
        }

        $dimensions = @getimagesize($archivo['tmp_name']);
        if ($dimensions === false) {
            security_log('upload_failed', ['reason' => 'invalid_image'], 'warning');
            return [false, '', 'La imagen no es válida'];
        }

        if ($dimensions[0] > $this->maxDimension || $dimensions[1] > $this->maxDimension) {
            security_log('upload_failed', [
                'reason' => 'dimensions',
                'width' => $dimensions[0],
                'height' => $dimensions[1]
            ], 'warning');
            return [false, '', 'La imagen supera las dimensiones permitidas'];
        }

        $directory = upload_storage_path();
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $this->allowedMimeTypes[$mimeReal];
        $destination = upload_storage_path($filename);

        if (!move_uploaded_file($archivo['tmp_name'], $destination)) {
            return [false, '', 'No se pudo guardar la imagen'];
        }

        return [true, $filename, null];
    }

    public function deleteImage($filename) {
        if (empty($filename)) {
            return false;
        }

        $path = upload_storage_path($filename);
        if (file_exists($path)) {
            return unlink($path);
        }

        return false;
    }

    private function detectMimeType($path) {
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            return $finfo->file($path);
        }

        if (function_exists('mime_content_type')) {
            return mime_content_type($path);
        }

        return '';
    }
}
