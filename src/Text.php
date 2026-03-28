<?php

namespace mbootsman\Remindme;

final class Text {
    /**
     * Converts HTML content from Mastodon API into plain text.
     * This is used to extract the command text from the status content.
     */
    public static function fromHtml(string $html): string {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, "UTF-8");
        $text = preg_replace("/\\s+/", " ", $text ?? "") ?? "";
        return trim($text);
    }

    /**
     * Removes the leading bot handle from a text string.
     * Example: if $botHandle is "remindme", and $text is "@remindme hello", returns "hello".
     */
    public static function removeLeadingBotHandle(string $text, string $botHandle): string {
        $pattern = "/^" . preg_quote($botHandle, "/") . "\\s*/i";
        return preg_replace($pattern, "", trim($text)) ?? $text;
    }

    /**
     * Returns true if the text looks like the user is trying to use the bot.
     * Used to avoid replying to random mentions like "@remindme" with no command.
     */
    public static function looksLikeCommand(string $text): bool {
        return (bool)preg_match('/\b(remind\s+me|help|\?|list|cancel|delete)\b/i', $text);
    }

    /**
     * Returns true if the text contains a time expression that the parser can handle.
     * Used to detect public replies like "@remindme in 2 days" on a post.
     */
    public static function looksLikeTimeExpression(string $text): bool {
        return (bool)preg_match(
            '/\b(in\s+\d+\s+(minutes?|hours?|days?|weeks?|months?)|tomorrow|next\s+(monday|tuesday|wednesday|thursday|friday|saturday|sunday)|on\s+\d{4}-\d{2}-\d{2})\b/i',
            $text
        );
    }
}
