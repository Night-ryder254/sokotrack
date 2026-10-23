<?php

namespace App\Support;

/**
 * Works out, from a product name, what the product is so a matching photo can be found:
 *
 *  - brandOf()      the brand ("Nivea" from "Nivea Body Lotion 400ml")
 *  - typeTokens()   what kind of product it is (["lotion"])
 *  - searchTerms()  the search text for the Open Food Facts family of databases
 *  - titlesFor()    Wikipedia articles whose lead photo shows that product type
 *
 * Wikipedia titles are only listed for product types where the lead photo is a clean picture
 * of the item (milk, cheese, a stapler...). For types where it was a diagram or a person
 * (lotion, shampoo, detergent...) the list is empty on purpose, so the photo comes from the
 * Open Facts databases instead, which hold real photos of packaged products.
 *
 * To teach it a new product type, add one line to RULES.
 *
 * No Laravel dependencies, so it can be unit tested on its own.
 */
final class ProductImageTerms
{
    /**
     * Words that mean different things in different shop categories. Checked first.
     * category (lowercase) => [ keyword => [article titles] ]
     */
    private const CATEGORY_RULES = [
        'dairy' => [
            'milk powder' => ['Powdered milk'],
            'cream' => ['Cream'],
            'milk' => ['Milk'],
        ],
        'cosmetics' => [
            'cocoa butter' => ['Cocoa butter'],
            'shea butter' => ['Shea butter'],
            'butter' => ['Cocoa butter', 'Shea butter'],
            'cream' => [],
            'powder' => [],
        ],
    ];

    /**
     * Product type rules. The first keyword found in the product name wins, so specific
     * phrases come before general words. A keyword also matches its plural ("pen" / "pens").
     * An empty list means "no clean Wikipedia photo for this type, use the Open Facts databases".
     * keyword => [article titles]
     */
    private const RULES = [
        // Chocolate before milk, so "Dairy Milk" is a chocolate bar and not a milk bottle
        'dairy milk' => ['Chocolate bar'],
        'cadbury' => ['Chocolate bar'],
        'chocolate' => ['Chocolate bar'],

        // Dairy and pantry
        'milk powder' => ['Powdered milk'],
        'ice cream' => ['Ice cream'],
        'yoghurt' => ['Yogurt', 'Greek yogurt'],
        'yogurt' => ['Yogurt', 'Greek yogurt'],
        'cheese' => ['Cheese'],
        'ghee' => ['Ghee', 'Clarified butter'],
        'butter' => ['Butter'],
        'milk' => ['Milk'],
        'cereal' => ['Breakfast cereal'],
        'flour' => [],
        'rice' => ['Rice'],
        'sugar' => ['Sugar'],
        'salt' => ['Salt'],
        'cooking oil' => ['Cooking oil'],

        // Snacks
        'sausage' => [],
        'nugget' => ['Chicken nugget'],
        'bubble gum' => ['Bubble gum', 'Chewing gum'],
        'chewing gum' => ['Chewing gum'],
        'chews' => ['Gummy candy', 'Candy'],
        'candy' => ['Candy'],
        'crisps' => ['Potato chip'],
        'chips' => ['Potato chip'],
        'digestive' => ['Digestive biscuit'],
        'biscuit' => ['Cookie', 'Shortbread'],
        'cookie' => ['Cookie'],
        'popcorn' => ['Popcorn'],
        'peanut' => ['Peanut'],

        // Beverages
        'malt' => [],
        'fanta' => ['Orange soft drink', 'Soft drink'],
        'sprite' => ['Lemon-lime drink', 'Soft drink'],
        'cola' => ['Cola', 'Soft drink'],
        'soda' => ['Soft drink'],
        'lager' => ['Lager', 'Beer'],
        'beer' => ['Beer'],
        'wine' => ['Wine'],
        'water' => ['Bottled water'],
        'mango' => ['Juice', 'Orange juice'],
        'juice' => ['Juice', 'Orange juice'],
        'coffee' => ['Coffee', 'Coffee bean'],
        'tea' => ['Tea'],

        // Cleaning
        'toilet paper' => ['Toilet paper'],
        'toilet cleaner' => ['Toilet cleaner'],
        'shoe polish' => ['Shoe polish'],
        'glass cleaner' => [],
        'dishwashing' => ['Dishwashing liquid'],
        'washing powder' => ['Laundry detergent'],
        'detergent' => ['Laundry detergent'],
        'scouring' => [],
        'bleach' => ['Bleach'],
        'antiseptic' => ['Antiseptic'],
        'disinfectant' => ['Disinfectant'],
        'broom' => ['Broom'],
        'mop' => ['Mop'],

        // Stationery
        'exercise book' => ['Exercise book', 'Notebook'],
        'notebook' => ['Notebook'],
        'printing paper' => [],
        'ream' => [],
        'ballpoint' => ['Ballpoint pen'],
        'biro' => ['Ballpoint pen', 'Pen'],
        'pen' => ['Ballpoint pen', 'Pen'],
        'pencil' => ['Pencil'],
        'folder' => ['File folder'],
        'stapler' => ['Stapler'],
        'highlighter' => ['Highlighter', 'Marker pen'],
        'marker' => ['Marker pen'],
        'correction fluid' => ['Correction fluid'],
        'envelope' => ['Envelope'],
        'sticky note' => ['Post-it Note', 'Sticky note'],
        'eraser' => ['Eraser'],
        'ruler' => ['Ruler'],
        'scissors' => ['Scissors'],
        'calculator' => ['Calculator'],

        // Cosmetics and personal care
        'petroleum jelly' => [],
        'body wash' => [],
        'lotion' => [],
        'shampoo' => [],
        'conditioner' => [],
        'toothpaste' => ['Toothpaste'],
        'toothbrush' => ['Toothbrush'],
        'deodorant' => ['Deodorant'],
        'perfume' => ['Perfume'],
        'relaxer' => [],
        'soap' => ['Soap'],
        'razor' => ['Razor'],
        'lipstick' => ['Lipstick'],
        'nail polish' => ['Nail polish'],
        'sunscreen' => ['Sunscreen'],
        'diaper' => ['Diaper'],
    ];

