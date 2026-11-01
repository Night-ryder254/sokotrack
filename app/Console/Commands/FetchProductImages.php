<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\OpenFactsMatcher;
use App\Support\ProductImageTerms;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FetchProductImages extends Command
{
    protected $signature = 'products:fetch-images
                            {--preview : Build an HTML sheet of the photos that would be used, without saving anything}
                            {--exact-only : Skip "similar product" photos; only use the exact product or a clean photo of its type}
                            {--force : Replace every product image, including ones uploaded by hand}
                            {--only= : Only process products whose name contains this text}';

    protected $description = 'Give each product a free-licensed photo: the exact product from the Open Facts databases, else a clean photo of its type from Wikipedia';

    /** Wikimedia and Open Facts ask every client to identify itself. Put your own email here. */
    protected const USER_AGENT = 'SokoTrackPortfolio/1.0 (local development; +https://github.com/Night-ryder254/sokotrack)';

    /** Folders this command (or its earlier versions) saves photos in. */
    protected const OWN_FOLDERS = ['products/openfacts/', 'products/wiki/', 'products/verified/', 'products/stock'];

    /** Which Open Facts database holds each kind of product. */
    protected const OPEN_FACTS_HOSTS = [
        'cosmetics' => ['Open Beauty Facts' => 'world.openbeautyfacts.org'],
        'cleaning supplies' => ['Open Products Facts' => 'world.openproductsfacts.org', 'Open Beauty Facts' => 'world.openbeautyfacts.org'],
        'stationery' => ['Open Products Facts' => 'world.openproductsfacts.org'],
    ];

    protected const DEFAULT_HOSTS = ['Open Food Facts' => 'world.openfoodfacts.org'];

    protected float $lastOpenFactsCall = 0.0;

    public function handle(): int
    {
        $preview = (bool) $this->option('preview');
        $exactOnly = (bool) $this->option('exact-only');
        $disk = Storage::disk('public');

        $query = Product::with('category')->orderBy('category_id')->orderBy('id');

        if ($only = $this->option('only')) {
            $query->where('name', 'like', "%{$only}%");
        }

        $toProcess = $query->get()->filter(fn (Product $product) => $this->isReplaceable($product));

        if ($toProcess->isEmpty()) {
            $this->info('Nothing to do. Use --force to replace images that were uploaded by hand.');
            return self::SUCCESS;
        }

        // Photos on products we are not touching must not be handed to another product.
        $usedPaths = Product::whereNotIn('id', $toProcess->pluck('id')->all())
            ->pluck('image_path')
            ->filter()
            ->values()
            ->all();

        $this->info(($preview ? 'Previewing ' : 'Matching ') . "{$toProcess->count()} product(s). The first run takes about 10 minutes because Open Food Facts limits searches; later runs use the cache and take seconds.");

        $credits = $this->readCredits($disk);
        $rows = [];
        $counts = [];
        $unmatched = [];

        foreach ($toProcess as $product) {
            $match = $this->findPhoto($product, $usedPaths, $exactOnly);

            if (! $match) {
                $unmatched[] = $product->name;
                $rows[] = ['product' => $product, 'match' => null];
                $this->warn("  [no photo] {$product->name}");

                if (! $preview) {
                    $this->clearOwnPhoto($product);
                }

                continue;
            }

            $usedPaths[] = $match['path'];
            $rows[] = ['product' => $product, 'match' => $match];

            if (! $preview) {
                $body = $this->download($match['thumb']);

                if ($body === null) {
                    $unmatched[] = $product->name;
                    $this->warn("  [download failed] {$product->name}");
                    continue;
                }

                $disk->put($match['path'], $body);
                $product->update(['image_path' => $match['path']]);
                $credits[$match['path']] = $match['credit'];
            }

            $counts[$match['kind']] = ($counts[$match['kind']] ?? 0) + 1;
            $this->line("  [{$match['kind']}] {$product->name}  ->  {$match['label']}  ({$match['source']})");
        }

        $this->newLine();

        if ($preview) {
            $disk->put('products/preview.html', $this->previewHtml($rows));
            $this->info('Preview written. Open http://127.0.0.1:8000/storage/products/preview.html to check every photo at once.');
            $this->line('Nothing was saved or changed. Run the command without --preview to apply it.');
        } else {
            $this->saveCredits($disk, $credits);
            $this->removeUnusedPhotos($disk);
            $this->info('Done. Photo credits are in storage/app/public/products/CREDITS.txt');
        }

        foreach ($counts as $kind => $count) {
            $this->line("  {$count} x {$kind}");
        }

        if ($unmatched) {
            $this->warn(count($unmatched) . ' product(s) have no photo and keep the placeholder: ' . implode(', ', $unmatched));
            $this->line('Upload a photo in Admin > Products > Edit, or add its product type to app/Support/ProductImageTerms.php.');
        }

        return self::SUCCESS;
    }

    /**
     * Products with no image, and photos this command (or its earlier versions) picked, can be
     * redone. Images uploaded by hand are kept unless --force is used.
     */
    protected function isReplaceable(Product $product): bool
    {
        $path = (string) $product->image_path;

        if ($this->option('force') || $path === '') {
            return true;
        }

        return $this->isOwnPhoto($path);
    }

    protected function isOwnPhoto(string $path): bool
    {
        foreach (self::OWN_FOLDERS as $folder) {
            if (str_starts_with($path, $folder)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Better a clean placeholder than a photo of the wrong thing.
     */
    protected function clearOwnPhoto(Product $product): void
    {
        if ($this->isOwnPhoto((string) $product->image_path)) {
            $product->update(['image_path' => null]);
        }
    }

    /**
     * 1. The exact product (same brand and kind) from the Open Facts databases.
     * 2. A clean photo of the product type from Wikipedia.
     * 3. A similar product of the same type from the Open Facts databases.
     */
    protected function findPhoto(Product $product, array $usedPaths, bool $exactOnly): ?array
    {
        $name = $product->name;
        $category = $product->category->name ?? '';

        return $this->openFactsPhoto($name, $category, $usedPaths, true)
            ?? $this->wikipediaPhoto($name, $category, $usedPaths)
            ?? ($exactOnly ? null : $this->openFactsPhoto($name, $category, $usedPaths, false));
    }

    protected function openFactsPhoto(string $name, string $category, array $usedPaths, bool $exact): ?array
    {
        $terms = ProductImageTerms::searchTerms($name);
        $query = $exact ? $terms['exact'] : $terms['type'];

        if ($query === '' || ($exact && $terms['brand'] === null)) {
            return null;
        }

        $hosts = self::OPEN_FACTS_HOSTS[strtolower($category)] ?? self::DEFAULT_HOSTS;

        foreach ($hosts as $source => $host) {
            $records = array_values(array_filter(
                $this->openFactsSearch($host, $query),
                fn ($record) => ! empty($record['code']) && ! in_array($this->openFactsPath($record), $usedPaths, true)
            ));

            $record = $exact
                ? OpenFactsMatcher::bestExact($records, $name)
                : OpenFactsMatcher::bestSimilar($records, $name);

            if (! $record) {
                continue;
            }

            $path = $this->openFactsPath($record);
            $label = trim(($record['product_name'] ?? 'Product') . ' - ' . ($record['brands'] ?? ''), ' -');
            $page = "https://{$host}/product/{$record['code']}";

            return [
                'source' => $source,
                'kind' => $exact ? 'exact product' : 'similar product',
                'label' => $label,
                'thumb' => $record['image_front_url'],
                'path' => $path,
                'credit' => sprintf('%s - %s photo "%s" by Open Facts contributors, CC BY-SA (%s)', $path, $source, $label, $page),
            ];
        }

        return null;
    }

    protected function openFactsPath(array $record): string
    {
        return 'products/openfacts/' . (Str::slug((string) ($record['code'] ?? '')) ?: 'unknown') . '.jpg';
    }

    /**
     * Search results, cached for a week. Open Food Facts allows about 10 searches a minute,
     * so uncached searches are spaced 6.5 seconds apart.
     */
    protected function openFactsSearch(string $host, string $terms): array
    {
        $key = 'openfacts:v1:' . md5($host . '|' . $terms);
        $cached = Cache::get($key);

        if ($cached !== null) {
            return $cached;
        }

        $json = $this->openFactsRequest($host, $terms);

        if ($json === null) {
            return []; // a network problem: don't remember it as "no results"
        }

        $keep = array_flip(['code', 'product_name', 'generic_name', 'brands', 'quantity', 'image_front_url']);

        $records = array_map(
            fn ($product) => array_intersect_key((array) $product, $keep),
            (array) ($json['products'] ?? [])
        );

        Cache::put($key, $records, now()->addDays(7));

        return $records;
    }

    protected function openFactsRequest(string $host, string $terms): ?array
    {
        for ($try = 1; $try <= 2; $try++) {
            $wait = 6.5 - (microtime(true) - $this->lastOpenFactsCall);

            if ($wait > 0) {
                usleep((int) ($wait * 1000000));
            }

            $this->lastOpenFactsCall = microtime(true);

            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT, 'Accept' => 'application/json'])
                ->timeout(30)
                ->get("https://{$host}/cgi/search.pl", [
                    'search_terms' => $terms,
                    'search_simple' => 1,
                    'action' => 'process',
                    'json' => 1,
                    'page_size' => 24,
                    'fields' => 'code,product_name,generic_name,brands,quantity,image_front_url',
                ]);

            if (in_array($response->status(), [429, 503], true) && $try === 1) {
                $this->warn('  Open Facts is busy, waiting 30 seconds...');
                sleep(30);
                continue;
            }

            if ($response->failed()) {
                $this->warn("  Open Facts search failed for '{$terms}' (HTTP {$response->status()})");

                return null;
            }

            $json = $response->json();

            return is_array($json) ? $json : null;
        }

        return null;
    }

    protected function wikipediaPhoto(string $name, string $category, array $usedPaths): ?array
    {
        $fallback = null;

        foreach (ProductImageTerms::titlesFor($name, $category) as $title) {
            $image = $this->leadImage($title);

            if (! $image) {
                continue;
            }

            if (! in_array($image['path'], $usedPaths, true)) {
                return $image;
            }

            // Same photo as another product: remember it, but try the other titles first.
            $fallback ??= $image;
        }

        return $fallback;
    }

    /**
     * The lead photo of a Wikipedia article, cached for a week.
     */
    protected function leadImage(string $title): ?array
    {
        $key = 'wikilead:v2:' . md5($title);
        $cached = Cache::get($key);

        if ($cached !== null) {
            return $cached ?: null;
        }

        $image = $this->fetchLeadImage($title);

        if ($image === false) {
            return null; // a network problem: don't remember it as "no photo"
        }

        // An empty array records "nothing usable", so Wikipedia isn't asked again for a week.
        Cache::put($key, $image ?? [], now()->addDays(7));

        return $image;
    }

    /**
     * @return array|false|null the photo details, null if the article has no usable photo,
     *                          false if Wikipedia could not be reached
     */
    protected function fetchLeadImage(string $title)
    {
        // Step 1: the article's lead image. pilicense=free returns freely licensed images only.
        $wiki = $this->wikimediaApi('https://en.wikipedia.org/w/api.php', [
            'action' => 'query',
            'format' => 'json',
            'formatversion' => 2,
            'redirects' => 1,
            'titles' => $title,
            'prop' => 'pageimages',
            'piprop' => 'thumbnail|name',
            'pithumbsize' => 640,
            'pilicense' => 'free',
        ]);

        if ($wiki === null) {
            return false;
        }

        $page = $wiki['query']['pages'][0] ?? null;

        if (! $page || isset($page['missing']) || empty($page['thumbnail']['source']) || empty($page['pageimage'])) {
            return null;
        }

        $width = (int) ($page['thumbnail']['width'] ?? 0);
        $height = (int) ($page['thumbnail']['height'] ?? 0);

        // Too small to look good on a product card, or a very wide/tall banner or panorama.
        if ($width < 300 || $height < 250) {
            return null;
        }

        $ratio = $width / max($height, 1);

        if ($ratio < 0.5 || $ratio > 2.2) {
            return null;
        }

        // Step 2: licence and author from Wikimedia Commons, for the credits file.
        $fileTitle = 'File:' . $page['pageimage'];

        $commons = $this->wikimediaApi('https://commons.wikimedia.org/w/api.php', [
            'action' => 'query',
            'format' => 'json',
            'formatversion' => 2,
            'titles' => $fileTitle,
            'prop' => 'imageinfo',
            'iiprop' => 'url|mime|extmetadata',
            'iiextmetadatafilter' => 'Artist|LicenseShortName',
        ]);

        if ($commons === null) {
            return false;
        }

        $info = $commons['query']['pages'][0]['imageinfo'][0] ?? null;

        if (! $info) {
            return null; // not on Commons, so we couldn't credit it properly
        }

        // Photos only: skips SVG diagrams, GIFs and TIFFs.
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$info['mime'] ?? ''] ?? null;

        if (! $ext) {
            return null;
        }

        $meta = $info['extmetadata'] ?? [];
        $artist = trim(html_entity_decode(strip_tags((string) ($meta['Artist']['value'] ?? ''))));
        $license = $meta['LicenseShortName']['value'] ?? 'Free license';
        $slug = Str::slug(pathinfo($page['pageimage'], PATHINFO_FILENAME)) ?: 'photo';
        $path = 'products/wiki/' . $slug . '-' . substr(sha1($page['pageimage']), 0, 8) . '.' . $ext;
        $source = $info['descriptionurl'] ?? 'https://commons.wikimedia.org/wiki/' . rawurlencode($fileTitle);

        return [
            'source' => 'Wikipedia',
            'kind' => 'same product type',
            'label' => "Wikipedia article: {$title}",
            'thumb' => $page['thumbnail']['source'],
            'path' => $path,
            'credit' => sprintf(
                '%s - "%s" by %s, %s (%s)',
                $path,
                $page['pageimage'],
                Str::limit($artist !== '' ? $artist : 'Unknown author', 80),
                $license,
                $source
            ),
        ];
    }

    protected function wikimediaApi(string $url, array $params): ?array
    {
        for ($try = 1; $try <= 2; $try++) {
            usleep(250000); // be polite to Wikimedia's servers

            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT, 'Accept' => 'application/json'])
                ->timeout(20)
                ->get($url, $params);

            if ($response->status() === 429 && $try === 1) {
                $this->warn('  Wikimedia asked us to slow down, waiting 10 seconds...');
                sleep(10);
                continue;
            }

            if ($response->failed()) {
                $this->warn("  Wikimedia request failed (HTTP {$response->status()}): " . Str::limit($response->body(), 100));

                return null;
            }

            return $response->json();
        }

        return null;
    }

    protected function download(string $url): ?string
    {
        $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])->timeout(30)->get($url);

        return $response->successful() ? $response->body() : null;
    }

    /**
     * One page showing every product beside the photo it would get, for a quick visual check.
     */
    protected function previewHtml(array $rows): string
    {
        $cards = '';

        foreach ($rows as $row) {
            $name = e($row['product']->name);
            $category = e($row['product']->category->name ?? '');

            if ($row['match']) {
                $m = $row['match'];
                $thumb = e($m['thumb']);
                $kind = e($m['kind']);
                $class = str_replace(' ', '-', $m['kind']);
                $label = e($m['label']);
                $source = e($m['source']);
                $cards .= "<div class=\"card\"><img src=\"{$thumb}\" alt=\"\"><div class=\"body\"><strong>{$name}</strong><span>{$category}</span><em class=\"{$class}\">{$kind}</em><small>{$label}<br>{$source}</small></div></div>";
            } else {
                $cards .= "<div class=\"card none\"><div class=\"blank\">No photo</div><div class=\"body\"><strong>{$name}</strong><span>{$category}</span><small>Will keep the placeholder</small></div></div>";
            }
        }

        return '<!doctype html><html><head><meta charset="utf-8"><title>Product photo preview</title><style>'
            . 'body{font-family:system-ui,sans-serif;background:#F7F9F6;margin:0;padding:24px;color:#1F2421}'
            . 'h1{font-size:20px}.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px}'
            . '.card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden}'
            . '.card img,.blank{width:100%;height:160px;object-fit:contain;background:#fff;display:block}'
            . '.blank{background:#1B4332;color:#fff;display:flex;align-items:center;justify-content:center;object-fit:cover}'
            . '.none{border-color:#C2622D}.body{padding:10px;display:flex;flex-direction:column;gap:3px}'
            . '.body span{font-size:12px;color:#1B4332}.body small{font-size:11px;color:#6b7280}'
            . 'em{font-style:normal;font-size:11px;font-weight:600;padding:1px 8px;border-radius:999px;align-self:flex-start}'
            . 'em.exact-product{background:#dcfce7;color:#166534}em.same-product-type{background:#e0f2fe;color:#075985}'
            . 'em.similar-product{background:#fef3c7;color:#92400e}'
            . '</style></head><body><h1>Product photo preview</h1><div class="grid">' . $cards . '</div></body></html>';
    }

    protected function readCredits($disk): array
    {
        if (! $disk->exists('products/CREDITS.txt')) {
            return [];
        }

        $credits = [];

        foreach (explode("\n", $disk->get('products/CREDITS.txt')) as $line) {
            $line = trim($line);

            if ($line !== '' && str_contains($line, ' - ')) {
                $credits[Str::before($line, ' - ')] = $line;
            }
        }

        return $credits;
    }

    protected function saveCredits($disk, array $credits): void
    {
        $referenced = Product::pluck('image_path')->filter()->all();
        $credits = array_intersect_key($credits, array_flip($referenced));

        $disk->put('products/CREDITS.txt', $credits ? implode("\n", $credits) . "\n" : '');
    }

    /**
     * Delete photos saved by this command or its earlier versions once no product uses them.
     */
    protected function removeUnusedPhotos($disk): void
    {
        $referenced = Product::pluck('image_path')->filter()->all();

        foreach ($disk->allFiles('products') as $file) {
            $isOwn = $this->isOwnPhoto($file) || str_starts_with($file, 'products/stock-');

            if ($isOwn && ! in_array($file, $referenced, true)) {
                $disk->delete($file);
            }
        }
    }
}
