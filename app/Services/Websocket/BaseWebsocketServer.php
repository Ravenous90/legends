<?php

namespace App\Services\Websocket;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

abstract class BaseWebsocketServer implements MessageComponentInterface
{
    const MAX_AUTH_RETRIES_COUNT = 5;

    protected $connections;

    protected $authenticatedConnections = [];

    protected $authenticationCount = [];

    abstract public function onOpen(ConnectionInterface $connection): void;

    abstract public function onClose(ConnectionInterface $connection): void;

    abstract public function onError(ConnectionInterface $connection, \Exception $exception): void;

    abstract public function onMessage(ConnectionInterface $from, $message): void;

    protected function sendSuccessMessage(ConnectionInterface $connection, string $message, array $additionalData = []): void
    {
        $result = [
            'status' => 'success',
            'message' => $message,
        ];

        if (count($additionalData) > 0) {
            foreach ($additionalData as $field => $value) {
                $result[$field] = $value;
            }
        }

        $connection->send(json_encode($result));
    }

    protected function sendFailedMessage(ConnectionInterface $connection, string $message, bool $isCloseConnection = false): void
    {
        $connection->send(json_encode([
            'status' => 'failed',
            'message' => $message,
        ]));

        if ($isCloseConnection) {
            $connection->close();
        }
    }
}