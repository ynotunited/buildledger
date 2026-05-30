<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

class InputSanitizer
{
    private static ?HTMLPurifier $richTextPurifier = null;

    public static function text(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = self::stripDangerousBlocks($value);
        $value = self::stripControlCharacters($value);
        $value = strip_tags($value);
        $value = preg_replace('/\s+/u', ' ', $value);

        return trim((string) $value);
    }

    public static function multilineText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = self::stripDangerousBlocks($value);
        $value = self::stripControlCharacters($value);
        $value = strip_tags($value);
        $value = preg_replace("/\r\n|\r/u", "\n", $value);
        $value = preg_replace("/\n{3,}/u", "\n\n", $value);

        return trim((string) $value);
    }

    public static function richText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = self::stripControlCharacters($value);
        $value = self::richTextPurifier()->purify($value);

        return trim((string) $value);
    }

    public static function fileName(string $value): string
    {
        $value = basename($value);
        $value = preg_replace('/[^A-Za-z0-9._ -]/', '-', $value);
        $value = preg_replace('/-+/', '-', (string) $value);

        return trim((string) $value, " .-\t\n\r\0\x0B") ?: 'file';
    }

    private static function stripControlCharacters(string $value): string
    {
        return preg_replace('/[^\P{C}\n\t]+/u', '', $value) ?? '';
    }

    private static function stripDangerousBlocks(string $value): string
    {
        return preg_replace('#<\s*(script|style|iframe|object|embed|link|meta)[^>]*>.*?<\s*/\s*\1\s*>#is', '', $value) ?? '';
    }

    private static function richTextPurifier(): HTMLPurifier
    {
        if (self::$richTextPurifier instanceof HTMLPurifier) {
            return self::$richTextPurifier;
        }

        $cachePath = storage_path('framework/cache/htmlpurifier');

        if (! is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', $cachePath);
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.Allowed', 'p,br,strong,em,ul,ol,li,a[href],blockquote,h1,h2,h3,h4,h5,h6');
        $config->set('HTML.ForbiddenElements', ['script', 'style', 'iframe', 'object', 'embed', 'link', 'meta', 'form']);
        $config->set('CSS.AllowedProperties', []);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('AutoFormat.RemoveEmpty.RemoveNbsp', true);
        $config->set('Attr.EnableID', false);
        $config->set('Attr.AllowedFrameTargets', []);
        $config->set('HTML.TargetBlank', false);
        $config->set('HTML.Nofollow', true);
        $config->set('URI.DisableResources', true);
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
        ]);

        self::$richTextPurifier = new HTMLPurifier($config);

        return self::$richTextPurifier;
    }
}
