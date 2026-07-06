<?php
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/notifications.php';

function sf_payment_provider(): string {
  $provider = sf_get_setting('payment_provider', getenv('SF_PAYMENT_PROVIDER') ?: 'sandbox') ?: 'sandbox';
  return in_array($provider, ['sandbox','stripe','paypal'], true) ? $provider : 'sandbox';
}
function sf_payment_mode(): string { $mode = getenv('SF_PAYMENT_MODE') ?: sf_get_setting('payment_mode', 'sandbox') ?: 'sandbox'; return in_array($mode, ['sandbox','test','live'], true) ? $mode : 'sandbox'; }
function sf_payment_provider_label(?string $provider = null): string { $provider = $provider ?: sf_payment_provider(); return ['sandbox'=>'Sandbox','stripe'=>'Stripe','paypal'=>'PayPal'][$provider] ?? ucfirst($provider); }
function sf_payment_secret_mask(?string $value): string { $value=(string)$value; if($value==='') return 'missing'; return substr($value,0,4) . '…' . substr($value,-4); }
function sf_payment_gateway_ready(?string $provider = null): bool { $provider=$provider?:sf_payment_provider(); if($provider==='sandbox')return true; if($provider==='stripe')return (bool)getenv('SF_STRIPE_SECRET_KEY'); if($provider==='paypal')return (bool)(getenv('SF_PAYPAL_CLIENT_ID')&&getenv('SF_PAYPAL_SECRET')); return false; }
function sf_payment_absolute_url(string $path): string { $url = sf_url($path); if (preg_match('~^https?://~i', $url)) return $url; $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http'; $host = $_SERVER['HTTP_HOST'] ?? 'localhost'; return $scheme . '://' . $host . '/' . ltrim($url, '/'); }
function sf_payment_gateway_status(): array { $provider=sf_payment_provider(); return ['provider'=>$provider,'label'=>sf_payment_provider_label($provider),'ready'=>sf_payment_gateway_ready($provider),'mode'=>sf_payment_mode(),'stripe_public'=>sf_get_setting('stripe_publishable_key',getenv('SF_STRIPE_PUBLISHABLE_KEY')?:''),'paypal_client_id'=>sf_get_setting('paypal_client_id',getenv('SF_PAYPAL_CLIENT_ID')?:''),'stripe_secret'=>sf_payment_secret_mask(getenv('SF_STRIPE_SECRET_KEY')?:''),'webhook_secret'=>sf_payment_secret_mask(getenv('SF_STRIPE_WEBHOOK_SECRET')?:'')]; }

function sf_payment_http_post(string $url, array $fields, array $headers = [], bool $form = true): array {
  if (!function_exists('curl_init')) return ['ok'=>false,'error'=>'PHP cURL extension is not available.'];
  $ch = curl_init($url);
  $body = $form ? http_build_query($fields) : json_encode($fields, JSON_UNESCAPED_SLASHES);
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$body, CURLOPT_TIMEOUT=>20, CURLOPT_HTTPHEADER=>$headers]);
  $raw = curl_exec($ch); $errno = curl_errno($ch); $error = curl_error($ch); $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE); curl_close($ch);
  $json = json_decode((string)$raw, true);
  return ['ok'=>$errno===0 && $code>=200 && $code<300, 'status'=>$code, 'error'=>$errno?$error:($json['error']['message']??null), 'body'=>is_array($json)?$json:['raw'=>$raw]];
}

