<?php
require_once __DIR__ . '/../includes/ai_governance.php';

$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$check(sf_agentic_safe_redirect('https://evil.example/x', '/admin/index.php') === '/admin/index.php', 'External redirect must be rejected.');
$check(sf_agentic_safe_redirect('//evil.example/x', '/admin/index.php') === '/admin/index.php', 'Protocol-relative redirect must be rejected.');
$check(sf_agentic_safe_redirect('/admin/storyboards.php?id=2', '/admin/index.php') === '/admin/storyboards.php?id=2', 'Local redirect must be preserved.');
$check(sf_agentic_model_name('gpt-4.1') === 'gpt-4.1', 'Valid model name rejected.');
$check(sf_agentic_model_name("gpt-4.1\r\nX-Test: 1") === '', 'Header injection model name accepted.');
$check(strlen(sf_agentic_text(str_repeat('a', 50), 20)) === 20, 'Bounded text did not clamp.');

$changed = sf_agentic_action_execution_allowed([
    'payload_json' => '{}',
    'risk_level' => 'low',
    'approved_at' => '2026-07-09 10:00:00',
    'updated_at' => '2026-07-09 10:05:00',
]);
$check(empty($changed['ok']) && ($changed['error'] ?? '') === 'action_changed_after_approval', 'Post-approval action mutation was not blocked.');
$check(empty(sf_agentic_action_execution_allowed(['payload_json'=>'{bad'])['ok']), 'Invalid action JSON was accepted.');

$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAIAAAAlC+aJAAAAYElEQVR4nO3PQQ0AIBDAMMC/50MEj4ZkVbDtmVk/OzrgVQNaA1oDWgNaA1oDWgNaA1oDWgNaA1oDWgNaA1oDWgNaA1oDWgNaA1oDWgNaA1oDWgNaA1oDWgNaA1oDWgPaBXKqA31N0fbGAAAAAElFTkSuQmCC', true);
$validImage = is_string($png) ? sf_agentic_validate_image_bytes($png) : ['ok'=>false];
$check(!empty($validImage['ok']) && ($validImage['width'] ?? 0) === 64, 'Valid image bytes were rejected.');
$check(empty(sf_agentic_validate_image_bytes('not-an-image')['ok']), 'Invalid image bytes were accepted.');

$bounded = sf_agentic_bounded_output(['title'=>str_repeat('x',300),'names'=>['One','Two']], ['title'=>100,'names'=>'string_list']);
$check(strlen($bounded['title'] ?? '') === 100 && count($bounded['names'] ?? []) === 2, 'Structured output bounds failed.');

if ($failures) {
    fwrite(STDERR, "Agentic governance smoke tests failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "Agentic governance smoke tests passed.\n";
