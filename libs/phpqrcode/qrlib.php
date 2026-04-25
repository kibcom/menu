<?php
declare(strict_types=1);

class QRcode
{
    public static function png(string $text, string $outfile, string $level = 'M', int $size = 8, int $margin = 2): bool
    {
        $encoded = rawurlencode($text);
        $dimension = max(150, min(800, $size * 40));
        $url = "https://api.qrserver.com/v1/create-qr-code/?size={$dimension}x{$dimension}&margin={$margin}&ecc={$level}&data={$encoded}";

        $image = @file_get_contents($url);
        if ($image === false) {
            return false;
        }

        return file_put_contents($outfile, $image) !== false;
    }
}
