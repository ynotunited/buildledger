<?php

namespace App\Support;

class AiPromptBuilder
{
    public static function sanitize(string $value): string
    {
        $value = preg_replace('/[^\P{C}\n\t]+/u', '', $value) ?? '';
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        return trim($value);
    }

    public static function wrapUserContent(string $value, ?string $label = null): string
    {
        $label = $label !== null && trim($label) !== '' ? trim($label) : 'USER CONTENT';
        $open = (string) config('security.ai_prompt_user_delimiter_start', 'BEGIN USER CONTENT');
        $close = (string) config('security.ai_prompt_user_delimiter_end', 'END USER CONTENT');
        $content = self::sanitize($value);

        return implode("\n", [
            "[{$open}: {$label}]",
            $content,
            "[{$close}: {$label}]",
        ]);
    }

    /**
     * Build role-separated chat messages so user input never occupies the system role.
     *
     * @param  array<string, string>  $trustedContext
     * @return array<int, array{role: string, content: string}>
     */
    public static function buildMessages(string $systemPrompt, string $userInput, array $trustedContext = []): array
    {
        $messages = [
            [
                'role' => 'system',
                'content' => self::sanitize($systemPrompt),
            ],
        ];

        foreach ($trustedContext as $label => $context) {
            if (! is_string($context) || trim($context) === '') {
                continue;
            }

            $messages[] = [
                'role' => 'system',
                'content' => self::wrapUserContent($context, (string) $label),
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => self::wrapUserContent($userInput),
        ];

        return $messages;
    }
}
