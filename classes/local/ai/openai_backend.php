<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace qbank_aigen\local\ai;

use core\http_client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Calls any OpenAI-compatible chat completions API (OpenAI, Azure, Groq, Ollama, vLLM...).
 *
 * Useful for sites that have not configured the core AI subsystem, or that want
 * to use a self-hosted model so course content never leaves their servers.
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class openai_backend implements backend {
    /** @var string Default endpoint. */
    public const DEFAULT_ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    /** @var string Default model. */
    public const DEFAULT_MODEL = 'gpt-4o-mini';

    /**
     * An endpoint and a model are required. The key is optional for self-hosted models.
     *
     * @return bool
     */
    public function is_available(): bool {
        return (bool) $this->get_endpoint() && (bool) $this->get_model();
    }

    /**
     * The administrator accepted the terms of the provider when configuring it.
     *
     * @param int $userid
     * @return bool
     */
    public function requires_policy_acceptance(int $userid): bool {
        return false;
    }

    /**
     * Call the chat completions API in JSON mode.
     *
     * @param string $prompt
     * @param \context $context
     * @param int $userid
     * @return string
     */
    public function generate(string $prompt, \context $context, int $userid): string {
        $headers = ['Content-Type' => 'application/json'];
        $apikey = (string) get_config('qbank_aigen', 'apikey');
        if ($apikey !== '') {
            $headers['Authorization'] = 'Bearer ' . $apikey;
        }

        try {
            $response = \core\di::get(http_client::class)->request('POST', $this->get_endpoint(), [
                'headers' => $headers,
                'json' => [
                    'model' => $this->get_model(),
                    'temperature' => 0.4,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are an expert assessment designer. You only answer with JSON.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ],
                'timeout' => 120,
                'http_errors' => false,
            ]);
        } catch (GuzzleException $e) {
            throw new \moodle_exception('aierror', 'qbank_aigen', '', $e->getMessage());
        }

        $data = json_decode((string) $response->getBody(), true);
        if ($response->getStatusCode() !== 200) {
            $message = $data['error']['message'] ?? ('HTTP ' . $response->getStatusCode());
            throw new \moodle_exception('aierror', 'qbank_aigen', '', $message);
        }
        $content = $data['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || $content === '') {
            throw new \moodle_exception('aierror', 'qbank_aigen', '', 'Empty response');
        }
        return $content;
    }

    /**
     * Configured endpoint.
     *
     * @return string
     */
    private function get_endpoint(): string {
        return trim((string) (get_config('qbank_aigen', 'apiendpoint') ?: self::DEFAULT_ENDPOINT));
    }

    /**
     * Configured model.
     *
     * @return string
     */
    private function get_model(): string {
        return trim((string) (get_config('qbank_aigen', 'model') ?: self::DEFAULT_MODEL));
    }
}
