<?php

namespace Tests\Unit\WebDav;

use PHPUnit\Framework\TestCase;
use App\WebDav;
use Mockery;
use Sabre\HTTP\Response as SabreResponse;

class SapiTest extends TestCase {
    public function testSendResponseDoesNothing(): void {
        $sapi = new WebDav\Sapi();

        ob_start();

        $sapi->sendResponse(Mockery::mock(SabreResponse::class));

        $output = ob_get_clean();
        $headersList = headers_list();

        $this->assertEmpty($output);
        $this->assertEmpty($headersList);
    }
}
