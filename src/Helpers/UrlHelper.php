<?php

namespace App\Helpers;

class UrlHelper
{
    /**
     * Получает текущий протокол (http/https)
     */
    public static function getProtocol(): string
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    }

    /**
     * Получает текущий хост (domain:port)
     */
    public static function getHost(): string
    {
        return $_SERVER['HTTP_HOST'] ?? 'localhost';
    }

    /**
     * Получает полный базовый URL (протокол + хост)
     */
    public static function getBaseUrl(): string
    {
        return self::getProtocol() . '://' . self::getHost();
    }

    /**
     * Получает полный текущий URL
     */
    public static function getCurrentUrl(): string
    {
        $baseUrl = self::getBaseUrl();
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        return $baseUrl . $requestUri;
    }

    /**
     * Заменяет плейсхолдеры в HTML контенте
     * Поддерживает: {{current.site}}, {{base_url}}, {{current_url}}
     */
    public static function render(string $templatePath, array $data = []): string
    {
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template not found: {$templatePath}");
        }

        $content = file_get_contents($templatePath);

        // Заменяем плейсхолдеры
        $replacements = [
            '{{current.site}}' => self::getHost(),
            '{{base_url}}' => self::getBaseUrl(),
            '{{current_url}}' => self::getCurrentUrl(),
            '{{protocol}}' => self::getProtocol(),
        ];

        // Добавляем кастомные данные
        $replacements = array_merge($replacements, $data);

        // Заменяем все плейсхолдеры вида {{key}}
        foreach ($replacements as $key => $value) {
            // Экранируем ключ для использования в preg_replace
            $pattern = '/\{\{\s*' . preg_quote(str_replace(['{{', '}}'], '', $key), '/') . '\s*\}\}/';
            $content = preg_replace($pattern, htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $content);
        }

        return $content;
    }
}