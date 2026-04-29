<?php

declare(strict_types=1);

namespace App\Modules\Files\Infrastructure\Optimizers;

use App\Modules\Files\Domain\Contracts\FileOptimizerInterface;

final class PdfOptimizer implements FileOptimizerInterface
{
    public function optimize(string $content, string $mimeType): array
    {
        return ['content' => $content, 'optimized' => false];
    }
}
