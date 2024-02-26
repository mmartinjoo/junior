<?php

namespace Mmartinjoo\Junior\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

use function Laravel\Prompts\info;
use function Laravel\Prompts\text;

class JuniorCommand extends Command
{
    protected $signature = 'junior {action}';

    protected $description = 'Your personal junior developer who writes boilerplate for you';

    private string $url;

    private string $apiKey;

    public function __construct()
    {
        parent::__construct();

        $this->url = 'https://junior-alq2s.ondigitalocean.app';

        $this->apiKey = config('junior-artisan.api_key');
    }

    public function handle()
    {
        if ($this->argument('action') === 'make:model') {
            $this->makeModel();
        }

        if ($this->argument('action') === 'make:crud') {
            $this->makeCrud();
        }

        if ($this->argument('action') === 'make:factory') {
            $this->makeFactory();
        }
    }

    private function makeCrud()
    {
        ['modelName' => $modelName, 'attributes' => $attributes] = $this->userInput();

        $response = Http::withHeader('Authorization', 'Bearer ' . $this->apiKey)
            ->post("{$this->url}/api/cruds", [
                'model' => $modelName,
                'attributes' => $attributes,
            ])
            ->throw();

        $uuid = $response->json('uuid');

        $status = [
            'model' => false,
            'migration' => false,
            'factory' => false,
            'controller' => false,
            'request' => false,
            'resource' => false,
            'create_test' => false,
            'update_test' => false,
            'delete_test' => false,
        ];

        $i = 0;

        while (true) {
            $i++;

            $data = Http::withHeader('Authorization', 'Bearer ' . $this->apiKey)
                ->get("{$this->url}/api/cruds/" . $uuid)
                ->json('data');

            $status = $this->createFileIfReady($status, $data, 'model');

            $status = $this->createFileIfReady($status, $data, 'migration');

            $status = $this->createFileIfReady($status, $data, 'factory');

            $status = $this->createFileIfReady($status, $data, 'controller');

            $status = $this->createFileIfReady($status, $data, 'request');

            $status = $this->createFileIfReady($status, $data, 'resource');

            $status = $this->createFileIfReady($status, $data, 'create_test');

            $status = $this->createFileIfReady($status, $data, 'update_test');

            $status = $this->createFileIfReady($status, $data, 'delete_test');

            if ($data['finished'] === true) {
                $this->addApiEndpoints(substr(Arr::get($data, 'model.filename', ''), 0, -4));

                break;
            }

            if ($i === 120) {
                if ($data['finished'] !== true) {
                    $this->error('Sorry but something went wrong. Please try again later.');
                }

                break;
            }

            sleep(1);
        }
    }

    private function makeModel(): void
    {
        ['modelName' => $modelName, 'attributes' => $attributes] = $this->userInput();

        $response = Http::withHeader('Authorization', 'Bearer ' . $this->apiKey)
            ->post("{$this->url}/api/models", [
                'model' => $modelName,
                'attributes' => $attributes,
            ])
            ->throw();

        $uuid = $response->json('uuid');

        $status = [
            'model' => false,
            'migration' => false,
            'factory' => false,
        ];

        $i = 0;

        while (true) {
            $i++;

            $data = Http::withHeader('Authorization', 'Bearer ' . $this->apiKey)
                ->get("{$this->url}/api/models/" . $uuid)->json('data');

            $status = $this->createFileIfReady($status, $data, 'model');

            $status = $this->createFileIfReady($status, $data, 'migration');

            $status = $this->createFileIfReady($status, $data, 'factory');

            if ($data['finished'] === true) {
                break;
            }

            if ($i === 180) {
                if ($data['finished'] !== true) {
                    $this->error('Sorry but something went wrong. Please try again later.');
                }

                break;
            }

            sleep(1);
        }
    }

    private function makeFactory(): void
    {
        $modelName = text(
            label: "Your existing model's name:",
            required: true,
        );

        $path = 'app' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . $modelName . '.php';

        try {
            $modelCode = file_get_contents(base_path() . DIRECTORY_SEPARATOR . $path);
        } catch (Exception) {
            info("Cannot find model [$path]");

            $modelName = text(
                label: "Your existing model's name:",
                required: true,
            );

            $path = 'app' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . $modelName . '.php';

            $modelCode = file_get_contents(base_path() . DIRECTORY_SEPARATOR . $path);
        }


        if (!$modelCode) {
            $this->error("Unable to load model [$path]");
        }

        $response = Http::withHeader('Authorization', 'Bearer ' . $this->apiKey)
            ->post("{$this->url}/api/factories", [
                'model' => $modelName,
                'model_code' => $modelCode,
            ])
            ->throw();

        $uuid = $response->json('uuid');

        $status = false;

        $i = 0;

        while (true) {
            $i++;

            $data = Http::withHeader('Authorization', 'Bearer ' . $this->apiKey)
                ->get("{$this->url}/api/factories/" . $uuid)
                ->json('data');

            if (!$status && Arr::get($data, 'factory.code')) {
                $this->createFactory($data);

                $status = true;
            }

            if ($data['finished'] === true) {
                break;
            }

            if ($i === 180) {
                if ($data['finished'] !== true) {
                    $this->error('Sorry but something went wrong. Please try again later.');
                }

                break;
            }

            sleep(1);
        }
    }

    private function createModel(array $data): void
    {
        $filename = $data['model']['filename'];

        $path = 'app' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . $filename;

        file_put_contents(base_path() . DIRECTORY_SEPARATOR . $path, $data['model']['code']);

        info("Model [$path] created successfully.");
    }

