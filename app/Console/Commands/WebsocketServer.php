<?php

namespace app\Console\Commands;

use App\Constants\WebsocketServerStatusConstant;
use App\Models\WebsocketServer as WebsocketServerModel;
use App\Services\WebSocket\MerchrWebsocketServer;
use App\Services\WebSocket\WebsocketService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;


class WebsocketServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:server {process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Websocket server command';

    private $websocketService;

    private $isCheck = false;
    private $isRestart = false;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->websocketService = new WebsocketService();
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $process = $this->argument('process');

        if (method_exists($this, $process)) {
            return $this->{$process}();
        } else {
            $this->error("Command $process not found");
        }
    }

    public function start()
    {
        $this->output->info('Starting server');
        $pid = getmypid();

        try {
            $merchrWebsocket = new MerchrWebsocketServer();
            $checkedApp = new WsServer($merchrWebsocket);

            $server = IoServer::factory(
                new HttpServer($checkedApp),
                $this->websocketService->wsPort,
                $this->websocketService->wsHost,
            );

            $checkedApp->enableKeepAlive($server->loop);

            $data = [
                'url' => $this->websocketService->wsUrl,
                'host' => $this->websocketService->wsHost,
                'port' => $this->websocketService->wsPort,
                'status' => WebsocketServerStatusConstant::STARTED,
                'token' => Str::random(32),
                'command_pid' => $pid,
            ];

            WebsocketServerModel::create($data);

            $this->output->success("Server " . $this->websocketService->wsUrl . " was started");

            $server->run();
        } catch (\Exception $exception) {
            $this->output->error($exception->getMessage());
            $this->websocketService->handleServerFailed($exception->getMessage());
        }
    }

    public function stop()
    {
        $this->output->info('Stopping server');

        try {
            $websocketServerModel = WebsocketServerModel::where([
                'url' => $this->websocketService->wsUrl,
                'host' => $this->websocketService->wsHost,
                'port' => $this->websocketService->wsPort,
                'status' => WebsocketServerStatusConstant::STARTED,
            ])->first();

            if (is_null($websocketServerModel)) {
                $this->output->error('Server not found');
                return;
            }

            $pid = $websocketServerModel->command_pid;
            shell_exec("kill $pid");

            $stopType = 'manually';

            if ($this->isRestart) {
                $stopType = 'from manually restarting';
            }
            if ($this->isCheck) {
                $stopType = 'from check cron';
            }

            $websocketServerModel->update([
                'status' => WebsocketServerStatusConstant::MANUALLY_STOPPED,
                'message' => 'Server was stopped ' . $stopType,
            ]);

            $this->output->success("Server was stopped");
        } catch (\Exception $exception) {
            $this->output->error($exception->getMessage());
            $this->websocketService->handleServerFailed($exception->getMessage());
        }
    }

    public function restart()
    {
        $this->isRestart = true;

        try {
            $this->stop();
            $this->start();
        } catch (\Exception $exception) {
            $this->output->error($exception->getMessage());
            return;
        }

        $this->output->success('Server was restarted successfully');
    }

    public function check()
    {
        $this->output->info('Checking server');
        $this->isCheck = true;

        try {
            $websocketServerModel = WebsocketServerModel::where([
                'url' => $this->websocketService->wsUrl,
                'host' => $this->websocketService->wsHost,
                'port' => $this->websocketService->wsPort,
                'status' => WebsocketServerStatusConstant::STARTED,
            ])->first();

            if (is_null($websocketServerModel)) {
                $this->start();
                return;
            } else {
                $isProcessRunning = posix_getpgid($websocketServerModel->proccess_pid);
                $isWebsocketServerRunning = $this->websocketService->pingWebsocketServer($websocketServerModel);

                if ($isProcessRunning === false || !$isWebsocketServerRunning) {
                    $this->restart();
                    return;
                }

                $this->output->success("Server is running");
            }
        } catch (\Exception $exception) {
            $this->output->error($exception->getMessage());
            $this->websocketService->handleServerFailed($exception->getMessage());
        }
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array<string, string>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'process' => 'Missing websocket server command name',
        ];
    }
}