<?php

namespace mbootsman\Remindme;

final class Text {
    public static function fromHtml(string $html): string {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, "UTF-8");
        $text = preg_replace("/\\s+/", " ", $text ?? "") ?? "";
        return trim($text);
    }

    public static function removeLeadingBotHandle(string $text, string $botHandle): string {
        $pattern = "/^" . preg_quote($botHandle, "/") . "\\s*/i";
        return preg_replace($pattern, "", trim($text)) ?? $text;
    }
}