function sf_payment_create_stripe_checkout(array $payload): array {
  $secret = getenv('SF_STRIPE_SECRET_KEY') ?: '';
  if ($secret === '') return ['ok'=>false,'error'=>'Missing SF_STRIPE_SECRET_KEY'];
  $token=(string)($payload['checkout_token']??''); $amount=(int)($payload['amount_cents']??0); $currency=strtolower((string)($payload['currency']??'usd'));
  $success=sf_payment_absolute_url('billing-success.php?token='.urlencode($token).'&provider=stripe'); $cancel=sf_payment_absolute_url('billing-cancel.php?token='.urlencode($token));
  $fields=['mode'=>'subscription','success_url'=>$success,'cancel_url'=>$cancel,'client_reference_id'=>$token,'line_items[0][quantity]'=>1,'line_items[0][price_data][currency]'=>$currency,'line_items[0][price_data][unit_amount]'=>$amount,'line_items[0][price_data][recurring][interval]'=>($payload['billing_interval']??'month')==='year'?'year':'month','line_items[0][price_data][product_data][name]'=>substr((string)($payload['plan_name']??'Stonefellow Membership'),0,190),'metadata[checkout_token]'=>$token,'subscription_data[metadata][checkout_token]'=>$token];
  if (!empty($payload['customer_email'])) $fields['customer_email']=(string)$payload['customer_email'];
  $res=sf_payment_http_post('https://api.stripe.com/v1/checkout/sessions',$fields,['Authorization: Bearer '.$secret,'Content-Type: application/x-www-form-urlencoded']);
  if(!$res['ok']) return ['ok'=>false,'error'=>$res['error']?:'Stripe checkout creation failed','provider'=>'stripe','mode'=>sf_payment_mode()];
  $session=$res['body'];
  return ['ok'=>true,'provider'=>'stripe','provider_checkout_id'=>(string)($session['id']??''),'checkout_url'=>(string)($session['url']??$payload['local_checkout_url']??''),'mode'=>sf_payment_mode(),'raw'=>$session];
}

function sf_payment_create_checkout(array $payload): array {
  $provider=sf_payment_provider(); $token=(string)($payload['checkout_token']??bin2hex(random_bytes(16))); $localUrl=(string)($payload['local_checkout_url']??'');
  if($provider==='stripe' && sf_payment_gateway_ready('stripe')){ $stripe=sf_payment_create_stripe_checkout($payload); if(!empty($stripe['ok'])) return $stripe; return ['ok'=>true,'provider'=>'sandbox','provider_checkout_id'=>'stripe_fallback_'.substr(hash('sha256',$token),0,18),'checkout_url'=>$localUrl,'mode'=>'sandbox-fallback','message'=>$stripe['error']??'Stripe unavailable; using local checkout fallback.']; }
  $externalId=$provider.'_checkout_'.substr(hash('sha256',$token.json_encode($payload)),0,18);
  return ['ok'=>true,'provider'=>$provider==='sandbox'?'sandbox':$provider,'provider_checkout_id'=>$externalId,'checkout_url'=>$localUrl,'mode'=>$provider==='sandbox'?'sandbox':'adapter-ready','message'=>$provider==='sandbox'?'Sandbox checkout created locally.':sf_payment_provider_label($provider).' adapter shell ready; local checkout remains available.'];
}

