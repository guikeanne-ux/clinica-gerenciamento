<?php

declare(strict_types=1);

namespace App\Modules\Files\Domain\Contracts;

interface FileOptimizerInterface
{
    /** @return array{content:string,optimized:bool} */
    public function optimize(string $content, string $mimeType): array;
}
