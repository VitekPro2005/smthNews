<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use InvalidArgumentException;
use Symfony\Component\DomCrawler\Crawler;
use Exception;

class NewsImageService
{
    public function fetchImage(string $url, int $width = 400, int $height = 300): ?string
    {
        Log::info('NewsImageService: Fetching image from URL: ' . $url);

        try {
            $response = Http::get($url);

            if (!$response->successful()) {
                Log::warning('NewsImageService: Failed to fetch article page.');
                return null;
            }

            $crawler = new Crawler($response->body());
            $ogImage = null;

            try {
                $ogImage = $crawler->filterXPath('//meta[@property="og:image"]')->attr('content');
            } catch (InvalidArgumentException) {
                Log::info('NewsImageService: og:image tag not found.');
            }

            if (empty($ogImage)) {
                Log::warning('NewsImageService: No og:image found.');
                return null;
            }

            $imageResponse = Http::get($ogImage);
            if (!$imageResponse->successful()) {
                Log::error('NewsImageService: Failed to download image from ' . $ogImage);
                return null;
            }

            $imageContents = $imageResponse->body();

            $manager = new ImageManager(new Driver());
            $image = $manager->read($imageContents);

            $image = $image->scale(width: $width, height: $height);

            $ext = pathinfo(parse_url($ogImage, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (empty($ext) || strlen($ext) > 5) {
                $mime = $imageResponse->header('Content-Type');
                $ext = match ($mime) {
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp',
                    default => 'jpg',
                };
            }

            $filename = 'news_images/' . uniqid() . '.' . $ext;
            Storage::disk('public')->put($filename, (string) $image->encode());

            Log::info("NewsImageService: Image saved as $filename");

            return $filename;
        } catch (Exception $e) {
            Log::error("NewsImageService: Exception occurred - " . $e->getMessage());
            return null;
        }
    }
}
