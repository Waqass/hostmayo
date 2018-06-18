<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\VanillaConnect;

use PHPUnit\Framework\TestCase;
use Vanilla\VanillaConnect\VanillaConnect;

class VanillaConnectRequestTest extends TestCase {

    /**
     * @var VanillaConnect
     */
    private static $vanillaConnect;

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass() {
        self::$vanillaConnect = new VanillaConnect('TestClientID', 'TestSecret');
    }

    /**
     * Test a valid request.
     */
    public function testRequest() {
        $nonce = uniqid();
        $jwt = self::$vanillaConnect->createRequestAuthJWT($nonce);

        $this->assertTrue(self::$vanillaConnect->validateRequest($jwt, $claim));

        $this->assertTrue(is_array($claim));
        $this->assertArrayHasKey('nonce', $claim);
        $this->assertEquals($nonce, $claim['nonce']);
    }
}
