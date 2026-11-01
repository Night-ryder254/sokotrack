<?php

namespace App\Support;

/**
 * Chooses the right product from Open Food Facts / Open Beauty Facts / Open Products Facts
 * search results. Each result is an array with product_name, generic_name, brands, quantity
 * and image_front_url.
 *
 * bestExact()   the same brand and kind of product, so "Coca-Cola 500ml" never gets
 *               "Coca-Cola Zero" or a Pepsi, and the nearest pack size wins.
 * bestSimilar() the same kind of product from any brand, for names with no brand match.
 *
 * No Laravel dependencies, so it can be unit tested on its own.
 */
final class OpenFactsMatcher
{
    /** Flavour and recipe words that make a different product from the plain one. */
    private const VARIANT_WORDS = ['zero', 'diet', 'light', 'lite', 'cherry', 'vanilla', 'max', 'mini', 'sugarfree', 'decaf'];

    public static function bestExact(array $records, string $productName): ?array
    {
        $brand = ProductImageTerms::brandOf($productName);

        if ($brand === null) {
            return null;
        }

        $brandTokens = self::tokens($brand);
        $typeTokens = self::tokens(implode(' ', ProductImageTerms::typeTokens($productName)));
        $ourWords = self::tokens($productName);
        $ourQuantity = self::parseQuantity($productName);

        $best = null;
        $bestScore = -INF;

        foreach ($records as $index => $record) {
            if (empty($record['image_front_url'])) {
                continue;
            }

            // Our whole brand must appear inside ONE of their brands (they are comma separated),
            // so "Nice, Lovely Co" is two other brands and not "Nice & Lovely".
            if (! self::brandMatches($brandTokens, (string) ($record['brands'] ?? ''))) {
                continue;
            }

            $text = self::tokens(($record['product_name'] ?? '') . ' ' . ($record['generic_name'] ?? ''));
            $score = 4.0;
            $hits = 0;

            foreach ($typeTokens as $token) {
                if (in_array($token, $text, true)) {
                    $score += 2;
                    $hits++;
                }
            }

            // Right brand, but not the kind of product we want (a Fanta Pineapple for a Fanta Orange).
            if ($typeTokens && $hits === 0) {
                continue;
            }

            $score -= 3 * count(array_intersect(self::VARIANT_WORDS, array_diff($text, $ourWords)));
            $score += self::quantityScore($ourQuantity, $record['quantity'] ?? null, 3.0, -0.5);
            $score -= $index * 0.01;

            if ($score >= 3.0 && $score > $bestScore) {
                $best = $record;
                $bestScore = $score;
            }
        }

        return $best;
    }

    public static function bestSimilar(array $records, string $productName): ?array
    {
        $typeTokens = self::tokens(implode(' ', ProductImageTerms::typeTokens($productName)));

        if (! $typeTokens) {
            return null;
        }

        $head = $typeTokens[count($typeTokens) - 1];
        $allowedMissing = count($typeTokens) >= 3 ? 1 : 0;
        $ourWords = self::tokens($productName);
        $ourQuantity = self::parseQuantity($productName);

        $best = null;
        $bestScore = -INF;

        foreach ($records as $index => $record) {
            if (empty($record['image_front_url'])) {
                continue;
            }

            $text = self::tokens(($record['product_name'] ?? '') . ' ' . ($record['generic_name'] ?? ''));

            if (! in_array($head, $text, true)) {
                continue;
            }

            $missing = count(array_diff($typeTokens, $text));

            if ($missing > $allowedMissing) {
                continue;
            }

            $score = 2 * (count($typeTokens) - $missing);
            $score -= 3 * count(array_intersect(self::VARIANT_WORDS, array_diff($text, $ourWords)));
            $score += self::quantityScore($ourQuantity, $record['quantity'] ?? null, 1.0, 0.0);
            $score -= $index * 0.01;

            if ($score > $bestScore) {
                $best = $record;
                $bestScore = $score;
            }
        }

        return $best;
    }

    /**
     * "500ml", "1L", "2 kg", "6 x 330 ml" into a size in ml or g. Null when there is no size.
     *
     * @return array{unit: string, value: float}|null
     */
    public static function parseQuantity(?string $text): ?array
    {
        if (! $text || ! preg_match('/(\d+(?:[.,]\d+)?)\s?(ml|cl|ltr|litres?|liters?|l|kg|gm|g)\b/i', $text, $m)) {
            return null;
        }

        $value = (float) str_replace(',', '.', $m[1]);
        $unit = strtolower($m[2]);

        if ($unit === 'ml') {
            return ['unit' => 'ml', 'value' => $value];
        }

        if ($unit === 'cl') {
            return ['unit' => 'ml', 'value' => $value * 10];
        }

        if ($unit === 'kg') {
            return ['unit' => 'g', 'value' => $value * 1000];
        }

        if ($unit === 'g' || $unit === 'gm') {
            return ['unit' => 'g', 'value' => $value];
        }

        return ['unit' => 'ml', 'value' => $value * 1000];
    }

    private static function brandMatches(array $brandTokens, string $brands): bool
    {
        foreach (explode(',', $brands) as $entry) {
            if (! array_diff($brandTokens, self::tokens($entry))) {
                return true;
            }
        }

        return false;
    }

    private static function quantityScore(?array $ours, ?string $theirText, float $same, float $different): float
    {
        $theirs = self::parseQuantity($theirText);

        if (! $ours || ! $theirs) {
            return 0.0;
        }

        return ($ours['unit'] === $theirs['unit'] && abs($ours['value'] - $theirs['value']) < 0.5) ? $same : $different;
    }

    /**
     * Lower-case words with a trailing plural "s" removed, so "Farmers" matches "Farmer".
     *
     * @return list<string>
     */
    private static function tokens(string $text): array
    {
        $words = preg_split('/[^a-z0-9]+/', strtolower($text), -1, PREG_SPLIT_NO_EMPTY);

        return array_map(
            fn ($word) => (strlen($word) > 3 && str_ends_with($word, 's')) ? substr($word, 0, -1) : $word,
            $words
        );
    }
}
