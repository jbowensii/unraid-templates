<?php
/**
 * mediawiki-plus build patch: OIDC cross-domain SSO state fix.
 *
 * MediaWiki's PHP session handling drops the jumbojett OpenID Connect
 * state/nonce on the RETURN leg of the cross-domain SSO redirect
 * (auth.<domain> back to the wiki domain), which surfaces to users as an
 * "Unable to determine state" consent loop and login failure.
 *
 * Fix: mirror the jumbojett session store into a short-lived
 * Secure; HttpOnly; SameSite=None cookie so the state/nonce survive the
 * cross-site round trip, and read it back when the PHP session is empty.
 *
 * This runs at image-build time so the fix ships in the image and survives
 * container recreation. It is idempotent (guarded by the COOKIEPATCH marker) and
 * FAILS THE BUILD if the upstream jumbojett anchors have moved, so an
 * unpatched image is never published silently.
 */
$f = '/var/www/html/extensions/OpenIDConnect/vendor/jumbojett/openid-connect-php/src/OpenIDConnectClient.php';

$s = @file_get_contents($f);
if ($s === false) {
    fwrite(STDERR, "COOKIEPATCH: cannot read $f\n");
    exit(1);
}
if (strpos($s, '/*COOKIEPATCH*/') !== false) {
    echo "COOKIEPATCH: already applied\n";
    exit(0);
}

// from => to. Each 'from' is pristine jumbojett code that must appear exactly
// once in the file; we assert the match count to catch upstream drift.
$replacements = [
    // getSessionKey(): prefer the cookie mirror when the PHP session is empty.
    'if (array_key_exists($key, $_SESSION)) {'
        => "/*COOKIEPATCH*/ if (isset(\$_COOKIE[\$key])) { return \$_COOKIE[\$key]; }\n        if (array_key_exists(\$key, \$_SESSION)) {",

    // setSessionKey(): also write the value to the cookie mirror.
    '$_SESSION[$key] = $value;'
        => "/*COOKIEPATCH*/ @setcookie(\$key, (string)\$value, ['expires' => time() + 900, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'None']); \$_COOKIE[\$key] = (string)\$value; \$_SESSION[\$key] = \$value;",

    // unsetSessionKey(): clear the cookie mirror too.
    'unset($_SESSION[$key]);'
        => "/*COOKIEPATCH*/ @setcookie(\$key, '', ['expires' => time() - 3600, 'path' => '/']); unset(\$_COOKIE[\$key]); unset(\$_SESSION[\$key]);",
];

foreach ($replacements as $from => $to) {
    $count = 0;
    $s = str_replace($from, $to, $s, $count);
    if ($count !== 1) {
        fwrite(STDERR, "COOKIEPATCH: expected exactly 1 match for [$from], found $count - upstream jumbojett changed, aborting build\n");
        exit(1);
    }
}

if (@file_put_contents($f, $s) === false) {
    fwrite(STDERR, "COOKIEPATCH: cannot write $f\n");
    exit(1);
}
echo "COOKIEPATCH: applied OK\n";