<?php
putenv('SF_ENV=testing');
require_once __DIR__ . '/../includes/ai_staging_certification.php';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$catalog = sf_ai_cert_catalog();
$assert(count($catalog) >= 25, 'Certification catalog should include the full staging gate.');
$assert(isset($catalog['providers.chatgpt.connection'],$catalog['concurrency.mission_claim'],$catalog['rollback.storyboard'],$catalog['cost.reconciliation']), 'Critical certification controls are missing.');
$assert(sf_ai_cert_retryable_status(0), 'Network failures must be retryable.');
$assert(sf_ai_cert_retryable_status(429), '429 must be retryable.');
$assert(sf_ai_cert_retryable_status(503), '5xx must be retryable.');
$assert(!sf_ai_cert_retryable_status(400), 'Permanent 400 errors must not be retryable.');
$assert(!sf_ai_cert_retryable_status(401), 'Credential errors must not be retried automatically.');
$assert(empty(sf_sbgen_parse_json_text('not-json',1)['ok']), 'Malformed model output must be rejected.');
$assert(empty(sf_sbgen_parse_json_text(str_repeat('x',262145),1)['ok']), 'Oversized model output must be rejected.');
$assert((bool)preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/',sf_ai_cert_uuid()), 'Certification UUID must be RFC 4122 version 4 shaped.');

if ($failures) {
    fwrite(STDERR,"AI staging certification smoke failures:\n- " . implode("\n- ",$failures) . "\n");
    exit(1);
}

echo "AI staging certification smoke tests passed.\n";
