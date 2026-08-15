<?php

namespace App\Console\Commands;

use App\Support\OpenApiDocument;
use Illuminate\Console\Command;
use JsonException;
use RuntimeException;

class GenerateOpenApiDocument extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openapi:generate {--check : Fail if the committed artifact is not current}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the committed OpenAPI document from the current API contract';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $document = OpenApiDocument::toJson();
        } catch (JsonException $exception) {
            throw new RuntimeException('The OpenAPI document could not be encoded.', previous: $exception);
        }

        $path = OpenApiDocument::outputPath();

        if ($this->option('check')) {
            $current = is_file($path) ? file_get_contents($path) : false;

            if (! is_string($current) || ! hash_equals($document, $current)) {
                $this->components->error('The committed OpenAPI document is missing or out of date. Run php artisan openapi:generate.');

                return self::FAILURE;
            }

            $this->components->info('The committed OpenAPI document is current.');

            return self::SUCCESS;
        }

        if (file_put_contents($path, $document) === false) {
            throw new RuntimeException("The OpenAPI document could not be written to [{$path}].");
        }

        $this->components->info("OpenAPI document generated at [{$path}].");

        return self::SUCCESS;
    }
}
