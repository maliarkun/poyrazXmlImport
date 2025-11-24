<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Model\Importer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Gallery\Processor;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;
use Poyraz\XmlImport\Logger\Logger;

class ImageImporter
{
    private const DEFAULT_PATH = 'import/poyraz';

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly File $file,
        private readonly Processor $galleryProcessor,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param array<int, string> $images
     */
    public function attachImages(\Magento\Catalog\Api\Data\ProductInterface $product, array $images, string $path = self::DEFAULT_PATH): void
    {
        $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $targetDir = $mediaDirectory->getAbsolutePath($path);
        if (!$mediaDirectory->isExist($path)) {
            $mediaDirectory->create($path);
        }

        $added = 0;
        foreach ($images as $url) {
            $cleanUrl = trim($url);
            if ($cleanUrl === '') {
                continue;
            }
            $fileName = basename(parse_url($cleanUrl, PHP_URL_PATH) ?? uniqid('img_', true));
            $destination = rtrim($targetDir, '/') . '/' . $fileName;
            try {
                $content = @file_get_contents($cleanUrl);
                if ($content === false) {
                    $this->logger->warning(sprintf('Cannot download image %s', $cleanUrl));
                    continue;
                }
                $this->file->write($destination, $content);
                $relativePath = $path . '/' . $fileName;
                $roles = $added === 0 ? ['image', 'small_image', 'thumbnail'] : [];
                $this->galleryProcessor->addImage(
                    $product,
                    $relativePath,
                    array_merge(['media_gallery'], $roles),
                    false,
                    false
                );
                $added++;
            } catch (\Throwable $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }
}