function sf_payment_extract_header(array $headers, string $name): string { $lookup=strtolower(str_replace('_','-',$name)); foreach($headers as $k=>$v){ $key=strtolower(str_replace('_','-',preg_replace('/^HTTP_/','',(string)$k))); if($key===$lookup) return is_array($v)?(string)reset($v):(string)$v; } return ''; }
function sf_payment_verify_stripe_signature(string $rawBody, string $header, string $secret, int $tolerance = 300): bool { if($secret===''||$header==='')return false; $parts=[]; foreach(explode(',',$header) as $piece){ [$k,$v]=array_pad(explode('=',trim($piece),2),2,''); $parts[$k][]=$v; } $timestamp=(int)($parts['t'][0]??0); if(!$timestamp||abs(time()-$timestamp)>$tolerance)return false; $signed=$timestamp.'.'.$rawBody; $expected=hash_hmac('sha256',$signed,$secret); foreach($parts['v1']??[] as $sig){ if(hash_equals($expected,$sig)) return true; } return false; }
function sf_payment_verify_webhook(string $provider, string $rawBody, array $headers = []): bool { if($provider==='sandbox')return true; if($provider==='stripe')return sf_payment_verify_stripe_signature($rawBody, sf_payment_extract_header($headers,'STRIPE_SIGNATURE'), getenv('SF_STRIPE_WEBHOOK_SECRET')?:''); if($provider==='paypal')return getenv('SF_PAYPAL_WEBHOOK_ID') ? sf_payment_extract_header($headers,'PAYPAL_TRANSMISSION_ID')!=='' : false; return false; }
function sf_payment_record_gateway_event(string $provider, string $eventType, array $payload, string $status = 'received', ?string $error = null): void { $pdo=sf_db(); if(!$pdo)return; try{$eventId=(string)($payload['id']??$payload['event_id']??hash('sha256',$provider.$eventType.json_encode($payload))); if(sf_settings_table_exists('payment_gateway_webhook_events')){ $stmt=$pdo->prepare("INSERT INTO payment_gateway_webhook_events (provider, provider_event_id, event_type, status, payload_json, error_message, processed_at) VALUES (?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE status=VALUES(status), payload_json=VALUES(payload_json), error_message=VALUES(error_message), processed_at=NOW()"); $stmt->execute([$provider,$eventId,$eventType,$status,json_encode($payload,JSON_UNESCAPED_SLASHES),$error]); }}catch(Throwable $e){error_log('Stonefellow gateway event log failed: '.$e->getMessage());} }
function sf_payment_event_object(array $payload): array { $object=$payload['data']['object']??$payload['resource']??$payload; return is_array($object)?$object:[]; }
function sf_payment_checkout_token_from_event(array $payload): string { $object=sf_payment_event_object($payload); return (string)($object['client_reference_id']??$object['metadata']['checkout_token']??$object['subscription_details']['metadata']['checkout_token']??''); }
function sf_payment_process_gateway_event(string $provider, string $eventType, array $payload): array { $pdo=sf_db(); if(!$pdo)return ['ok'=>false,'message'=>'No database connection.']; $object=sf_payment_event_object($payload); try{ if($provider==='stripe' && $eventType==='checkout.session.completed'){ $token=sf_payment_checkout_token_from_event($payload); if($token==='')return ['ok'=>false,'message'=>'Checkout token missing.']; if(function_exists('sf_billing_complete_provider_checkout')){ return sf_billing_complete_provider_checkout($token, ['provider'=>'stripe','provider_payment_id'=>(string)($object['payment_intent']??$object['id']??''),'provider_subscription_id'=>(string)($object['subscription']??''),'provider_customer_id'=>(string)($object['customer']??''),'payload'=>$payload]); } } if($provider==='stripe' && in_array($eventType,['customer.subscription.deleted','customer.subscription.paused'],true)){ $sub=(string)($object['id']??''); if($sub!==''&&sf_settings_table_exists('user_subscriptions')){ $pdo->prepare("UPDATE user_subscriptions SET status='canceled', canceled_at=NOW(), updated_at=NOW() WHERE provider_subscription_id=? OR external_subscription_id=?")->execute([$sub,$sub]); return ['ok'=>true,'message'=>'Subscription canceled from provider event.']; } } if($provider==='stripe' && $eventType==='invoice.payment_failed'){ $sub=(string)($object['subscription']??''); if($sub!==''&&sf_settings_table_exists('user_subscriptions')){ $pdo->prepare("UPDATE user_subscriptions SET status='past_due', updated_at=NOW() WHERE provider_subscription_id=? OR external_subscription_id=?")->execute([$sub,$sub]); return ['ok'=>true,'message'=>'Subscription marked past_due.']; } } return ['ok'=>true,'message'=>'Event recorded; no lifecycle mutation required.']; }catch(Throwable $e){ error_log('Stonefellow gateway processing failed: '.$e->getMessage()); return ['ok'=>false,'message'=>$e->getMessage()]; } }
?>
