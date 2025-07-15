<?php

namespace App;

use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Trace\SpanInterface;

class Tracer
{
    public readonly TracerInterface $rootTracer;
    public readonly SpanInterface $rootSpan;
    public readonly \OpenTelemetry\Context\ScopeInterface $activeRootSpan;

    public function __construct(
      private bool $enabled = true,
      private string $rootTraceName = 'app',
      private string $rootSpanName = 'api-root',
      private array $spans = []
      )
    {
        $this->enabled = $enabled;
        if (!$this->enabled) {
            return;
        }

        $this->rootTracer = Globals::tracerProvider()->getTracer($rootTraceName);
        $this->rootSpan = $this->rootTracer->spanBuilder($rootSpanName)
            ->startSpan();
        $this->activeRootSpan = $this->rootSpan->activate();
    }

    public function startSpan(string $spanName): void
    {
        if (!$this->enabled) {
            return;
        }

        $span = $this->rootTracer->spanBuilder($spanName)
            ->startSpan();
        $this->spans[$spanName] = $span;
    }

    public function endSpan(string $spanName): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!isset($this->spans[$spanName])) {
            throw new \Exception("$spanName は設定されていません。");
        }

        $this->spans[$spanName]->end();
        unset($this->spans[$spanName]);
    }

    public function __destruct()
    {
        if (!$this->enabled) {
            return;
        }

        $this->activeRootSpan->detach();
        $this->rootSpan->end();
    }
}