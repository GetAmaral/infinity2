<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Service for sending audit anomaly alerts
 *
 * Logs anomalies to security channel and can be extended
 * to send email/Slack notifications for high severity alerts.
 */
final class AuditAlertService
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.security')]
        private readonly LoggerInterface $securityLogger
    ) {}

    /**
     * Send alert for detected anomaly
     *
     * Logs to security channel and can be extended to send
     * email/Slack notifications based on severity.
     *
     * @param array $anomaly Anomaly data with type, severity, message
     */
    public function sendAnomalyAlert(array $anomaly): void
    {
        $severity = $anomaly['severity'] ?? 'low';
        $type = $anomaly['type'] ?? 'unknown';
        $message = $anomaly['message'] ?? 'Anomaly detected';

        // Always log to security channel
        $this->securityLogger->warning('Audit anomaly detected', [
            'anomaly_type' => $type,
            'severity' => $severity,
            'message' => $message,
            'data' => $anomaly['data'] ?? null,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);

        // Future: Send email for high severity
        // if ($severity === 'high') {
        //     $this->sendEmail($anomaly);
        // }

        // Future: Send Slack notification for medium/high severity
        // if (in_array($severity, ['medium', 'high'])) {
        //     $this->sendSlack($anomaly);
        // }
    }

    /**
     * Send multiple anomaly alerts
     *
     * @param array $anomalies Array of anomaly data
     */
    public function sendAnomalyAlerts(array $anomalies): void
    {
        foreach ($anomalies as $anomaly) {
            $this->sendAnomalyAlert($anomaly);
        }
    }

    /**
     * Send capacity warning alert
     *
     * @param array $recommendation Capacity recommendation data
     */
    public function sendCapacityAlert(array $recommendation): void
    {
        if ($recommendation['status'] === 'warning') {
            $this->securityLogger->warning('Audit capacity warning', [
                'status' => $recommendation['status'],
                'message' => $recommendation['message'],
                'recommendation' => $recommendation['recommendation'],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ]);
        }
    }

    // Future methods for email/Slack integration:
    //
    // private function sendEmail(array $anomaly): void
    // {
    //     $email = (new Email())
    //         ->to($_ENV['SECURITY_EMAIL'])
    //         ->subject('🚨 Audit Anomaly Detected')
    //         ->html($this->renderEmailTemplate($anomaly));
    //
    //     $this->mailer->send($email);
    // }
    //
    // private function sendSlack(array $anomaly): void
    // {
    //     $message = new ChatMessage("🚨 Audit Anomaly: {$anomaly['message']}");
    //     $this->slack->send($message);
    // }
}
