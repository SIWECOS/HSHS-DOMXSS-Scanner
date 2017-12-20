<?php

namespace App;

use App\Ratings\CSPRating;
use App\Ratings\ContentTypeRating;
use App\Ratings\HPKPRating;
use App\Ratings\HSTSRating;
use App\Ratings\XContentTypeOptionsRating;
use App\Ratings\XFrameOptionsRating;
use App\Ratings\XXSSProtectionRating;


/**
 * Returns a HeaderReport / Rating for the given URL.
 */
class HeaderCheck
{
    public $url;
    public $siteRating = null;
    public $comment = null;

    public function __construct($url)
    {
        $this->url = $url;
    }

   
    public function report()
    {
        $cspRating = new CSPRating($this->url);
        $contentTypeRating = new ContentTypeRating($this->url);
        $hpkpRating = new HPKPRating($this->url);
        $hstsRating = new HSTSRating($this->url);
        $xContenTypeOptionsRating = new XContentTypeOptionsRating($this->url);
        $xFrameOptionsRating = new XFrameOptionsRating($this->url);
        $xXssProtectionRating = new XXSSProtectionRating($this->url);

        $score = 0;

        return [
            'name' => 'HEADER',
            'hasError' => false,
            'errorMessage' => null,
            'score' => $score,
            'tests' => [
                $cspRating,
                $contentTypeRating,
                $hpkpRating,
                $hstsRating,
                $xContenTypeOptionsRating,
                $xFrameOptionsRating,
                $xXssProtectionRating,
            ]
        ];
    }
}