<?php

namespace Tests\Unit;

use App\HTTPResponse;
use App\Ratings\XContentTypeOptionsRating;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class XContentTypeOptionsRatingTest extends TestCase
{
    /** @test */
    public function xContentTypeOptionsRating_rates_a_missing_header()
    {
        $client = $this->getMockedGuzzleClient([
            new Response(200),
        ]);
        $response = new HTTPResponse($this->request, $client);
        $rating = new XContentTypeOptionsRating($response);

        $this->assertEquals(0, $rating->score);
        $expected = [
            'translationStringId' => 'HEADER_NOT_SET',
            'placeholders' => null,
        ];
        $this->assertEquals($expected, $rating->errorMessage);
    }

    /** @test */
    public function xContentTypeOptionsRating_rates_a_correct_header()
    {
        $client = $this->getMockedGuzzleClient([
            new Response(200, ['X-Content-Type-Options' => 'nosniff']),
        ]);
        $response = new HTTPResponse($this->request, $client);
        $rating = new XContentTypeOptionsRating($response);

        $this->assertEquals(100, $rating->score);
        $this->assertTrue(collect($rating->testDetails)->flatten()->contains('XCTO_CORRECT'));
    }

    /** @test */
    public function xContentTypeOptionsRating_rates_a_wrong_header()
    {
        $client = $this->getMockedGuzzleClient([
            new Response(200, ['X-Content-Type-Options' => 'wrong entry']),
        ]);
        $response = new HTTPResponse($this->request, $client);
        $rating = new XContentTypeOptionsRating($response);

        $this->assertEquals(0, $rating->score);
        $this->assertTrue(collect($rating->testDetails)->flatten()->contains('XCTO_NOT_CORRECT'));
    }

    /** @test */
    public function xContentTypeOptionsRating_detects_wrong_encoding()
    {
        $client = $this->getMockedGuzzleClient([
            // Producing an encoding error
            new Response(200, ['X-Content-Type-Options' => zlib_encode('SGVsbG8gV29ybGQ=', ZLIB_ENCODING_RAW)]),
        ]);
        $response = new HTTPResponse($this->request, $client);
        $rating = new XContentTypeOptionsRating($response);

        $this->assertEquals(0, $rating->score);
        $this->assertTrue(collect($rating->errorMessage)->contains('HEADER_ENCODING_ERROR'));
        $this->assertTrue($rating->hasError);
    }
}
