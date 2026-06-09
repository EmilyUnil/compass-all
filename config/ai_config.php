<?php
/**
 * Настройки ИИ-анализа
 *
 * GROQ (рекомендуется — полностью бесплатно):
 *   Получить ключ: https://console.groq.com → API Keys → Create API Key
 *
 * Gemini (Google AI Studio — бесплатный тир, но может быть ограничен):
 *   Получить ключ: https://aistudio.google.com → Get API key
 */

// --- Groq (приоритет) ---
define('GROQ_API_KEY', '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');

// --- Gemini (резерв, если Groq не задан) ---
define('GEMINI_API_KEY', 'AQ.Ab8RN6JmDOmLPOkomRW0Dr0qZt2lpexkKWoPUAB3oejYV8h6BQ');
