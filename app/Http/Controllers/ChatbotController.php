<?php

namespace App\Http\Controllers;

use App\Exceptions\ChatbotException;
use App\Http\Requests\ChatbotRequest;
use App\Services\GroqChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Throwable;

class ChatbotController extends Controller
{
    public function index(): View
    {
        return view('Chatbot.index');
    }

    public function message(
        ChatbotRequest $request,
        GroqChatbotService $chatbot
    ): JsonResponse {
        try {
            $answer = $chatbot->respond(
                $request->string('message')->toString()
            );
        } catch (ChatbotException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->httpStatus());
        } catch (Throwable) {
            return response()->json([
                'message' => 'Não foi possível obter uma resposta agora. Tente novamente em alguns instantes.',
            ], 503);
        }

        return response()->json([
            'answer' => $answer,
        ]);
    }
}
