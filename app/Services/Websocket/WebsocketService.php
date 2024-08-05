<?php

namespace App\Services\WebSocket;

use App\Constants\WebsocketServerStatusConstant;
use App\Models\Printer;
use App\Models\WebsocketServer;
use App\Models\WebsocketServer as WebsocketServerModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class WebsocketService
{
    public string $wsUrl;
    public string $wsHost;
    public string $wsPort;

    public function __construct()
    {
        $this->setWsConfiguration();
    }

    public function setWsConfiguration()
    {
        $this->wsUrl = config('app.websocket_url');
        $this->wsHost = config('app.websocket_host');
        $this->wsPort = config('app.websocket_port');
    }

    public function checkAuthConnection(array $payload = []): ?array
    {
        if (isset($payload['token'])) {
            $user = $this->checkAuthByToken($payload['token']);
        } else {
            $user = $this->checkAuthByCredentials($payload);
        }

        if (is_null($user)) return null;

        $sessionToken = Str::random(32);

        return [
            'user_id' => $user->id,
            'session_token' => $sessionToken,
        ];
    }

    private function checkAuthByToken(string $personalAccessToken = '')
    {
        return PersonalAccessToken::findToken($personalAccessToken)?->tokenable;
    }

    private function checkAuthByCredentials(array $payload = [])
    {
        $credentials = [
            'email' => $payload['email'] ?? '',
            'password' => $payload['password'] ?? '',
        ];

        auth()->attempt($credentials);

        return Auth::user();
    }

    public function handleAuthMessage(string $receivedSessionToken = '', string $openAuthenticatedConnectionSessionToken = ''): bool
    {
        if ($receivedSessionToken === '' || $openAuthenticatedConnectionSessionToken === '') return false;

        return hash_equals($receivedSessionToken, $openAuthenticatedConnectionSessionToken);
    }

    public function handleServerFailed(string $errorMessage = '')
    {
        WebsocketServer::where([
            'status' => WebsocketServerStatusConstant::STARTED,
            'url' => $this->wsUrl,
        ])->update([
            'status' => WebsocketServerStatusConstant::FAILED,
            'message' => $errorMessage,
        ]);
    }

    public function pingWebsocketServer(?WebsocketServer $websocketServerModel = null): bool
    {
        $fullUrl = $this->getUrlWithPort($websocketServerModel);

        try {
            \Ratchet\Client\connect($fullUrl)->then(function ($connection) {
                $connection->send(json_encode([
                    'type' => 'check',
                    'status' => 'success',
                ]));

                $connection->close();
            }, function ($exception) use ($websocketServerModel) {
                $websocketServerModel->update([
                    'status' => WebsocketServerStatusConstant::FAILED,
                    'message' => 'Cannot connect to server',
                ]);
            });
        } catch (\DomainException $exception) {
            return false;
        }

        return true;
    }

    public function getUrlWithPort(?WebsocketServerModel $websocketServerModel = null)
    {
        $url = $websocketServerModel?->url;
        $port = $websocketServerModel?->port;

        if (str_ends_with($url, '/')) {
            $url = rtrim($url, '/');
        }

        return "$url:$port/";
    }

    public function getPrinters()
    {
        // TODO
//        $printers = Printer::all();

        return [
            'printers' => [1,2,3]
        ];
    }
}