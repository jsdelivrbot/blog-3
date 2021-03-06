<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ImageToUseTest extends BrowserKitTestCase
{



    public function testSeeingIfExistOnAWS()
    {
        $image = new \App\ImageToUse();

        $results = $image->exists("foo.png");

        $this->assertContains("foo.png", $results);
    }
}
