<?php

namespace Tests\Unit;

use App\HTTPResponse;
use App\Ratings\HSTSRating;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class HSTSRatingTest extends TestCase
{
    /** @test */
    public function hstsRating_rates_0_for_a_missing_header()
    {
        $client = $this->getMockedGuzzleClient([
            new Response(200),
        ]);
        $response = new HTTPResponse($this->request, $client);
        $rating = new HSTSRating($response);

        $this->assertEquals(0, $rating->score);

        $expected = [
            'translationStringId' => 'HEADER_NOT_SET',
            'placeholders' => null,
        ];
        $this->assertEquals($expected, $rating->errorMessage);
    }

    /** @test */
    public function hstsRating_rates_b_for_a_short_max_age()
    {
        $client = $this->getMockedGuzzleClient([
            new Response(200, [
                'Strict-Transport-Security' => 'max-age=30',
            ]),
        ]);
        $response = new HTTPResponse($this->request, $client);
        $rating = new HSTSRating($response);

        $this->assertEquals(60, $rating->score);
        $this->assertTrue(collect($rating->testDetails)->flatten()->contains('HSTS_LESS_6'));
    }

    /** @test */
    public function hstsRating_rates_a_for_a_good_max_age()
    {
        $client = $this->getMockedGuzzleClient([
            new Response(200, [
                'Strict-Transport-Security' => 'max-age=' . 6 * 31 * 24 * 60 * 60,
            ]),
        ]);
        $response = new HTTPResponse($this->request, $client);
        $rating = new HSTSRating($response);

        $this->assertEquals(100, $rating->score);
        $this->assertTrue(collect($rating->testDetails)->flatten()->contains('HSTS_MORE_6'));
    }

    /** @test */
    public function hstsRating_rates_x_plus_for_includeSubDomains()
    {
        $client = $this->getMockedGuzzleClient([
            new Response(200, [
                'Strict-Transport-Security' => 'max-age=30; includeSubDomains',
            ]),
        ]);
        $response = new HTTPResponse($this->request, $client);
        $rating = new HSTSRating($response);

        $this->assertTrue(collect($rating->testDetails)->flatten()->contains('INCLUDE_SUBDOMAINS'));
    }

    /** @test */
    public function hstsRating_rates_x_plus_for_preload()
    {
        $client = $this->getMockedGuzzleClient([
            new Response(200, [
                'Strict-Transport-Security' => 'max-age=30; preload',
            ]),
        ]);
        $response = new HTTPResponse($this->request, $client);
        $rating = new HSTSRating($response);

        $this->assertTrue(collect($rating->testDetails)->flatten()->contains('HSTS_PRELOAD'));
    }

    /** @test */
    public function HSTSRating_detects_wrong_encoding()
    {
        $client = $this->getMockedGuzzleClient([
            // Producing an encoding error
            new Response(200, ['Strict-Transport-Security' => zlib_encode('SGVsbG8gV29ybGQ=', ZLIB_ENCODING_RAW)]),
        ]);
        $response = new HTTPResponse($this->request, $client);
        $rating = new HSTSRating($response);

        $this->assertEquals(0, $rating->score);
        $this->assertTrue(collect($rating->errorMessage)->contains('HEADER_ENCODING_ERROR'));
        $this->assertTrue($rating->hasError);
    }
}