    private function createMigration(array $data): void
    {
        $filename = $data['migration']['filename'];

        $path = 'database' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . $filename;

        file_put_contents(base_path() . DIRECTORY_SEPARATOR . $path, $data['migration']['code']);

        info("Migration [$path] created successfully.");
    }

    private function createFactory(array $data): void
    {
        $filename = $data['factory']['filename'];

        $path = 'database' . DIRECTORY_SEPARATOR . 'factories' . DIRECTORY_SEPARATOR . $filename;

        file_put_contents(base_path() . DIRECTORY_SEPARATOR . $path, $data['factory']['code']);

        info("Factory [$path] created successfully.");
    }

    private function createController(array $data): void
    {
        $filename = $data['controller']['filename'];

        $path = 'app' . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . $filename;

        file_put_contents(base_path() . DIRECTORY_SEPARATOR . $path, $data['controller']['code']);

        info("Controller [$path] created successfully.");
    }

    private function createRequest(array $data): void
    {
        $filename = $data['request']['filename'];

        $this->ensureRequestsDirectoryExists();

        $path = 'app' . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Requests' . DIRECTORY_SEPARATOR . $filename;

        file_put_contents(base_path() . DIRECTORY_SEPARATOR . $path, $data['request']['code']);

        info("Request [$path] created successfully.");
    }

    private function createResource(array $data): void
    {
        $filename = $data['resource']['filename'];

        $this->ensureResourcesDirectoryExists();

        $path = 'app' . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . $filename;

        file_put_contents(base_path() . DIRECTORY_SEPARATOR . $path, $data['resource']['code']);

        info("Resource [$path] created successfully.");
    }

    private function createCreateTest(array $data): void
    {
        $filename = $data['create_test']['filename'];

        $this->ensureFeatureTestDirectoryExists();

        $path = 'tests' . DIRECTORY_SEPARATOR . 'Feature' . DIRECTORY_SEPARATOR . $filename;

        file_put_contents(base_path() . DIRECTORY_SEPARATOR . $path, $data['create_test']['code']);

        info("Test [$path] created successfully.");
    }

    private function createUpdateTest(array $data): void
    {
        $filename = $data['update_test']['filename'];

        $this->ensureFeatureTestDirectoryExists();

        $path = 'tests' . DIRECTORY_SEPARATOR . 'Feature' . DIRECTORY_SEPARATOR . $filename;

        file_put_contents(base_path() . DIRECTORY_SEPARATOR . $path, $data['update_test']['code']);

        info("Test [$path] created successfully.");
    }

    private function createDeleteTest(array $data): void
    {
        $filename = $data['delete_test']['filename'];

        $this->ensureFeatureTestDirectoryExists();

        $path = 'tests' . DIRECTORY_SEPARATOR . 'Feature' . DIRECTORY_SEPARATOR . $filename;

        file_put_contents(base_path() . DIRECTORY_SEPARATOR . $path, $data['delete_test']['code']);

        info("Test [$path] created successfully.");
    }

    private function addApiEndpoints(string $modelName)
    {
        $path = base_path() . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'api.php';

        $content = file_get_contents($path);

        $endpointName = str($modelName)
            ->lower()
            ->plural();

        $content .= "\r\nRoute::apiResource('$endpointName', \App\Http\Controllers\\" . $modelName . "Controller::class);\r\n";

        file_put_contents($path, $content);

        info("Routes in [$path] created successfully.");
    }

    private function ensureFeatureTestDirectoryExists(): void
    {
        if (!is_dir(base_path() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'Feature' . DIRECTORY_SEPARATOR)) {
            mkdir(base_path() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'Feature' . DIRECTORY_SEPARATOR, 0755, true);
        }
    }

    private function ensureRequestsDirectoryExists(): void
    {
        if (!is_dir(base_path() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Requests')) {
            mkdir(base_path() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Requests', 0755, true);
        }
    }

    private function ensureResourcesDirectoryExists(): void
    {
        if (!is_dir(base_path() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Resources')) {
            mkdir(base_path() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Resources', 0755, true);
        }
    }

    private function createFileIfReady(array &$status, array $responseData, string $type): array
    {
        $typeCamel = Str::camel($type);

        $methodName = 'create' . $typeCamel;

        if (!$status[$type] && Arr::get($responseData, $type . '.code')) {
            $this->{$methodName}($responseData);

            $status[$type] = true;
        }

        return $status;
    }

    private function userInput(): array
    {
        $attributes = [];

        $modelName = text(
            label: 'What is the name of the model?',
            placeholder: 'Only the class name without file extension or namespace',
            required: false,
            validate: fn (string $value) => match (true) {
                strlen($value) < 3 => 'The name must be at least 3 characters',
                default => null,
            },
        );

        foreach (range(1, 20) as $i) {
            $attribute = text(
                label: "Attribute $i:",
                required: $i === 1,
                placeholder: 'ID and timestamps are included by default. Hit enter to stop adding attributes.',
                validate: fn (string $value) => match (true) {
                    strlen($value) === 0 => null,
                    strlen($value) < 3 => 'The attribute name must be at least 3 characters',
                    default => null,
                },
            );

            if (empty($attribute)) {
                break;
            }

            $attributes[] = $attribute;
        }

        if (empty($attributes)) {
            $this->error('You must provide at least one attribute.');
        }

        return [
            'modelName' => $modelName,
            'attributes' => $attributes,
        ];
    }
}

