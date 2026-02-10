<?php

namespace AwardWallet\MainBundle\Service\AIModel;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class TokenCounter
{
    /**
     * Count tokens for ChatGPT (GPT-3.5/GPT-4) models.
     *
     * This is a simplified approximation based on GPT tokenization patterns
     */
    public static function countGptTokens(string $text): int
    {
        // Basic cleaning
        $text = trim($text);

        if (empty($text)) {
            return 0;
        }

        // ChatGPT tokenization approximation rules:
        // - Average English words are ~1.3 tokens
        // - Spaces and punctuation are separate tokens
        // - Numbers and special characters often split into multiple tokens

        // Count words (rough starting point)
        $wordCount = str_word_count($text);

        // Count non-alphanumeric characters (spaces, punctuation, etc.)
        $nonAlphaNumCount = preg_match_all('/[^a-zA-Z0-9]/', $text);

        // Count numbers (digits usually become individual tokens)
        $digitCount = preg_match_all('/\d/', $text);

        // Apply GPT-specific token approximation formula
        $tokenEstimate = ($wordCount * 1.3) + $nonAlphaNumCount + ($digitCount * 0.5);

        return max(1, (int) round($tokenEstimate));
    }

    /**
     * Count tokens for DeepSeek models.
     *
     * This is a simplified approximation based on similar tokenization patterns
     */
    public static function countDeepSeekTokens(string $text): int
    {
        // Basic cleaning
        $text = trim($text);

        if (empty($text)) {
            return 0;
        }

        // DeepSeek tends to have slightly different tokenization patterns
        // This is an approximation based on similar patterns to other models

        // Count words
        $wordCount = str_word_count($text);

        // Count characters (for Asian languages and special chars handling)
        $charCount = mb_strlen($text, 'UTF-8');

        // Count non-alphanumeric characters
        $nonAlphaNumCount = preg_match_all('/[^a-zA-Z0-9]/', $text);

        // DeepSeek tokenization approximation formula, 1 English character ≈ 0.3 token.
        $tokenEstimate = ($wordCount * 1.3) + ($charCount * 0.3) + $nonAlphaNumCount;

        return max(1, (int) round($tokenEstimate));
    }

    /**
     * Estimate token count for multilingual text using Claude-specific ratios.
     *
     * This method analyzes the input text by character types and applies
     * different token-to-character ratios based on language characteristics:
     *
     * - Latin scripts (English, European languages): ~3.5 chars/token
     * - Cyrillic scripts (Russian, Bulgarian, etc.): ~2.8 chars/token
     * - CJK ideographs (Chinese, Japanese kanji, Korean hanja): ~1.5 chars/token
     * - Arabic script: ~2.5 chars/token
     * - Other scripts: ~3.0 chars/token (fallback)
     *
     * The ratios are based on empirical observations of Claude's tokenization
     * and provide more accurate estimates than single-ratio approaches.
     *
     * @return int Estimated number of tokens
     */
    public static function countClaudeTokens(string $text): int
    {
        // More sophisticated token estimation for Claude that handles multiple languages
        $length = mb_strlen($text, 'UTF-8');

        // Count different character types for better estimation
        $latinCount = preg_match_all('/[a-zA-Z0-9\s\p{P}]/u', $text);
        $cyrillicCount = preg_match_all('/[\p{Cyrillic}]/u', $text);
        $cjkCount = preg_match_all('/[\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]/u', $text);
        $arabicCount = preg_match_all('/[\p{Arabic}]/u', $text);
        $otherCount = $length - $latinCount - $cyrillicCount - $cjkCount - $arabicCount;

        // Apply different ratios based on character types
        // These ratios are based on empirical observations of Claude tokenization
        $estimatedTokens = 0;

        // English/Latin: ~3.5 characters per token
        $estimatedTokens += ceil($latinCount / 3.5);

        // Cyrillic (Russian, etc.): ~2.8 characters per token (more compact)
        $estimatedTokens += ceil($cyrillicCount / 2.8);

        // CJK (Chinese, Japanese, Korean): ~1.5 characters per token
        $estimatedTokens += ceil($cjkCount / 1.5);

        // Arabic: ~2.5 characters per token
        $estimatedTokens += ceil($arabicCount / 2.5);

        // Other characters: ~3.0 characters per token
        $estimatedTokens += ceil($otherCount / 3.0);

        // Add some padding for special tokens, formatting, etc.
        $estimatedTokens = (int) ceil($estimatedTokens * 1.1);

        // Minimum of 1 token for non-empty strings
        return max(1, $estimatedTokens);
    }
}
