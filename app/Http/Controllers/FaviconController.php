<?php

namespace App\Http\Controllers;

class FaviconController extends Controller
{
    /** The brand accent (DB-driven), validated to a hex color. */
    private function accent(): string
    {
        $a = (string) config('brand.accent', '#06b6d4');

        return preg_match('/^#[0-9a-fA-F]{6}$/', $a) ? $a : '#06b6d4';
    }

    /** Scalable favicon: shield mark in the brand accent on dark chrome. */
    public function svg()
    {
        $accent = $this->accent();
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="32" height="32">'
            . '<rect width="32" height="32" rx="7" fill="#0b1220"/>'
            . '<g transform="translate(4 4)" fill="none" stroke="' . $accent . '" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">'
            . '<path d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>'
            . '</g></svg>';

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function faviconPng()
    {
        return $this->png(64);
    }

    public function appleIcon()
    {
        return $this->png(180);
    }

    /** PNG fallback rendered with the accent (dark rounded square + shield + check). */
    private function png(int $size)
    {
        [$r, $g, $b] = sscanf($this->accent(), '#%02x%02x%02x');

        $im = imagecreatetruecolor($size, $size);
        imagesavealpha($im, true);
        imagealphablending($im, false);
        imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
        imagealphablending($im, true);

        $bg = imagecolorallocate($im, 0x0b, 0x12, 0x20);
        $accent = imagecolorallocate($im, $r, $g, $b);

        $rad = (int) round($size * 0.22);
        imagefilledrectangle($im, $rad, 0, $size - $rad, $size, $bg);
        imagefilledrectangle($im, 0, $rad, $size, $size - $rad, $bg);
        foreach ([[$rad, $rad], [$size - $rad, $rad], [$rad, $size - $rad], [$size - $rad, $size - $rad]] as [$cx, $cy]) {
            imagefilledellipse($im, $cx, $cy, $rad * 2, $rad * 2, $bg);
        }

        $p = fn ($x, $y) => [(int) round($x * $size), (int) round($y * $size)];
        $pts = array_merge($p(0.25, 0.24), $p(0.50, 0.16), $p(0.75, 0.24), $p(0.75, 0.55), $p(0.50, 0.82), $p(0.25, 0.55));
        imagefilledpolygon($im, $pts, $accent);

        imagesetthickness($im, max(2, (int) round($size * 0.075)));
        [$ax, $ay] = $p(0.39, 0.47);
        [$bx, $by] = $p(0.47, 0.56);
        [$dx, $dy] = $p(0.63, 0.36);
        imageline($im, $ax, $ay, $bx, $by, $bg);
        imageline($im, $bx, $by, $dx, $dy, $bg);

        ob_start();
        imagepng($im);
        $data = ob_get_clean();
        imagedestroy($im);

        return response($data, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
