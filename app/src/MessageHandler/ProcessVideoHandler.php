<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ProcessVideoMessage;
use App\Repository\CourseLectureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ProcessVideoHandler
{
    private const HLS_SEGMENT_DURATION = 6; // seconds

    public function __construct(
        private readonly CourseLectureRepository $lectureRepository,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(service: 'monolog.logger.video_processing')]
        private readonly LoggerInterface $logger,
        #[Autowire(param: 'app.videos.hls_path')]
        private readonly string $hlsBasePath
    ) {}

    public function __invoke(ProcessVideoMessage $message): void
    {
        $lecture = $this->lectureRepository->find($message->getLectureId());

        if (!$lecture) {
            $this->logger->error('Lecture not found for video processing', [
                'lectureId' => $message->getLectureId()
            ]);
            return;
        }

        try {
            // Update status to processing
            $lecture->setProcessingStatus('processing');
            $lecture->setProcessingStep('Starting video processing...');
            $lecture->setProcessingPercentage(0);
            $this->entityManager->flush();

            $originalFile = $message->getOriginalFilePath();
            $outputDir = $this->hlsBasePath . '/' . $lecture->getId()->toString();

            // Create output directory
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // Extract video duration and metadata
            $lecture->setProcessingStep('Extracting video metadata...');
            $lecture->setProcessingPercentage(10);
            $this->entityManager->flush();

            $duration = $this->extractDuration($originalFile);

            // Convert to HLS with multiple qualities
            $lecture->setProcessingStep('Converting to HLS format...');
            $lecture->setProcessingPercentage(20);
            $this->entityManager->flush();

            $this->convertToHLS($originalFile, $outputDir, $lecture);

            // Create master playlist
            $lecture->setProcessingStep('Creating master playlist...');
            $lecture->setProcessingPercentage(90);
            $this->entityManager->flush();

            $this->createMasterPlaylist($outputDir);

            // Update lecture
            $lecture->setVideoPath('/videos/hls/' . $lecture->getId()->toString() . '/master.m3u8');
            $lecture->setLengthSeconds($duration);
            $lecture->setProcessingStatus('completed');
            $lecture->setProcessingStep('Processing complete!');
            $lecture->setProcessingPercentage(100);
            $lecture->setProcessedAt(new \DateTimeImmutable());
            $lecture->setProcessingError(null);

            $this->entityManager->flush();

            // Update parent module and course total length
            $course = $lecture->getCourseModule()->getCourse();
            if ($course) {
                // First update the module total length
                $lecture->getCourseModule()->calculateTotalLengthSeconds();
                // Then update the course total length
                $course->calculateTotalLengthSeconds();
                $this->entityManager->flush();
            }

            $this->logger->info('Video processing completed', [
                'lectureId' => $lecture->getId()->toString(),
                'duration' => $duration
            ]);

        } catch (\Exception $e) {
            $lecture->setProcessingStatus('failed');
            $lecture->setProcessingError($e->getMessage());
            $this->entityManager->flush();

            $this->logger->error('Video processing failed', [
                'lectureId' => $lecture->getId()->toString(),
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    private function extractDuration(string $file): int
    {
        $command = sprintf(
            'ffprobe -v error -show_entries format=duration ' .
            '-of default=noprint_wrappers=1:nokey=1 %s 2>&1',
            escapeshellarg($file)
        );

        $output = shell_exec($command);

        if ($output === null) {
            throw new \RuntimeException('Failed to extract video duration');
        }

        $duration = (float) trim($output);

        if ($duration <= 0) {
            throw new \RuntimeException('Invalid video duration: ' . $duration);
        }

        return (int) round($duration);
    }

    private function convertToHLS(string $inputFile, string $outputDir, $lecture): void
    {
        // Get video resolution to determine which qualities to generate
        $resolution = $this->getVideoResolution($inputFile);
        $qualities = $this->determineQualities($resolution);

        $totalQualities = count($qualities);
        $currentQuality = 0;

        foreach ($qualities as $quality => $config) {
            $currentQuality++;
            $percentage = 20 + (int)(($currentQuality / $totalQualities) * 70); // 20% to 90%

            $lecture->setProcessingStep("Generating quality: {$quality}");
            $lecture->setProcessingPercentage($percentage);
            $this->entityManager->flush();

            $this->generateHLSQuality($inputFile, $outputDir, $quality, $config);
        }
    }

    private function getVideoResolution(string $file): array
    {
        $command = sprintf(
            'ffprobe -v error -select_streams v:0 ' .
            '-show_entries stream=width,height ' .
            '-of csv=s=x:p=0 %s 2>&1',
            escapeshellarg($file)
        );

        $output = shell_exec($command);

        if ($output === null) {
            return ['width' => 1920, 'height' => 1080]; // Default to 1080p
        }

        [$width, $height] = explode('x', trim($output));

        return [
            'width' => (int) $width,
            'height' => (int) $height
        ];
    }

    private function determineQualities(array $resolution): array
    {
        $sourceHeight = $resolution['height'];

        $allQualities = [
            '360p' => [
                'width' => 640,
                'height' => 360,
                'videoBitrate' => '800k',
                'audioBitrate' => '64k',
                'bandwidth' => 800000
            ],
            '480p' => [
                'width' => 854,
                'height' => 480,
                'videoBitrate' => '1400k',
                'audioBitrate' => '96k',
                'bandwidth' => 1400000
            ],
            '720p' => [
                'width' => 1280,
                'height' => 720,
                'videoBitrate' => '2800k',
                'audioBitrate' => '128k',
                'bandwidth' => 2800000
            ],
            '1080p' => [
                'width' => 1920,
                'height' => 1080,
                'videoBitrate' => '5000k',
                'audioBitrate' => '192k',
                'bandwidth' => 5000000
            ],
        ];

        // Only generate qualities up to source resolution
        $qualities = [];
        foreach ($allQualities as $name => $config) {
            if ($config['height'] <= $sourceHeight) {
                $qualities[$name] = $config;
            }
        }

        // Always have at least 360p
        if (empty($qualities)) {
            $qualities['360p'] = $allQualities['360p'];
        }

        return $qualities;
    }

    private function generateHLSQuality(
        string $inputFile,
        string $outputDir,
        string $quality,
        array $config
    ): void {
        $command = sprintf(
            'ffmpeg -i %s ' .
            '-vf scale=%d:%d ' .
            '-c:v libx264 -preset medium -crf 23 -b:v %s -maxrate %s -bufsize %s ' .
            '-c:a aac -b:a %s -ac 2 ' .
            '-hls_time %d ' .
            '-hls_playlist_type vod ' .
            '-hls_segment_type mpegts ' .
            '-hls_segment_filename %s ' .
            '-f hls %s 2>&1',
            escapeshellarg($inputFile),
            $config['width'],
            $config['height'],
            $config['videoBitrate'],
            $config['videoBitrate'],
            $config['videoBitrate'],
            $config['audioBitrate'],
            self::HLS_SEGMENT_DURATION,
            escapeshellarg($outputDir . '/' . $quality . '_%03d.ts'),
            escapeshellarg($outputDir . '/' . $quality . '.m3u8')
        );

        $this->logger->info('Generating HLS quality', [
            'quality' => $quality,
            'command' => $command
        ]);

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \RuntimeException(
                "FFmpeg conversion failed for {$quality}: " . implode("\n", $output)
            );
        }
    }

    private function createMasterPlaylist(string $outputDir): void
    {
        $playlists = glob($outputDir . '/*.m3u8');

        if (empty($playlists)) {
            throw new \RuntimeException('No HLS playlists generated');
        }

        $qualities = [
            '360p' => ['bandwidth' => 800000, 'resolution' => '640x360'],
            '480p' => ['bandwidth' => 1400000, 'resolution' => '854x480'],
            '720p' => ['bandwidth' => 2800000, 'resolution' => '1280x720'],
            '1080p' => ['bandwidth' => 5000000, 'resolution' => '1920x1080'],
        ];

        $content = "#EXTM3U\n#EXT-X-VERSION:3\n";

        foreach ($playlists as $playlist) {
            $filename = basename($playlist);
            $quality = str_replace('.m3u8', '', $filename);

            if ($quality === 'master') {
                continue; // Skip the master playlist itself
            }

            if (isset($qualities[$quality])) {
                $content .= sprintf(
                    "#EXT-X-STREAM-INF:BANDWIDTH=%d,RESOLUTION=%s\n%s\n",
                    $qualities[$quality]['bandwidth'],
                    $qualities[$quality]['resolution'],
                    $filename
                );
            }
        }

        file_put_contents($outputDir . '/master.m3u8', $content);
    }
}
