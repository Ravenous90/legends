<?php

namespace App\Services\Websocket;

use App\Models\Printer;
use Ratchet\ConnectionInterface;

class MerchrWebsocketServer extends BaseWebsocketServer
{
    public WebsocketService $webSocketService;
    public $printers = [];
    public $users = [];

    public function __construct()
    {
        $this->connections = new \SplObjectStorage();
        $this->webSocketService = new WebsocketService();
    }

    public function onOpen(ConnectionInterface $connection): void
    {
        $this->connections->attach($connection);
        $this->sendSuccessMessage($connection, 'Send authentication');
    }

    public function onMessage(ConnectionInterface $from, $message): void
    {
        $payload = json_decode($message, true);
        $type = $payload['type'] ?? '';

        if ($type === '') {
            $this->sendFailedMessage($from, 'No type given', true);
            return;
        }

        if ($type !== 'authenticate' && !$this->checkAuth($from, $payload)) {
            $this->sendFailedMessage($from, 'Auth failed', true);
            return;
        }

        switch ($type) {
            case 'authenticate':
                $connectionAuthData = $this->webSocketService->checkAuthConnection($payload);

                if (is_null($connectionAuthData)) {
                    $this->handleFailAuthConnection($from);
                } else {
                    $this->handleSuccessAuthConnection($from, $connectionAuthData);
                }
                break;
            case 'get_printers':
                $printers = $this->webSocketService->getPrinters();
                $this->sendSuccessMessage($from, '', ['printers' => $printers]);

                break;
            case 'set_printer':
                if (isset($payload['printer_id']) && Printer::where('id', $payload['printer_id'])->exists()) {
                    $this->printers[$payload['printer_id']] = $from->resourceId;
                } else {
                    $this->sendFailedMessage($from, 'No printer received or not found');
                }
                break;
            case 'send_artwork':
                if (isset($payload['printer_id']) && isset($this->printers[$payload['printer_id']])) {
                    $resourceId = $this->printers[$payload['printer_id']];
                }

                if (is_null($resourceId)) {
                    $this->sendFailedMessage($from, 'Connected printer not found');
                }

                $messageSent = $this->sendMessageToClient($resourceId, $message);

                if (!$messageSent) {
                    $this->sendFailedMessage($from, 'Connected printer not found');
                }

                break;
            case 'user_notification':
                $resourceId = $this->users[$payload['user_id'] ?? 0] ?? null;

                if (is_null($resourceId) || !isset($this->authenticatedConnections[$resourceId])) {
                    $this->sendFailedMessage($from, 'Connected user not found');
                    break;
                }

                $messageSent = $this->sendMessageToClient($resourceId, $payload['message'] ?? '');

                if ($messageSent) {
                    $this->sendSuccessMessage($from, 'Notification was sent successfully');
                } else {
                    $this->sendFailedMessage($from, 'Notification was not sent');
                }

                break;
            default:
                $this->sendFailedMessage($from, 'No message type found');
                break;
        }
    }

    public function onClose(ConnectionInterface $connection): void
    {
        $connection->send(json_encode([
            'status' => 'disconnected',
            'message' => 'Connection was closed',
        ]));

        unset($this->authenticatedConnections[$connection->resourceId]);
        unset($this->authenticationCount[$connection->resourceId]);

        $this->connections->detach($connection);
    }

    public function onError(ConnectionInterface $connection, \Exception $exception): void
    {
        $this->sendFailedMessage($connection, $exception->getMessage(), true);
    }

    protected function handleFailAuthConnection(ConnectionInterface $connection)
    {
        $count = $this->authenticationCount[$connection->resourceId] ?? 0;
        $authenticationCount = $count + 1;

        if ($authenticationCount > self::MAX_AUTH_RETRIES_COUNT) {
            unset($this->authenticatedConnections[$connection->resourceId]);
            $this->sendFailedMessage($connection, 'Authentication failed, connection was closed', true);
        } else {
            $this->sendFailedMessage($connection, 'Authentication failed');
            $this->authenticationCount[$connection->resourceId] = $authenticationCount;
        }
    }

    protected function handleSuccessAuthConnection(ConnectionInterface $connection, array $connectionAuthData)
    {
        $sessionToken = $connectionAuthData['session_token'] ?? '';

        if ($sessionToken === '') {
            $this->sendFailedMessage($connection, 'Session token was not found');
        }

        $this->authenticatedConnections[$connection->resourceId]['is_authenticate'] = true;
        $this->authenticatedConnections[$connection->resourceId]['session_token'] = hash_hmac('sha256', $sessionToken, config('app.store_encrypt_key'));

        unset($this->authenticationCount[$connection->resourceId]);

        if (isset($connectionAuthData['user_id'])) {
            $this->users[$connectionAuthData['user_id']] = $connection->resourceId;
        }

        $this->sendSuccessMessage($connection, 'Authentication successfully', ['session_token' => $sessionToken]);
    }

    protected function checkAuth(ConnectionInterface $connection, array $payload = []): bool
    {
        $receivedSessionToken = $payload['session_token'] ?? '';
        $openAuthenticatedConnectionSessionToken = $this->authenticatedConnections[$connection->resourceId]['session_token'] ?? '';

        if ($receivedSessionToken === '') {
            $this->sendFailedMessage($connection, 'No session token provided', true);
            return false;
        }

        if ($openAuthenticatedConnectionSessionToken === '') {
            $this->sendFailedMessage($connection, 'No authenticated connection found, try to authenticate', true);
            return false;
        }

        $receivedSessionTokenHash = hash_hmac('sha256', $receivedSessionToken, config('app.store_encrypt_key'));

        $checkAuth = $this->webSocketService->handleAuthMessage($receivedSessionTokenHash, $openAuthenticatedConnectionSessionToken);

        if (!$checkAuth) {
            $this->sendFailedMessage($connection, 'Message authentication has failed', true);
            return false;
        }

        return true;
    }

    private function sendMessageToClient(?string $resourceId = null, string $message = '')
    {
        $result = false;

        foreach ($this->connections as $connection) {
            if (intval($resourceId) === intval($connection->resourceId)) {
                $connection->send($message);
                $result = true;
                break;
            }
        }

        return $result;
    }
}