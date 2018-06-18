<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\VanillaConnect;

use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;
use Prophecy\Exception\Exception;
use Vanilla\VanillaConnect\VanillaConnect;
use Vanilla\VanillaConnect\VanillaConnectProvider;

class VanillaConnectProviderTest extends TestCase {

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
     * Create a provider.
     *
     * @param array $whiteList
     * @return VanillaConnectProvider
     */
    private function createProvider($whiteList = ['https://vanilla.dev/*']) {
        return new VanillaConnectProvider(
            self::$vanillaConnect->getClientID(),
            self::$vanillaConnect->getSecret(),
            $whiteList
        );
    }

    /**
     * Test an error response.
     */
    public function testErrorResponse() {
        $erroneousRequestJWT = JWT::encode(
            [
                'iat' => time(),
                'exp' => time() + VanillaConnect::TIMEOUT,
                'nonce' => uniqid(),
                'redirect' => 'https://vanilla.dev/',
                // Missing version.
            ],
            self::$vanillaConnect->getSecret(),
            VanillaConnect::HASHING_ALGORITHM,
            null,
            ['azp' => self::$vanillaConnect->getClientID()]
        );

        $provider = $this->createProvider();

        // This response will contain errors from the authentication request.
        $location = $provider->createResponseURL($erroneousRequestJWT, ['id' => uniqid()]);
        $responseJWT = explode('jwt=', $location)[1];

        $this->assertFalse(self::$vanillaConnect->validateResponse($responseJWT));

        $errors = self::$vanillaConnect->getErrors();
        $this->assertTrue(!empty($errors));
        $this->assertArrayHasKey('request_missing_claim_item', $errors);
    }

    /**
     * Test a provider containing an invalid whitelisted URL.
     *
     * @dataProvider invalidWhiteListURLProvider
     * @expectedException Exception
     */
    public function testInvalidWhitelistURL($whitelistURL) {
        $this->createProvider([$whitelistURL]);
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public function invalidWhiteListURLProvider() {
        $urls = [
            // Malformed
            '*',
            'example.com',
            'example.com*',
            'example.com/test',
            'example.com/*',
            'https://*',
            'https://example.com',
            'https://example.com*',
            // Contains either fragment or query string.
            'https://example.com/?',
            'https://example.com/#',
            'https://example.com/?query=true',
            'https://example.com/path?query=true',
            'https://example.com/*?query=true',
            'https://example.com/?query=true&test',
            'https://example.com/#fragment',
            'https://example.com/?query=true#something',
            // Restricted
            'http://www.example.*/',
            'http://www.example.*:80/',
            'http://www.example*.com/',
            'http://www.example*.com:80/',
            'http://user@www.example*.com:80/',
            'http://user:pasword@www.example*.com:80/',
            'http://*.com/',
        ];

        $data = [];
        foreach ($urls as $url) {
            $data[] = [$url];
        }

        return $data;
    }

    /**
     * Test a provider containing a valid whitelisted URL.
     *
     * @dataProvider validWhiteListURLProvider
     */
    public function testValidWhitelistURL($whitelistURL) {
        $this->createProvider([$whitelistURL]);
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public function validWhiteListURLProvider() {
        $schemes = ['http://', 'https://', '//'];
        $userPasswords = [
            '',
            'user@',
            'user:password@',
        ];
        $hosts = [
            '*',
            '127.0.0.1',
            '127.0.0.1',
            '*.example.com',
            'www.example.com',
            'www.example.qc.ca',
        ];
        $ports = ['', ':80'];
        $paths = [
            '/',
            '/*',
            '/blabla*',
            '/blabla/*/test',
        ];

        // Combine, in order, all values of each arrays to the result of the combination of all previous arrays.
        $combine = function(...$arrays) {
            $tmp = array_shift($arrays);
            while ($array = array_shift($arrays)) {
                $newResults = [];
                foreach ($tmp as $existingValue) {
                    foreach ($array as $value) {
                        $newResults[] = $existingValue.$value;
                    }
                }
                $tmp = $newResults;
            }

            $results = [];
            foreach ($tmp as $result) {
                $results[] = [$result];
            }

            return $results;
        };

        $data = $combine($schemes, $userPasswords, $hosts, $ports, $paths);

        return $data;
    }

    /**
     * @param $whitelistURL
     * @param $redirectURL
     * @param $isValid
     *
     * @dataProvider redirectURLsProvider
     */
    public function testRedirectURLs($whitelistURL, $redirectURL, $isValid) {
        $provider = $this->createProvider([$whitelistURL]);

        $requestJWT = self::$vanillaConnect->createRequestAuthJWT(uniqid(), ['redirect' => $redirectURL]);
        $location = $provider->createResponseURL($requestJWT, ['id' => 1]);
        $responseJWT = explode('jwt=', $location)[1];

        $responseError = VanillaConnect::extractItemFromClaim($responseJWT, 'errors');

        $test = $isValid ? !isset($responseError) : isset($responseError);

        $this->assertTrue($test);
    }

    public function redirectURLsProvider() {
        return [
            // Pass
            ['//vanilla.dev/', '//vanilla.dev/', true],
            ['//vanilla.dev/lol', '//VaNiLlA.dEv/LoL', true],
            ['//vanilla.dev/', '//vanilla.dev/?query', true],
            ['//vanilla.dev/', '//vanilla.dev/?query#fragment', true],
            ['//vanilla.dev/', '//user:pwd@vanilla.dev/?query#fragment', true],
            ['//vanilla.dev/', 'http://vanilla.dev/', true],
            ['//vanilla.dev/', 'https://vanilla.dev/', true],
            ['//vanilla.dev/', 'https://vanilla.dev/?query=true', true],
            ['//*.vanilla.dev/', 'https://subdomain.vanilla.dev/#fragment', true],
            ['//*.vanilla.dev/', 'https://user:pwd@subdomain.vanilla.dev/#fragment', true],
            ['//*pwd@*.vanilla.dev/', 'https://user:pwd@subdomain.vanilla.dev/#fragment', true],
            ['//www.*.vanilla.dev/', 'https://user:pwd@www.subdomain.vanilla.dev/#fragment', true],
            ['//vanilla.dev/', 'https://vanilla.dev/#fragment', true],
            ['//vanilla.dev/*', '//vanilla.dev/path%20to%20some%20folder/', true],
            // Fail
            ['https://vanilla.dev/', '//vanilla.dev/', false],
            ['http://vanilla.dev/', '//vanilla.dev/', false],
            ['//vanilla.dev/', '//vanilla.dev', false],
            ['//vanilla.dev/*', '//vanilla.dev/path to some folder/', false],
            ['//*.vanilla.dev/*', '//lol.lol.com/haha/', false],
        ];
    }
}
