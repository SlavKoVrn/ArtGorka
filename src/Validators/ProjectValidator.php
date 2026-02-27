<?php

namespace App\Validators;

class ProjectValidator
{
    private const ALLOWED_PLATFORMS = ['WordPress', 'Bitrix', 'Custom', 'Other'];
    private const ALLOWED_STATUSES = ['development', 'production', 'maintenance', 'archived'];

    public static function validateCreate(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Project name is required';
        }

        if (empty($data['url']) || !filter_var($data['url'], FILTER_VALIDATE_URL)) {
            $errors['url'] = 'Valid URL is required';
        }

        if (!empty($data['platform']) && !in_array($data['platform'], self::ALLOWED_PLATFORMS)) {
            $errors['platform'] = 'Invalid platform type';
        }

        if (!empty($data['status']) && !in_array($data['status'], self::ALLOWED_STATUSES)) {
            $errors['status'] = 'Invalid status';
        }

        return $errors;
    }

    public static function validateUpdate(array $data): array
    {
        // При обновлении поля не обязательны, но если переданы - должны быть валидны
        $errors = [];

        if (isset($data['url']) && !filter_var($data['url'], FILTER_VALIDATE_URL)) {
            $errors['url'] = 'Valid URL is required';
        }

        if (isset($data['platform']) && !in_array($data['platform'], self::ALLOWED_PLATFORMS)) {
            $errors['platform'] = 'Invalid platform type';
        }

        if (isset($data['status']) && !in_array($data['status'], self::ALLOWED_STATUSES)) {
            $errors['status'] = 'Invalid status';
        }

        return $errors;
    }
}
