<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/videos')]
final class VideoController extends AbstractController
{
    public function __construct(
        #[Autowire(param: 'app.videos.hls_path')]
        private readonly string $hlsBasePath
    ) {}

    #[Route('/hls/{lectureId}/{filename}', name: 'video_hls', methods: ['GET'])]
    public function serveHLS(string $lectureId, string $filename): Response
    {
        // Validate filename to prevent directory traversal
        if (str_contains($filename, '..') || str_contains($filename, '/')) {
            throw $this->createNotFoundException('Invalid filename');
        }

        // Only allow .m3u8 and .ts files
        if (!preg_match('/\.(m3u8|ts)$/', $filename)) {
            throw $this->createNotFoundException('Invalid file type');
        }

        // Construct file path
        $filePath = $this->hlsBasePath . '/' . $lectureId . '/' . $filename;

        // Check if file exists
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Video file not found');
        }

        // Future: Add authentication check here
        // $this->denyAccessUnlessGranted('VIEW', $lecture);

        // Create binary file response
        $response = new BinaryFileResponse($filePath);

        // Set proper content type
        if (str_ends_with($filename, '.m3u8')) {
            $response->headers->set('Content-Type', 'application/vnd.apple.mpegurl');
        } else {
            $response->headers->set('Content-Type', 'video/mp2t');
        }

        // Set caching headers
        $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        // CORS headers for video streaming
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Range');
        $response->headers->set('Access-Control-Expose-Headers', 'Content-Length, Content-Range');

        // Set disposition inline (not download)
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $filename
        );

        return $response;
    }
}
