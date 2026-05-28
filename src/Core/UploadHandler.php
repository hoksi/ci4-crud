<?php

namespace Hoksi\Ci4Crud\Core;

use Hoksi\Ci4Crud\Config\CrudConfig;

class UploadHandler
{
    private const ALLOWED_IMAGES = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    private const MAX_SIZE_MB    = 10;

    public function __construct(private readonly CrudConfig $config) {}

    /**
     * 단일 파일 업로드 처리.
     * CI4 컨텍스트에서 service('request')->getFile() 사용.
     *
     * @return string|false 저장된 파일명 또는 false (실패)
     */
    public function handleSingle(string $field): string|false
    {
        if (!function_exists('service')) {
            return false;
        }

        $uploadConfig = $this->config->uploadFields[$field] ?? null;
        if ($uploadConfig === null) {
            return false;
        }

        $file = service('request')->getFile($field);
        if ($file === null || !$file->isValid() || $file->hasMoved()) {
            return false;
        }

        // before_upload 콜백
        foreach ($this->config->callbacks['before_upload'] ?? [] as $fn) {
            $file = $fn($file);
            if ($file === false) {
                return false;
            }
        }

        $destPath = rtrim($uploadConfig['path'], '/') . '/';
        $newName  = $file->getRandomName();

        if (!$file->move(WRITEPATH . 'uploads/' . $destPath, $newName)) {
            return false;
        }

        $storedPath = $destPath . $newName;

        // after_upload 콜백
        foreach ($this->config->callbacks['after_upload'] ?? [] as $fn) {
            $fn($storedPath, $destPath . $newName);
        }

        return $storedPath;
    }

    /**
     * 다중 파일 업로드 처리.
     *
     * @return array<string> 저장된 파일명 배열
     */
    public function handleMultiple(string $field): array
    {
        if (!function_exists('service')) {
            return [];
        }

        $uploadConfig = $this->config->uploadFields[$field] ?? null;
        if ($uploadConfig === null) {
            return [];
        }

        $files    = service('request')->getFileMultiple($field) ?? [];
        $stored   = [];
        $destPath = rtrim($uploadConfig['path'], '/') . '/';

        foreach ($files as $file) {
            if (!$file->isValid() || $file->hasMoved()) {
                continue;
            }

            // before_upload 콜백
            $skip = false;
            foreach ($this->config->callbacks['before_upload'] ?? [] as $fn) {
                $file = $fn($file);
                if ($file === false) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) continue;

            $newName = $file->getRandomName();
            if ($file->move(WRITEPATH . 'uploads/' . $destPath, $newName)) {
                $storedPath = $destPath . $newName;
                $stored[]   = $storedPath;

                foreach ($this->config->callbacks['after_upload'] ?? [] as $fn) {
                    $fn($storedPath, $destPath);
                }
            }
        }

        return $stored;
    }

    /**
     * 업로드 필드 데이터를 일반 데이터 배열에 병합합니다.
     * InsertHandler/UpdateHandler에서 호출합니다.
     */
    public function injectUploadedPaths(array $data): array
    {
        foreach ($this->config->uploadFields as $field => $opts) {
            if ($opts['multiple']) {
                $paths = $this->handleMultiple($field);
                if (!empty($paths)) {
                    $data[$field] = implode(',', $paths);
                }
            } else {
                $path = $this->handleSingle($field);
                if ($path !== false) {
                    $data[$field] = $path;
                }
            }
        }

        return $data;
    }
}
