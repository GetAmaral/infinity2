<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\PerformanceMonitor;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class PerformanceMonitoringSubscriber implements EventSubscriberInterface
{
    private const SLOW_REQUEST_THRESHOLD = 1.0; // 1 second

    public function __construct(
        private readonly PerformanceMonitor $performanceMonitor,
        #[Autowire(service: 'monolog.logger.app')]
        private readonly LoggerInterface $appLogger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1000],
            KernelEvents::RESPONSE => ['onKernelResponse', -1000],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $requestId = $this->generateRequestId();

        $request->attributes->set('_request_id', $requestId);
        $request->attributes->set('_start_time', microtime(true));

        $this->performanceMonitor->startTimer('request_' . $requestId);

        $this->appLogger->info('Request started', [
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        ]);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $requestId = $request->attributes->get('_request_id');
        $startTime = $request->attributes->get('_start_time');

        if (!$requestId || !$startTime) {
            return;
        }

        $duration = $this->performanceMonitor->endTimer('request_' . $requestId);
        $statusCode = $response->getStatusCode();

        $logData = [
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'status_code' => $statusCode,
            'duration_ms' => round($duration * 1000, 2),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];

        // Log slow requests as warnings
        if ($duration > self::SLOW_REQUEST_THRESHOLD) {
            $this->appLogger->warning('Slow request detected', $logData);
        }

        // Log error responses
        if ($statusCode >= 400) {
            $logData['response_content'] = $this->getSafeResponseContent($response);

            if ($statusCode >= 500) {
                $this->appLogger->error('Server error response', $logData);
            } else {
                $this->appLogger->info('Client error response', $logData);
            }
        } else {
            $this->appLogger->info('Request completed', $logData);
        }
    }

    private function generateRequestId(): string
    {
        return substr(uniqid('req_', true), 0, 16);
    }

    private function getSafeResponseContent(Response $response): ?string
    {
        $content = $response->getContent();

        // Limit content length to prevent log bloat
        if (strlen($content) > 1000) {
            $content = substr($content, 0, 1000) . '... [truncated]';
        }

        return $content;
    }
}