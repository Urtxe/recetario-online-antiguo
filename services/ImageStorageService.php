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
    private $maxWidth;
    private $maxHeight;
    private $quality;
    private $outputFormat;

    public function __construct($maxBytes = null, $maxDimension = 4096) {
        $this->maxBytes = $maxBytes ?: 2 * 1024 * 1024;
        $this->maxDimension = $maxDimension;
        $this->maxWidth = max(320, app_int_env('UPLOAD_IMAGE_MAX_WIDTH', 1600));
        $this->maxHeight = max(320, app_int_env('UPLOAD_IMAGE_MAX_HEIGHT', 1600));
        $this->quality = max(40, min(100, app_int_env('UPLOAD_IMAGE_QUALITY', 82)));
        $this->outputFormat = strtolower(trim((string)app_env('UPLOAD_IMAGE_FORMAT', 'auto')));
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

        $extension = $this->resolveOutputExtension($mimeReal);
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = upload_storage_path($filename);

        if ($this->canOptimizeImage($mimeReal)) {
            if (!$this->optimizeAndStoreImage($archivo['tmp_name'], $mimeReal, $destination)) {
                return [false, '', 'No se pudo guardar la imagen'];
            }
        } else {
            if (!move_uploaded_file($archivo['tmp_name'], $destination)) {
                return [false, '', 'No se pudo guardar la imagen'];
            }
        }

        return [true, $filename, null];
    }

    public function deleteImage($filename) {
        if (empty($filename)) {
            return false;
        }

        $paths = [
            upload_storage_path($filename),
            upload_legacy_public_path($filename)
        ];

        $deleted = false;
        foreach ($paths as $path) {
            if (file_exists($path) && unlink($path)) {
                $deleted = true;
            }
        }

        return $deleted;
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

    private function canOptimizeImage($mimeReal) {
        if (!extension_loaded('gd')) {
            return false;
        }

        return $this->resolveOutputExtension($mimeReal) !== '';
    }

    private function resolveOutputExtension($mimeReal) {
        if ($this->outputFormat === 'webp' && function_exists('imagewebp')) {
            return 'webp';
        }

        if ($this->outputFormat === 'jpg' || $this->outputFormat === 'jpeg') {
            return 'jpg';
        }

        if ($this->outputFormat === 'png') {
            return 'png';
        }

        return $this->allowedMimeTypes[$mimeReal] ?? '';
    }

    private function optimizeAndStoreImage($sourcePath, $mimeReal, $destination) {
        $image = $this->createImageResource($sourcePath, $mimeReal);
        if (!$image) {
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        if ($width <= 0 || $height <= 0) {
            imagedestroy($image);
            return false;
        }

        list($targetWidth, $targetHeight) = $this->calculateTargetDimensions($width, $height);
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        if (!$canvas) {
            imagedestroy($image);
            return false;
        }

        $extension = $this->resolveOutputExtension($mimeReal);
        $this->prepareCanvas($canvas, $extension);

        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        $saved = $this->saveImageResource($canvas, $destination, $extension);

        imagedestroy($canvas);
        imagedestroy($image);

        if ($saved) {
            security_log('image_optimized', [
                'source_mime' => $mimeReal,
                'target_extension' => $extension,
                'target_width' => $targetWidth,
                'target_height' => $targetHeight
            ], 'info');
        }

        return $saved;
    }

    private function createImageResource($sourcePath, $mimeReal) {
        switch ($mimeReal) {
            case 'image/jpeg':
                return function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($sourcePath) : false;
            case 'image/png':
                return function_exists('imagecreatefrompng') ? @imagecreatefrompng($sourcePath) : false;
            case 'image/gif':
                return function_exists('imagecreatefromgif') ? @imagecreatefromgif($sourcePath) : false;
            default:
                return false;
        }
    }

    private function calculateTargetDimensions($width, $height) {
        $ratio = min(
            1,
            $this->maxWidth / max(1, $width),
            $this->maxHeight / max(1, $height)
        );

        return [
            max(1, (int)round($width * $ratio)),
            max(1, (int)round($height * $ratio))
        ];
    }

    private function prepareCanvas($canvas, $extension) {
        if (in_array($extension, ['png', 'webp'], true)) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, imagesx($canvas), imagesy($canvas), $transparent);
            return;
        }

        $background = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, imagesx($canvas), imagesy($canvas), $background);
    }

    private function saveImageResource($canvas, $destination, $extension) {
        switch ($extension) {
            case 'jpg':
                return imagejpeg($canvas, $destination, $this->quality);
            case 'png':
                $compression = (int)round((100 - $this->quality) / 11.111111);
                $compression = max(0, min(9, $compression));
                return imagepng($canvas, $destination, $compression);
            case 'gif':
                return imagegif($canvas, $destination);
            case 'webp':
                return function_exists('imagewebp') ? imagewebp($canvas, $destination, $this->quality) : false;
            default:
                return false;
        }
    }
}
