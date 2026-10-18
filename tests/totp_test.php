<?php
require_once __DIR__ . '/../includes/totp.php';

return function (TestRunner $t) {

    $t->run('base32_encode/decode round-trips arbitrary bytes', function (TestRunner $t) {
        $original = random_bytes(20);
        $encoded = base32_encode($original);
        $decoded = base32_decode($encoded);
        $t->assertEquals($original, $decoded);
    });

    $t->run('base32_encode matches RFC 4648 test vectors (data portion, no "=" padding emitted)', function (TestRunner $t) {
        $t->assertEquals('MY', base32_encode('f'));
        $t->assertEquals('MZXQ', base32_encode('fo'));
        $t->assertEquals('MZXW6', base32_encode('foo'));
        $t->assertEquals('MZXW6YQ', base32_encode('foob'));
        $t->assertEquals('MZXW6YTB', base32_encode('fooba'));
        $t->assertEquals('MZXW6YTBOI', base32_encode('foobar'));
    });

    $t->run('totp_code matches RFC 4226 HOTP test vectors at counter-derived timestamps', function (TestRunner $t) {
        // RFC 4226 Appendix D test vectors use key = ASCII "12345678901234567890"
        // and raw counters 0-9. totp_code() derives its counter as
        // floor(timestamp / period), so period=1 and timestamp=counter
        // reproduces the same HOTP values TOTP is built on.
        $key = '12345678901234567890';
        $secret = base32_encode($key);
        $expected = ['755224', '287082', '359152', '969429', '338314', '254676', '287922', '162583', '399871', '520489'];

        foreach ($expected as $counter => $code) {
            $t->assertEquals($code, totp_code($secret, $counter, 1), "counter {$counter}");
        }
    });

    $t->run('totp_verify accepts the current code', function (TestRunner $t) {
        $secret = totp_generate_secret();
        $code = totp_code($secret);
        $t->assertTrue(totp_verify($secret, $code));
    });

    $t->run('totp_verify accepts a code from one window back (clock drift tolerance)', function (TestRunner $t) {
        $secret = totp_generate_secret();
        $code = totp_code($secret, time() - 30);
        $t->assertTrue(totp_verify($secret, $code, 1));
    });

    $t->run('totp_verify rejects a code outside the drift window', function (TestRunner $t) {
        $secret = totp_generate_secret();
        $code = totp_code($secret, time() - 300); // 5 minutes ago
        $t->assertFalse(totp_verify($secret, $code, 1));
    });

    $t->run('totp_verify rejects a wrong code', function (TestRunner $t) {
        $secret = totp_generate_secret();
        $t->assertFalse(totp_verify($secret, '000000'));
    });

    $t->run('totp_verify rejects malformed input without throwing', function (TestRunner $t) {
        $secret = totp_generate_secret();
        $t->assertFalse(totp_verify($secret, 'abcdef'));
        $t->assertFalse(totp_verify($secret, '123'));
        $t->assertFalse(totp_verify($secret, ''));
    });

    $t->run('totp_generate_secret produces a valid base32 string each time', function (TestRunner $t) {
        for ($i = 0; $i < 5; $i++) {
            $secret = totp_generate_secret();
            $t->assertTrue((bool) preg_match('/^[A-Z2-7]+$/', $secret), "secret '{$secret}' is not valid base32");
        }
    });

};
