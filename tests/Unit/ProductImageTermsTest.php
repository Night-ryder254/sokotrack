<?php

namespace Tests\Unit;

use App\Support\ProductImageTerms;
use PHPUnit\Framework\TestCase;

class ProductImageTermsTest extends TestCase
{
    /**
     * Every product from the seeded catalog:
     * [category, name, first Wikipedia title or null, brand or null]
     * Null for the title means the Wikipedia photo wasn't a clean product picture for that
     * type, so the photo comes from the Open Facts databases instead.
     */
    private function catalog(): array
    {
        return [
            // Beverages
            ['Beverages', 'Coca-Cola 500ml', 'Cola', 'Coca-Cola'],
            ['Beverages', 'Del Monte Juice 1L', 'Juice', 'Del Monte'],
            ['Beverages', 'Keringet Water 500ml', 'Bottled water', 'Keringet'],
            ['Beverages', 'Tusker Lager 500ml', 'Lager', 'Tusker'],
            ['Beverages', 'Java House Ground Coffee 250g', 'Coffee', 'Java House'],
            ['Beverages', 'Alvaro Malt Drink 350ml', null, 'Alvaro'],
            ['Beverages', 'Dasani Water 1L', 'Bottled water', 'Dasani'],
            ['Beverages', 'Minute Maid Mango 1L', 'Juice', 'Minute Maid'],
            ['Beverages', 'Fanta Orange 500ml', 'Orange soft drink', 'Fanta'],
            ['Beverages', 'Sprite 500ml', 'Lemon-lime drink', 'Sprite'],
            // Snacks
            ['Snacks', 'Tropical Heat Crisps', 'Potato chip', 'Tropical Heat'],
            ['Snacks', 'Britania Digestive Biscuits', 'Digestive biscuit', 'Britania'],
            ['Snacks', 'Cadbury Dairy Milk 80g', 'Chocolate bar', 'Cadbury'],
            ['Snacks', 'Farmers Choice Sausages', null, 'Farmers Choice'],
            ['Snacks', 'Kenchic Chicken Nuggets 500g', 'Chicken nugget', 'Kenchic'],
            ['Snacks', 'Nice Biscuits 200g', 'Cookie', 'Nice'],
            ['Snacks', 'Big G Bubble Gum', 'Bubble gum', 'Big G'],
            ['Snacks', 'Softa Fruit Chews', 'Gummy candy', 'Softa'],
            ['Snacks', 'Weetabix Cereal 430g', 'Breakfast cereal', 'Weetabix'],
            ['Snacks', 'Uji Flour 2kg', null, null],
            // Cleaning Supplies
            ['Cleaning Supplies', 'Omo Washing Powder 1kg', 'Laundry detergent', 'Omo'],
            ['Cleaning Supplies', 'Jik Bleach 750ml', 'Bleach', 'Jik'],
            ['Cleaning Supplies', 'Sunlight Dishwashing Liquid 500ml', 'Dishwashing liquid', 'Sunlight'],
            ['Cleaning Supplies', 'Harpic Toilet Cleaner 500ml', 'Toilet cleaner', 'Harpic'],
            ['Cleaning Supplies', 'Ariel Detergent 1kg', 'Laundry detergent', 'Ariel'],
            ['Cleaning Supplies', 'Dettol Antiseptic 250ml', 'Antiseptic', 'Dettol'],
            ['Cleaning Supplies', 'Vim Scouring Powder 500g', null, 'Vim'],
            ['Cleaning Supplies', 'Toilet Paper 10-Pack', 'Toilet paper', null],
            ['Cleaning Supplies', 'Kiwi Shoe Polish', 'Shoe polish', 'Kiwi'],
            ['Cleaning Supplies', 'Mr Muscle Glass Cleaner', null, 'Mr Muscle'],
            // Stationery
            ['Stationery', 'Bic Biro Pens (Box of 12)', 'Ballpoint pen', 'Bic'],
            ['Stationery', 'Foolscap Exercise Book', 'Exercise book', null],
            ['Stationery', 'Camlin Pencils (Pack of 10)', 'Pencil', 'Camlin'],
            ['Stationery', 'Manila File Folders', 'File folder', null],
            ['Stationery', 'Stapler with Pins', 'Stapler', null],
            ['Stationery', 'A4 Printing Paper Ream', null, null],
            ['Stationery', 'Highlighter Set', 'Highlighter', null],
            ['Stationery', 'Correction Fluid', 'Correction fluid', null],
            ['Stationery', 'Office Envelopes (50-Pack)', 'Envelope', null],
            ['Stationery', 'Sticky Notes Pack', 'Post-it Note', null],
            // Cosmetics
            ['Cosmetics', 'Nivea Body Lotion 400ml', null, 'Nivea'],
            ['Cosmetics', 'Fair & White Soap', 'Soap', 'Fair & White'],
            ['Cosmetics', 'Vaseline Petroleum Jelly 250ml', null, 'Vaseline'],
            ['Cosmetics', 'Dark & Lovely Relaxer', null, 'Dark & Lovely'],
            ['Cosmetics', 'Colgate Toothpaste 100ml', 'Toothpaste', 'Colgate'],
            ['Cosmetics', 'Geisha Beauty Soap', 'Soap', 'Geisha'],
            ['Cosmetics', 'Sunsilk Shampoo 350ml', null, 'Sunsilk'],
            ['Cosmetics', 'Rexona Deodorant Roll-On', 'Deodorant', 'Rexona'],
            ['Cosmetics', 'Nice & Lovely Body Wash', null, 'Nice & Lovely'],
            ['Cosmetics', 'Palmers Cocoa Butter', 'Cocoa butter', 'Palmers'],
            // Dairy
            ['Dairy', 'Brookside Fresh Milk 500ml', 'Milk', 'Brookside'],
            ['Dairy', 'KCC Butter 250g', 'Butter', 'KCC'],
            ['Dairy', 'Tuzo Yoghurt 500ml', 'Yogurt', 'Tuzo'],
            ['Dairy', 'Fresha Long Life Milk 1L', 'Milk', 'Fresha'],
            ['Dairy', 'Daima Ghee 500g', 'Ghee', 'Daima'],
            ['Dairy', 'Bio Yoghurt 1L', 'Yogurt', 'Bio'],
            ['Dairy', 'Kenya Creamery Cheese 200g', 'Cheese', 'Kenya Creamery'],
            ['Dairy', 'Ilara Fresh Milk 1L', 'Milk', 'Ilara'],
            ['Dairy', 'Delamere Cream 250ml', 'Cream', 'Delamere'],
            ['Dairy', 'Kanga Milk Powder 400g', 'Powdered milk', 'Kanga'],
        ];
    }

