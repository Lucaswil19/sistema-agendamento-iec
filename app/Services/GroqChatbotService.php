<?php

namespace App\Services;

use App\Exceptions\ChatbotException;
use App\Support\ChatbotKnowledgeBase;
use App\Support\ChatbotPrivacyGuard;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class GroqChatbotService
{
    private const RESPONSES_ENDPOINT = 'https://api.groq.com/openai/v1/responses';

    public function __construct(
        private readonly ChatbotKnowledgeBase $knowledgeBase,
        private readonly ChatbotPrivacyGuard $privacyGuard
    ) {}

    public function respond(string $message): string
    {
        $privacyResponse = $this->privacyGuard->responseFor($message);

        if ($privacyResponse !== null) {
            return $privacyResponse;
        }

        $key = trim((string) config('services.groq.key'));
        $model = trim((string) config('services.groq.model'));

        if ($key === '' || $model === '') {
            throw new ChatbotException(
                'O chatbot ainda não foi configurado. Entre em contato com a administração do sistema.'
            );
        }

        try {
            $response = Http::withToken($key)
                ->acceptJson()
                ->asJson()
                ->connectTimeout(5)
                ->timeout(20)
                ->post(self::RESPONSES_ENDPOINT, [
                    'model' => $model,
                    'instructions' => $this->knowledgeBase->instructions(),
                    'input' => $message,
                    'store' => false,
                    'max_output_tokens' => 600,
                ]);
        } catch (ConnectionException) {
            throw new ChatbotException(
                'O serviço do chatbot demorou para responder ou está indisponível. Tente novamente em alguns instantes.'
            );
        } catch (Throwable) {
            throw new ChatbotException(
                'Não foi possível acessar o serviço do chatbot agora. Tente novamente em alguns instantes.'
            );
        }

        if ($response->status() === 429) {
            throw new ChatbotException(
                'O chatbot recebeu muitas solicitações. Aguarde um momento antes de tentar novamente.',
                429
            );
        }

        if (in_array($response->status(), [401, 403], true)) {
            throw new ChatbotException(
                'O chatbot não está disponível por um problema de configuração. Entre em contato com a administração do sistema.'
            );
        }

        if ($response->serverError()) {
            throw new ChatbotException(
                'O serviço do chatbot está temporariamente indisponível. Tente novamente em alguns instantes.'
            );
        }

        if (! $response->successful()) {
            throw new ChatbotException(
                'Não foi possível processar sua pergunta. Revise o texto e tente novamente.',
                422
            );
        }

        try {
            $payload = $response->json();
        } catch (Throwable) {
            throw $this->invalidResponseException();
        }

        if (! is_array($payload)) {
            throw $this->invalidResponseException();
        }

        $status = data_get($payload, 'status');

        if (is_string($status) && $status !== 'completed') {
            throw $this->invalidResponseException();
        }

        $answer = $this->extractOutputText($payload);

        if ($answer === '') {
            throw $this->invalidResponseException();
        }

        return $answer;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractOutputText(array $payload): string
    {
        $directOutput = data_get($payload, 'output_text');

        if (is_string($directOutput) && trim($directOutput) !== '') {
            return trim($directOutput);
        }

        $texts = [];
        $outputItems = data_get($payload, 'output', []);

        if (! is_array($outputItems)) {
            return '';
        }

        foreach ($outputItems as $item) {
            if (! is_array($item) || ($item['type'] ?? null) !== 'message') {
                continue;
            }

            $contentItems = $item['content'] ?? [];

            if (! is_array($contentItems)) {
                continue;
            }

            foreach ($contentItems as $content) {
                if (
                    is_array($content)
                    && ($content['type'] ?? null) === 'output_text'
                    && is_string($content['text'] ?? null)
                    && trim($content['text']) !== ''
                ) {
                    $texts[] = trim($content['text']);
                }
            }
        }

        return trim(implode("\n\n", $texts));
    }

    private function invalidResponseException(): ChatbotException
    {
        return new ChatbotException(
            'O chatbot retornou uma resposta inválida. Tente novamente em alguns instantes.'
        );
    }
}
