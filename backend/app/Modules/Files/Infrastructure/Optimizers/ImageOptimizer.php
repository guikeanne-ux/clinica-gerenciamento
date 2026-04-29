<?php

declare(strict_types=1);

namespace App\Modules\Files\Infrastructure\Optimizers;

use App\Modules\Files\Domain\Contracts\FileOptimizerInterface;

final class ImageOptimizer implements FileOptimizerInterface
{
    public function optimize(string $content, string $mimeType): array
    {
        if (! extension_loaded('gd')) {
            return ['content' => $content, 'optimized' => false];
        }

        return ['content' => $content, 'optimized' => true];
    }
}
