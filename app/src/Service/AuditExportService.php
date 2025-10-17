<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AuditLog;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Service for exporting audit logs to various formats
 */
final class AuditExportService
{
    /**
     * Export audit logs to CSV format
     *
     * @param AuditLog[] $auditLogs
     */
    public function exportToCsv(array $auditLogs): StreamedResponse
    {
        $callback = function () use ($auditLogs) {
            $handle = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($handle, [
                'Timestamp',
                'Action',
                'Entity Class',
                'Entity ID',
                'User',
                'User Email',
                'Changes',
                'IP Address',
                'User Agent',
            ]);

            // Data rows
            foreach ($auditLogs as $log) {
                $metadata = $log->getMetadata() ?? [];

                fputcsv($handle, [
                    $log->getCreatedAt()->format('Y-m-d H:i:s'),
                    $log->getAction(),
                    $log->getEntityClass(),
                    $log->getEntityId()->toString(),
                    $log->getUser()?->getName() ?? 'System',
                    $log->getUser()?->getEmail() ?? 'N/A',
                    json_encode($log->getChanges()),
                    $metadata['ip_address'] ?? 'N/A',
                    $metadata['user_agent'] ?? 'N/A',
                ]);
            }

            fclose($handle);
        };

        return new StreamedResponse($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="audit_export_' . date('Y-m-d_His') . '.csv"',
        ]);
    }

    /**
     * Export audit logs to JSON format
     *
     * @param AuditLog[] $auditLogs
     */
    public function exportToJson(array $auditLogs): JsonResponse
    {
        $data = array_map(function (AuditLog $log) {
            $metadata = $log->getMetadata() ?? [];

            return [
                'id' => $log->getId()->toString(),
                'timestamp' => $log->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'action' => $log->getAction(),
                'entity' => [
                    'class' => $log->getEntityClass(),
                    'id' => $log->getEntityId()->toString(),
                ],
                'user' => $log->getUser() ? [
                    'id' => $log->getUser()->getId()->toString(),
                    'name' => $log->getUser()->getName(),
                    'email' => $log->getUser()->getEmail(),
                ] : null,
                'changes' => $log->getChanges(),
                'metadata' => [
                    'ip_address' => $metadata['ip_address'] ?? null,
                    'user_agent' => $metadata['user_agent'] ?? null,
                    'user_email' => $metadata['user_email'] ?? null,
                ],
            ];
        }, $auditLogs);

        $response = new JsonResponse([
            'export_date' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'total_records' => count($data),
            'data' => $data,
        ]);

        $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Set download headers
        $filename = 'audit_export_' . date('Y-m-d_His') . '.json';
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");

        return $response;
    }
}