    public function test_every_seeded_product_gets_the_intended_wikipedia_title(): void
    {
        foreach ($this->catalog() as [$category, $name, $firstTitle]) {
            $titles = ProductImageTerms::titlesFor($name, $category);

            $this->assertSame($firstTitle, $titles[0] ?? null, "'{$name}' should start with " . var_export($firstTitle, true));
        }
    }

    public function test_every_seeded_product_is_searched_under_the_right_brand(): void
    {
        foreach ($this->catalog() as [, $name, , $brand]) {
            $this->assertSame($brand, ProductImageTerms::brandOf($name), "Brand of '{$name}'");
        }
    }

    public function test_product_type_words_exclude_brand_sizes_and_descriptions(): void
    {
        $this->assertSame(['lotion'], ProductImageTerms::typeTokens('Nivea Body Lotion 400ml'));
        $this->assertSame(['water'], ProductImageTerms::typeTokens('Dasani Water 1L'));
        $this->assertSame(['orange'], ProductImageTerms::typeTokens('Fanta Orange 500ml'));
        $this->assertSame(['sausages'], ProductImageTerms::typeTokens('Farmers Choice Sausages'));
        $this->assertSame(['toilet', 'paper'], ProductImageTerms::typeTokens('Toilet Paper 10-Pack'));
        $this->assertSame([], ProductImageTerms::typeTokens('Coca-Cola 500ml'));
    }

    public function test_search_terms_combine_brand_and_type(): void
    {
        $this->assertSame('Nivea lotion', ProductImageTerms::searchTerms('Nivea Body Lotion 400ml')['exact']);
        $this->assertSame('Coca-Cola', ProductImageTerms::searchTerms('Coca-Cola 500ml')['exact']);
        $this->assertSame('Fair & White soap', ProductImageTerms::searchTerms('Fair & White Soap')['exact']);
        $this->assertSame('sausages', ProductImageTerms::searchTerms('Farmers Choice Sausages')['type']);
    }

    public function test_similar_words_do_not_match_by_accident(): void
    {
        // "Creamery" must not trigger the cream rule, and "Pencils" must not trigger the pen rule.
        $this->assertSame(['Cheese'], ProductImageTerms::titlesFor('Kenya Creamery Cheese 200g', 'Dairy'));
        $this->assertSame(['Pencil'], ProductImageTerms::titlesFor('Camlin Pencils (Pack of 10)', 'Stationery'));
    }

    public function test_the_same_word_can_mean_different_things_in_different_categories(): void
    {
        $this->assertSame(['Cream'], ProductImageTerms::titlesFor('Fresh Cream 250ml', 'Dairy'));
        $this->assertSame([], ProductImageTerms::titlesFor('Face Cream 100ml', 'Cosmetics'));
        $this->assertSame(['Butter'], ProductImageTerms::titlesFor('Table Butter 250g', 'Dairy'));
        $this->assertSame(['Cocoa butter', 'Shea butter'], ProductImageTerms::titlesFor('Body Butter 200g', 'Cosmetics'));
    }

    public function test_unknown_products_return_nothing_instead_of_guessing(): void
    {
        $this->assertSame([], ProductImageTerms::titlesFor('Mystery Gadget 3000', 'Stationery'));
    }

    public function test_catalog_has_sixty_products(): void
    {
        $this->assertSame(60, count($this->catalog()));
    }
}
