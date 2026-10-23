<?php

namespace Tests\Unit;

use App\Support\OpenFactsMatcher;
use PHPUnit\Framework\TestCase;

class OpenFactsMatcherTest extends TestCase
{
    private function record(string $code, string $name, string $brands, string $quantity = '', string $image = 'https://img/x.jpg'): array
    {
        return [
            'code' => $code,
            'product_name' => $name,
            'generic_name' => '',
            'brands' => $brands,
            'quantity' => $quantity,
            'image_front_url' => $image,
        ];
    }

    public function test_the_plain_product_in_the_right_size_beats_variants_and_other_brands(): void
    {
        $records = [
            $this->record('1', 'Coca-Cola Zero', 'Coca-Cola', '500 ml'),
            $this->record('2', 'Coca-Cola', 'Coca-Cola', '330 ml'),
            $this->record('3', 'Coca-Cola Original Taste', 'Coca-Cola', '500 ml'),
            $this->record('4', 'Pepsi', 'Pepsi', '500 ml'),
            $this->record('5', 'Coca-Cola Cherry', 'Coca-Cola', '500 ml'),
        ];

        $this->assertSame('3', OpenFactsMatcher::bestExact($records, 'Coca-Cola 500ml')['code']);
    }

    public function test_a_different_pack_size_still_matches_when_nothing_better_exists(): void
    {
        $records = [$this->record('2', 'Coca-Cola', 'Coca-Cola', '330 ml')];

        $this->assertSame('2', OpenFactsMatcher::bestExact($records, 'Coca-Cola 500ml')['code']);
    }

    public function test_other_brands_never_match(): void
    {
        $records = [$this->record('4', 'Cola', 'Pepsi', '500 ml'), $this->record('6', 'Cola', 'Tesco', '500 ml')];

        $this->assertNull(OpenFactsMatcher::bestExact($records, 'Coca-Cola 500ml'));
    }

    public function test_a_variant_alone_is_rejected_rather_than_shown_as_the_plain_product(): void
    {
        $records = [$this->record('1', 'Coca-Cola Zero', 'Coca-Cola', '330 ml')];

        $this->assertNull(OpenFactsMatcher::bestExact($records, 'Coca-Cola 500ml'));
    }

    public function test_the_right_flavour_is_chosen(): void
    {
        $records = [
            $this->record('10', 'Fanta Pineapple', 'Fanta', '500 ml'),
            $this->record('11', 'Fanta Orange', 'Fanta', '500 ml'),
        ];

        $this->assertSame('11', OpenFactsMatcher::bestExact($records, 'Fanta Orange 500ml')['code']);
    }

    public function test_the_right_kind_of_product_from_the_same_brand(): void
    {
        $records = [
            $this->record('20', 'Nivea Shower Gel', 'Nivea', '250 ml'),
            $this->record('21', 'Nivea Body Lotion Nourishing', 'Nivea', '400 ml'),
        ];

        $this->assertSame('21', OpenFactsMatcher::bestExact($records, 'Nivea Body Lotion 400ml')['code']);
    }

    public function test_a_brand_matches_inside_a_list_of_brands(): void
    {
        $records = [$this->record('30', 'Purified Water', 'PepsiCo, Dasani', '1 L')];

        $this->assertSame('30', OpenFactsMatcher::bestExact($records, 'Dasani Water 1L')['code']);
    }

    public function test_multi_word_brands_must_appear_together_in_one_brand(): void
    {
        $scattered = $this->record('31', 'Body Wash', 'Nice, Lovely Co');
        $correct = $this->record('32', 'Body Wash', 'Nice & Lovely');

        // The two words are present, but belong to two different brands: not a match.
        $this->assertNull(OpenFactsMatcher::bestExact([$scattered], 'Nice & Lovely Body Wash'));
        $this->assertSame('32', OpenFactsMatcher::bestExact([$scattered, $correct], 'Nice & Lovely Body Wash')['code']);
    }

    public function test_results_without_a_photo_are_skipped(): void
    {
        $records = [$this->record('40', 'Fanta Orange', 'Fanta', '500 ml', '')];

        $this->assertNull(OpenFactsMatcher::bestExact($records, 'Fanta Orange 500ml'));
    }

    public function test_names_without_a_brand_never_get_an_exact_match(): void
    {
        $records = [$this->record('50', 'Toilet Paper', 'Softex', '10 rolls')];

        $this->assertNull(OpenFactsMatcher::bestExact($records, 'Toilet Paper 10-Pack'));
    }

    public function test_similar_finds_the_same_kind_of_product_from_another_brand(): void
    {
        $records = [
            $this->record('60', 'Milk chocolate bar', 'Cadbury'),
            $this->record('61', 'Pork sausages', 'Wall\'s'),
        ];

        $this->assertSame('61', OpenFactsMatcher::bestSimilar($records, 'Farmers Choice Sausages')['code']);
    }

    public function test_similar_does_not_return_a_different_kind_of_product(): void
    {
        $records = [$this->record('60', 'Milk chocolate bar', 'Cadbury')];

        $this->assertNull(OpenFactsMatcher::bestSimilar($records, 'Farmers Choice Sausages'));
    }

    public function test_similar_avoids_diet_versions_when_a_plain_one_exists(): void
    {
        $records = [
            $this->record('70', 'Maize flour light', 'A'),
            $this->record('71', 'Maize flour', 'B'),
        ];

        $this->assertSame('71', OpenFactsMatcher::bestSimilar($records, 'Uji Flour 2kg')['code']);
    }

    public function test_sizes_are_read_in_any_unit(): void
    {
        $this->assertSame(['unit' => 'ml', 'value' => 500.0], OpenFactsMatcher::parseQuantity('Coca-Cola 500ml'));
        $this->assertSame(['unit' => 'ml', 'value' => 1000.0], OpenFactsMatcher::parseQuantity('Dasani Water 1L'));
        $this->assertSame(['unit' => 'ml', 'value' => 1500.0], OpenFactsMatcher::parseQuantity('1.5 l'));
        $this->assertSame(['unit' => 'ml', 'value' => 330.0], OpenFactsMatcher::parseQuantity('33 cl'));
        $this->assertSame(['unit' => 'g', 'value' => 2000.0], OpenFactsMatcher::parseQuantity('Uji Flour 2kg'));
        $this->assertSame(['unit' => 'g', 'value' => 250.0], OpenFactsMatcher::parseQuantity('KCC Butter 250g'));
        $this->assertSame(['unit' => 'ml', 'value' => 330.0], OpenFactsMatcher::parseQuantity('6 x 330 ml'));
        $this->assertNull(OpenFactsMatcher::parseQuantity('Toilet Paper 10-Pack'));
        $this->assertNull(OpenFactsMatcher::parseQuantity(null));
    }
}
