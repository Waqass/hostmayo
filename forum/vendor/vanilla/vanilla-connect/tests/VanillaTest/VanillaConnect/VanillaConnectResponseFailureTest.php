<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\VanillaConnect;

use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;
use Vanilla\VanillaConnect\VanillaConnect;

class VanillaConnectResponseFailureTest extends TestCase {

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
     * {@inheritdoc}
     */
    public function setUp() {
        parent::setUp();

        JWT::$timestamp = null;
    }

    /**
     * Test for an expired token.
     */
    public function testExpiredJWT() {
        $jwt = self::$vanillaConnect->createRequestAuthJWT(uniqid());

        // Do the validation as if we were in the future.
        JWT::$timestamp = time() + VanillaConnect::TIMEOUT;

        $this->assertFalse(self::$vanillaConnect->validateRequest($jwt));

        $this->assertArrayHasKey('request_jtw_decode_exception', self::$vanillaConnect->getErrors());

        $this->assertContains('Expired token', self::$vanillaConnect->getErrors());
    }

    /**
     * The for a non supported hash method.
     */
    public function testInvalidHashMethod() {
        $jwt = JWT::encode(['nonce' => uniqid()], 'TestSecret', 'HS512', null, ['azp' => 'TestClientID']);

        $this->assertFalse(self::$vanillaConnect->validateResponse($jwt));

        $this->assertArrayHasKey('response_jwt_decode_exception', self::$vanillaConnect->getErrors());

        $this->assertContains('Algorithm not allowed', self::$vanillaConnect->getErrors());
    }

    /**
     *  Test for an invalid signature.
     */
    public function testInvalidSignature() {
        $wrongSecret = new VanillaConnect(self::$vanillaConnect->getClientID(), self::$vanillaConnect->getSecret().'1');
        $jwt = $wrongSecret->createResponseAuthJWT(uniqid(), []);

        $this->assertFalse(self::$vanillaConnect->validateResponse($jwt));

        $this->assertArrayHasKey('response_jwt_decode_exception', self::$vanillaConnect->getErrors());

        $this->assertContains('Signature verification failed', self::$vanillaConnect->getErrors());
    }

    /**
     * Test for a jwt response with a missing 'id' from its claim.
     */
    public function testMissingClaimID() {
        $jwt = self::$vanillaConnect->createResponseAuthJWT(uniqid(), ['name' => 'joe']);

        $this->assertFalse(self::$vanillaConnect->validateResponse($jwt));

        $this->assertArrayHasKey('response_empty_claim_id', self::$vanillaConnect->getErrors());
    }

    /**
     * Test for a missing client id (azp) from the header.
     */
    public function testMissingClientID() {
        $jwt = JWT::encode([], 'TestSecret', VanillaConnect::HASHING_ALGORITHM);

        $this->assertFalse(self::$vanillaConnect->validateResponse($jwt));

        $this->assertArrayHasKey('response_missing_header_item', self::$vanillaConnect->getErrors());
    }

    /**
     * Test for a jwt response issued with the wrong client id.
     */
    public function testWrongClientID() {
        $wrongClient = new VanillaConnect(self::$vanillaConnect->getClientID().'1', self::$vanillaConnect->getSecret());
        $jwt = $wrongClient->createResponseAuthJWT(uniqid(), []);

        $this->assertFalse(self::$vanillaConnect->validateRequest($jwt));

        $this->assertArrayHasKey('request_client_id_mismatch', self::$vanillaConnect->getErrors());
    }
}
