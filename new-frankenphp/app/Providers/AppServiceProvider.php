<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
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
        // CLI実行時の環境変数チェック
        $endpoint = $this->getOtelEndpoint();
        $serviceName = env('OTEL_SERVICE_NAME', 'franken-php-app');
        
        // デバッグ情報をログに出力（開発環境のみ）
        if (app()->environment('local')) {
            \Log::info('OpenTelemetry initialization', [
                'service_name' => $serviceName,
                'endpoint' => $endpoint,
                'is_cli' => app()->runningInConsole(),
                'environment' => app()->environment(),
            ]);
        }

        $resource = ResourceInfoFactory::emptyResource()->merge(
            ResourceInfo::create(Attributes::create([
                'service.name' => $serviceName,
                'service.version' => env('OTEL_SERVICE_VERSION', '1.0.0'),
                'deployment.environment' => app()->environment(),
                'runtime.name' => app()->runningInConsole() ? 'cli' : 'web',
            ]))
        );

        try {
            $transport = (new OtlpHttpTransportFactory())->create(
                $endpoint . '/v1/traces',
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
                
        } catch (\Exception $e) {
            // OpenTelemetry初期化エラーをログに記録（アプリケーションの実行は継続）
            \Log::warning('OpenTelemetry initialization failed', [
                'error' => $e->getMessage(),
                'endpoint' => $endpoint,
            ]);
        }
    }
    
    private function getOtelEndpoint(): string
    {
        // CLI実行時とWeb実行時で異なるエンドポイントを使用
        $defaultEndpoint = app()->runningInConsole() 
            ? 'http://localhost:4318'  // CLI実行時
            : 'http://jaeger:4318';    // Docker Web実行時
            
        return env('OTEL_EXPORTER_OTLP_ENDPOINT', $defaultEndpoint);
    }
}