    /**
     * Words that describe a product rather than name its brand or type.
     */
    private const DESCRIPTORS = [
        'fresh', 'long', 'life', 'ground', 'body', 'beauty', 'fruit', 'chicken', 'foolscap',
        'manila', 'office', 'a4', 'uji', 'set', 'pack', 'box', 'liquid',
    ];

    /**
     * Product types that are also brand names when they start a product name ("Sprite 500ml").
     */
    private const BRAND_KEYWORDS = ['cola', 'fanta', 'sprite', 'cadbury'];

    private static ?array $typeWords = null;

    /**
     * Wikipedia article titles to try for this product, best first. Empty if none are suitable.
     *
     * @return list<string>
     */
    public static function titlesFor(string $productName, string $category = ''): array
    {
        $categoryRules = self::CATEGORY_RULES[strtolower(trim($category))] ?? [];

        foreach ([$categoryRules, self::RULES] as $rules) {
            foreach ($rules as $keyword => $titles) {
                if (preg_match('/\b' . preg_quote($keyword, '/') . 's?\b/i', $productName)) {
                    return $titles;
                }
            }
        }

        return [];
    }

    /**
     * The brand: the words at the start of the name, up to the first word that says what the
     * product is. "Fair & White Soap" gives "Fair & White". Null for names with no brand.
     */
    public static function brandOf(string $productName): ?string
    {
        [$brand] = self::split($productName);

        $brand = trim(implode(' ', $brand));

        return $brand === '' ? null : $brand;
    }

    /**
     * Lower-case words saying what the product is, without brand, sizes or descriptions.
     * "Nivea Body Lotion 400ml" gives ["lotion"].
     *
     * @return list<string>
     */
    public static function typeTokens(string $productName): array
    {
        [, $rest] = self::split($productName);

        $tokens = [];

        foreach ($rest as $word) {
            $word = strtolower($word);

            if ($word === '&' || ctype_digit($word) || in_array($word, self::DESCRIPTORS, true)) {
                continue;
            }

            $tokens[] = $word;
        }

        return $tokens;
    }

    /**
     * @return array{brand: ?string, type: string, exact: string}
     */
    public static function searchTerms(string $productName): array
    {
        $brand = self::brandOf($productName);
        $type = implode(' ', self::typeTokens($productName));

        return [
            'brand' => $brand,
            'type' => $type,
            'exact' => trim(($brand ?? '') . ' ' . $type),
        ];
    }

    /**
     * @return array{0: list<string>, 1: list<string>} brand words and the remaining words
     */
    private static function split(string $productName): array
    {
        $words = self::words($productName);
        $brand = [];

        foreach ($words as $index => $word) {
            $key = strtolower($word);

            if ($index === 0 && in_array($key, self::BRAND_KEYWORDS, true)) {
                $brand[] = $word;
                break;
            }

            if (self::isTypeWord($key) || count($brand) === 3) {
                break;
            }

            $brand[] = $word;
        }

        return [$brand, array_slice($words, count($brand))];
    }

    /**
     * The name without sizes and bracketed notes: "Bic Biro Pens (Box of 12)" gives Bic, Biro, Pens.
     *
     * @return list<string>
     */
    private static function words(string $productName): array
    {
        $name = preg_replace('/\(.*?\)/', ' ', $productName);
        $name = preg_replace('/\b\d+(?:\.\d+)?\s?-?\s?(?:ml|ltr|litres?|l|kg|gm|g|pcs?|packs?|sheets|pages)\b/i', ' ', $name);
        $name = preg_replace('/[^A-Za-z0-9&\-\s]/', ' ', $name);

        return array_values(array_filter(preg_split('/\s+/', trim($name))));
    }

    private static function isTypeWord(string $key): bool
    {
        $words = self::typeWords();

        return isset($words[$key])
            || (strlen($key) > 3 && str_ends_with($key, 's') && isset($words[substr($key, 0, -1)]));
    }

    /**
     * Every word that appears in a rule keyword, plus the descriptors.
     */
    private static function typeWords(): array
    {
        if (self::$typeWords === null) {
            $set = array_flip(self::DESCRIPTORS);

            $keywords = array_merge(
                array_keys(self::RULES),
                ...array_values(array_map('array_keys', self::CATEGORY_RULES))
            );

            foreach ($keywords as $keyword) {
                foreach (preg_split('/[^a-z0-9]+/', strtolower($keyword)) as $word) {
                    if ($word !== '') {
                        $set[$word] = true;
                    }
                }
            }

            self::$typeWords = $set;
        }

        return self::$typeWords;
    }
}
