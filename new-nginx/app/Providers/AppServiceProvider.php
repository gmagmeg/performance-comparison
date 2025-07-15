<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Instrumentation\Configurator;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Export\TransportInterface;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\SpanExporter\ConsoleSpanExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\Contrib\Otlp\SpanExporter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->initializeOpenTelemetry();
    }

    private function initializeOpenTelemetry(): void
    {
        $resource = ResourceInfoFactory::emptyResource()->merge(
            ResourceInfo::create(Attributes::create([
                'service.name' => env('OTEL_SERVICE_NAME', 'nginx-fpm-app'),
                'service.version' => env('OTEL_SERVICE_VERSION', '1.0.0'),
            ]))
        );

        $transport = (new OtlpHttpTransportFactory())->create(
            env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://jaeger:4318') . '/v1/traces',
            'application/x-protobuf'
        );

        $spanExporter = new SpanExporter($transport);

        $tracerProvider = TracerProvider::builder()
            ->setResource($resource)
            ->addSpanProcessor(new SimpleSpanProcessor($spanExporter))
            ->setSampler(new AlwaysOnSampler())
            ->build();

        Sdk::builder()
            ->setTracerProvider($tracerProvider)
            ->setAutoShutdown(true)
            ->buildAndRegisterGlobal();
    }
}
