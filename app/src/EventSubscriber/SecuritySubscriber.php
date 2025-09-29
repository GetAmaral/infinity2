<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class SecuritySubscriber implements EventSubscriberInterface
{
    // Common attack patterns to detect and block
    private const MALICIOUS_PATTERNS = [
        '/\b(?:union|select|insert|update|delete|drop|create|alter)\b/i', // SQL injection
        '/<script[^>]*>.*?<\/script>/i', // XSS
        '/javascript:/i', // Javascript protocol
        '/vbscript:/i', // VBScript protocol
        '/on\w+\s*=/i', // Event handlers
        '/eval\s*\(/i', // eval() function
        '/base64_decode\s*\(/i', // Base64 decode attempts
        '/system\s*\(/i', // System command execution
        '/exec\s*\(/i', // Exec function
        '/shell_exec\s*\(/i', // Shell execution
        '/passthru\s*\(/i', // Passthru function
        '/file_get_contents\s*\(/i', // File read attempts
        '/\.\.\/\.\.\//i', // Path traversal
        '/\.\.\\\.\.\\/i', // Windows path traversal
    ];

    private const SUSPICIOUS_USER_AGENTS = [
        '/sqlmap/i',
        '/nikto/i',
        '/burpsuite/i',
        '/nessus/i',
        '/w3af/i',
        '/nmap/i',
        '/masscan/i',
        '/zap/i',
        '/dirb/i',
        '/dirbuster/i',
    ];

    private const RATE_LIMIT_HEADERS = [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
    ];

    public function __construct(
        #[Autowire(service: 'monolog.logger.security')]
        private readonly LoggerInterface $securityLogger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 512],
            KernelEvents::RESPONSE => ['onKernelResponse', -512],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $this->checkForMaliciousRequests($request);
        $this->checkForSuspiciousUserAgents($request);
        $this->checkForExcessiveRequestSize($request);
        $this->logSecurityRelevantRequests($request);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();

        $this->addSecurityHeaders($response);
        $this->addRateLimitHeaders($response);
    }

    private function checkForMaliciousRequests(Request $request): void
    {
        $allInput = $this->getAllRequestInput($request);

        foreach (self::MALICIOUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $allInput)) {
                $this->securityLogger->warning('Malicious request pattern detected', [
                    'pattern' => $pattern,
                    'url' => $request->getRequestUri(),
                    'method' => $request->getMethod(),
                    'ip' => $request->getClientIp(),
                    'user_agent' => $request->headers->get('User-Agent'),
                    'matched_content' => substr($allInput, 0, 500),
                ]);

                // In production, you might want to block the request:
                // throw new BadRequestHttpException('Malicious request detected');
                break;
            }
        }
    }

    private function checkForSuspiciousUserAgents(Request $request): void
    {
        $userAgent = $request->headers->get('User-Agent', '');

        foreach (self::SUSPICIOUS_USER_AGENTS as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                $this->securityLogger->warning('Suspicious user agent detected', [
                    'user_agent' => $userAgent,
                    'url' => $request->getRequestUri(),
                    'method' => $request->getMethod(),
                    'ip' => $request->getClientIp(),
                ]);
                break;
            }
        }

        // Empty or missing user agent can also be suspicious
        if (empty($userAgent)) {
            $this->securityLogger->info('Request with empty user agent', [
                'url' => $request->getRequestUri(),
                'method' => $request->getMethod(),
                'ip' => $request->getClientIp(),
            ]);
        }
    }

    private function checkForExcessiveRequestSize(Request $request): void
    {
        $maxRequestSize = 10 * 1024 * 1024; // 10MB

        $contentLength = (int) $request->headers->get('Content-Length', 0);

        if ($contentLength > $maxRequestSize) {
            $this->securityLogger->warning('Excessively large request detected', [
                'content_length' => $contentLength,
                'max_allowed' => $maxRequestSize,
                'url' => $request->getRequestUri(),
                'method' => $request->getMethod(),
                'ip' => $request->getClientIp(),
            ]);
        }
    }

    private function logSecurityRelevantRequests(Request $request): void
    {
        // Log admin/sensitive endpoints
        if (str_contains($request->getPathInfo(), '/admin') ||
            str_contains($request->getPathInfo(), '/api') ||
            $request->getMethod() !== 'GET') {

            $this->securityLogger->info('Security-relevant request', [
                'url' => $request->getRequestUri(),
                'method' => $request->getMethod(),
                'ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent'),
                'referer' => $request->headers->get('Referer'),
            ]);
        }
    }

    private function addSecurityHeaders(Response $response): void
    {
        // Content Security Policy
        $response->headers->set('Content-Security-Policy',
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
            "style-src 'self' 'unsafe-inline'; " .
            "img-src 'self' data: https:; " .
            "font-src 'self' https:; " .
            "connect-src 'self'; " .
            "frame-ancestors 'none'"
        );

        // Additional security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // HSTS (only for HTTPS)
        if ($response->headers->get('X-Forwarded-Proto') === 'https') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
    }

    private function addRateLimitHeaders(Response $response): void
    {
        // Add rate limit information if available
        // This would typically come from your rate limiter
        $response->headers->set('X-RateLimit-Limit', '100');
        $response->headers->set('X-RateLimit-Window', '60');
    }

    private function getAllRequestInput(Request $request): string
    {
        $input = [];

        // Query parameters
        $input[] = http_build_query($request->query->all());

        // POST data
        if ($request->isMethod('POST')) {
            $input[] = http_build_query($request->request->all());
        }

        // Headers (excluding common safe ones)
        foreach ($request->headers->all() as $name => $values) {
            if (!in_array(strtolower($name), ['host', 'accept', 'accept-language', 'accept-encoding', 'connection'])) {
                $input[] = implode('', $values);
            }
        }

        // Request body (for JSON/XML etc.)
        $body = $request->getContent();
        if ($body) {
            $input[] = substr($body, 0, 1000); // Limit to prevent memory issues
        }

        return implode(' ', $input);
    }
}