<?php
require_once __DIR__ . '/db.php';

function sf_theme_db(): ?PDO { return sf_db(); }
function sf_theme_table_exists(string $table): bool {
  $pdo = sf_theme_db(); if (!$pdo) return false;
  try { $stmt = $pdo->prepare('SHOW TABLES LIKE ?'); $stmt->execute([$table]); return (bool)$stmt->fetchColumn(); }
  catch (Throwable $e) { error_log('Theme table check failed: ' . $e->getMessage()); return false; }
}
function sf_theme_ready(): bool { return sf_theme_table_exists('show_themes') && sf_theme_table_exists('show_theme_images'); }
function sf_theme_slug(string $name): string { $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name) ?: 'theme', '-')); return $slug ?: 'theme'; }
function sf_theme_default_palette(): array { return ['background'=>'#030302','panel'=>'#0b0907','accent'=>'#d6ad6c','accent_secondary'=>'#c79a52','text'=>'#ead8bc','muted'=>'#b09b79','border'=>'rgba(214,173,108,.18)']; }
function sf_theme_default_image_slots(): array {
  return [
    ['image_key'=>'home_hero','title'=>'Home Hero','page_location'=>'index.php hero','current_path'=>'images/home/home-hero.jpg','aspect_ratio'=>'16:9','recommended_size'=>'1920x1080','sort_order'=>10,'prompt'=>'Main cinematic brand hero for the show. Dark western rock drama scene with desert road, stage lights, guitar case, smoky atmosphere, warm gold highlights, premium streaming series poster feel.'],
    ['image_key'=>'series_characters_hero','title'=>'Series Characters Hero','page_location'=>'series-characters.php hero','current_path'=>'images/cast/cast-template-hero.png','aspect_ratio'=>'21:9','recommended_size'=>'1920x900','sort_order'=>20,'prompt'=>'Cinematic ensemble cast banner, dark western rock mood, silhouettes of musicians and drifters, warm gold rim light, desert and stage blend.'],
    ['image_key'=>'character_portrait_jax','title'=>'Jax Character Portrait','page_location'=>'character.php hero/profile','current_path'=>'images/cast/cast-jax.png','aspect_ratio'=>'3:4','recommended_size'=>'1200x1600','sort_order'=>30,'prompt'=>'Rugged male frontman, dark wavy hair, light stubble, leather jacket, western rock style, moody desert light, cinematic streaming-series portrait.'],
    ['image_key'=>'music_hero','title'=>'Music Hero','page_location'=>'music.php hero','current_path'=>'images/music/music-hero-guitar.png','aspect_ratio'=>'16:10','recommended_size'=>'1600x1000','sort_order'=>40,'prompt'=>'Dramatic guitar and stage-light scene, smoky music venue, premium rock drama soundtrack mood.'],
    ['image_key'=>'album_cover','title'=>'Album Cover','page_location'=>'album/music/player pages','current_path'=>'images/music/soundtrack-cover.png','aspect_ratio'=>'1:1','recommended_size'=>'1400x1400','sort_order'=>50,'prompt'=>'Album cover, dark road, worn guitar, desert horizon, cinematic music poster.'],
    ['image_key'=>'episode_poster','title'=>'Episode Poster','page_location'=>'episodes.php cards','current_path'=>'images/episodes/template-card-01.png','aspect_ratio'=>'16:9','recommended_size'=>'1600x900','sort_order'=>60,'prompt'=>'Cinematic episode still, desert road and backstage tension, warm noir lighting, premium streaming series card art.'],
    ['image_key'=>'merch_hero','title'=>'Merch Hero','page_location'=>'merch.php hero','current_path'=>'images/merch/merch-hero.png','aspect_ratio'=>'16:9','recommended_size'=>'1920x1080','sort_order'=>70,'prompt'=>'Premium merch campaign image, hoodie and apparel flatlay, warm studio lighting, show-specific color palette.'],
  ];
}
function sf_theme_all(): array { if (!sf_theme_ready()) return []; try { return sf_theme_db()->query('SELECT * FROM show_themes ORDER BY is_active DESC, updated_at DESC, id DESC')->fetchAll() ?: []; } catch (Throwable $e) { return []; } }
function sf_theme_active(): ?array { if (!sf_theme_ready()) return null; try { $row = sf_theme_db()->query('SELECT * FROM show_themes WHERE is_active = 1 ORDER BY id DESC LIMIT 1')->fetch(); return $row ?: null; } catch (Throwable $e) { return null; } }
function sf_theme_find(int $id): ?array { if (!sf_theme_ready() || $id <= 0) return null; try { $stmt = sf_theme_db()->prepare('SELECT * FROM show_themes WHERE id=? LIMIT 1'); $stmt->execute([$id]); $row = $stmt->fetch(); return $row ?: null; } catch (Throwable $e) { return null; } }
function sf_theme_images(int $themeId): array { if (!sf_theme_ready() || $themeId <= 0) return []; try { $stmt = sf_theme_db()->prepare('SELECT * FROM show_theme_images WHERE theme_id=? ORDER BY sort_order ASC, id ASC'); $stmt->execute([$themeId]); return $stmt->fetchAll() ?: []; } catch (Throwable $e) { return []; } }
function sf_theme_image_find(int $id): ?array { if (!sf_theme_ready() || $id <= 0) return null; try { $stmt = sf_theme_db()->prepare('SELECT * FROM show_theme_images WHERE id=? LIMIT 1'); $stmt->execute([$id]); $row = $stmt->fetch(); return $row ?: null; } catch (Throwable $e) { return null; } }
function sf_theme_palette(array $theme): array { $json = $theme['palette_json'] ?? ''; $data = is_string($json) ? json_decode($json, true) : (is_array($json) ? $json : []); return array_merge(sf_theme_default_palette(), is_array($data) ? $data : []); }
function sf_theme_create(array $data): int {
  if (!sf_theme_ready()) return 0;
  $name = trim((string)($data['theme_name'] ?? 'New Theme'));
  $slug = sf_theme_slug((string)($data['theme_slug'] ?? $name));
  $palette = json_encode($data['palette'] ?? sf_theme_default_palette(), JSON_UNESCAPED_SLASHES);
  try {
    $stmt = sf_theme_db()->prepare('INSERT INTO show_themes (theme_name, theme_slug, description, mood_prompt, palette_json, image_model, image_quality, status) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->execute([$name,$slug,trim((string)($data['description'] ?? '')),trim((string)($data['mood_prompt'] ?? '')),$palette,trim((string)($data['image_model'] ?? 'gpt-image-1')),trim((string)($data['image_quality'] ?? 'high')),trim((string)($data['status'] ?? 'draft')) ?: 'draft']);
    $themeId = (int)sf_theme_db()->lastInsertId();
    foreach (sf_theme_default_image_slots() as $slot) sf_theme_upsert_image($themeId, $slot);
    return $themeId;
  } catch (Throwable $e) { error_log('Theme create failed: ' . $e->getMessage()); return 0; }
}
function sf_theme_update(int $themeId, array $data): bool {
  if (!sf_theme_ready() || $themeId <= 0) return false;
  $palette = json_encode($data['palette'] ?? sf_theme_default_palette(), JSON_UNESCAPED_SLASHES);
  try { $stmt = sf_theme_db()->prepare('UPDATE show_themes SET theme_name=?, theme_slug=?, description=?, mood_prompt=?, palette_json=?, image_model=?, image_quality=?, status=? WHERE id=?'); return $stmt->execute([trim((string)$data['theme_name']),sf_theme_slug((string)$data['theme_slug']),trim((string)($data['description'] ?? '')),trim((string)($data['mood_prompt'] ?? '')),$palette,trim((string)($data['image_model'] ?? 'gpt-image-1')),trim((string)($data['image_quality'] ?? 'high')),trim((string)($data['status'] ?? 'draft')),$themeId]); } catch (Throwable $e) { error_log('Theme update failed: ' . $e->getMessage()); return false; }
}
function sf_theme_activate(int $themeId): bool { if (!sf_theme_ready() || $themeId <= 0) return false; try { sf_theme_db()->beginTransaction(); sf_theme_db()->exec('UPDATE show_themes SET is_active=0'); $stmt=sf_theme_db()->prepare("UPDATE show_themes SET is_active=1, status='active' WHERE id=?"); $ok=$stmt->execute([$themeId]); sf_theme_db()->commit(); return $ok; } catch(Throwable $e){ if(sf_theme_db()?->inTransaction()) sf_theme_db()->rollBack(); return false; } }
function sf_theme_upsert_image(int $themeId, array $slot): bool {
  if (!sf_theme_ready() || $themeId <= 0) return false;
  try { $stmt = sf_theme_db()->prepare("INSERT INTO show_theme_images (theme_id,image_key,title,page_location,current_path,aspect_ratio,recommended_size,prompt,status,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE title=VALUES(title), page_location=VALUES(page_location), current_path=VALUES(current_path), aspect_ratio=VALUES(aspect_ratio), recommended_size=VALUES(recommended_size), prompt=VALUES(prompt), status=VALUES(status), sort_order=VALUES(sort_order)"); return $stmt->execute([$themeId,$slot['image_key'],$slot['title'],$slot['page_location'] ?? '',$slot['current_path'] ?? '',$slot['aspect_ratio'] ?? '',$slot['recommended_size'] ?? '',$slot['prompt'] ?? '',$slot['status'] ?? 'active',(int)($slot['sort_order'] ?? 100)]); } catch(Throwable $e){ error_log('Theme image upsert failed: '.$e->getMessage()); return false; }
}
function sf_theme_update_image(int $imageId, array $data): bool {
  if (!sf_theme_ready() || $imageId <= 0) return false;
  try { $stmt=sf_theme_db()->prepare('UPDATE show_theme_images SET title=?, page_location=?, current_path=?, generated_path=?, approved_path=?, aspect_ratio=?, recommended_size=?, prompt=?, status=?, sort_order=? WHERE id=?'); return $stmt->execute([trim((string)$data['title']),trim((string)($data['page_location'] ?? '')),trim((string)($data['current_path'] ?? '')),trim((string)($data['generated_path'] ?? '')),trim((string)($data['approved_path'] ?? '')),trim((string)($data['aspect_ratio'] ?? '')),trim((string)($data['recommended_size'] ?? '')),trim((string)($data['prompt'] ?? '')),trim((string)($data['status'] ?? 'draft')),(int)($data['sort_order'] ?? 100),$imageId]); } catch(Throwable $e){ return false; }
}
function sf_theme_create_job(int $themeId, ?int $imageId, string $action, string $status, array $payload = [], string $path = '', string $error = ''): int {
  if (!sf_theme_ready()) return 0;
  try { $stmt=sf_theme_db()->prepare('INSERT INTO show_theme_image_jobs (theme_id, theme_image_id, action_type, status, request_payload, generated_path, error_message, completed_at) VALUES (?,?,?,?,?,?,?,?)'); $stmt->execute([$themeId,$imageId,$action,$status,json_encode($payload,JSON_UNESCAPED_SLASHES),$path,$error,in_array($status,['complete','failed'],true)?date('Y-m-d H:i:s'):null]); return (int)sf_theme_db()->lastInsertId(); } catch(Throwable $e){ return 0; }
}
function sf_theme_generation_prompt(array $theme, array $image): string { return trim((string)($theme['mood_prompt'] ?? '') . "\n\nImage slot: " . ($image['title'] ?? '') . "\nLocation: " . ($image['page_location'] ?? '') . "\nAspect ratio: " . ($image['aspect_ratio'] ?? '') . "\nPrompt: " . ($image['prompt'] ?? '')); }
function sf_theme_api_ready(): bool { return (bool)getenv('OPENAI_API_KEY'); }
function sf_theme_generate_image(array $theme, array $image): array {
  $themeId=(int)($theme['id']??0); $imageId=(int)($image['id']??0); $prompt=sf_theme_generation_prompt($theme,$image); $payload=['model'=>$theme['image_model'] ?: 'gpt-image-1','prompt'=>$prompt,'size'=>'1024x1024','quality'=>$theme['image_quality'] ?: 'high'];
  if (!sf_theme_api_ready()) { sf_theme_create_job($themeId,$imageId,'generate_one','queued',$payload,'','OPENAI_API_KEY is not configured.'); return ['ok'=>false,'queued'=>true,'message'=>'OPENAI_API_KEY is not configured. Generation request was recorded as queued.']; }
  $key=(string)getenv('OPENAI_API_KEY');
  $ch=curl_init('https://api.openai.com/v1/images/generations');
  curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.$key],CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_SLASHES),CURLOPT_TIMEOUT=>120]);
  $body=curl_exec($ch); $err=curl_error($ch); $code=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE); curl_close($ch);
  if ($body===false || $code>=400) { sf_theme_create_job($themeId,$imageId,'generate_one','failed',$payload,'',$err ?: substr((string)$body,0,500)); return ['ok'=>false,'message'=>'Image API request failed.']; }
  $json=json_decode((string)$body,true); $b64=$json['data'][0]['b64_json'] ?? '';
  if (!$b64) { sf_theme_create_job($themeId,$imageId,'generate_one','failed',$payload,'','No b64_json returned.'); return ['ok'=>false,'message'=>'No generated image payload returned.']; }
  $dir=dirname(__DIR__).'/assets/images/generated-themes/'.sf_theme_slug((string)$theme['theme_slug']); if(!is_dir($dir)) @mkdir($dir,0775,true);
  $file=sf_theme_slug((string)$image['image_key']).'-'.date('Ymd-His').'.png'; $abs=$dir.'/'.$file; file_put_contents($abs,base64_decode($b64)); $rel='images/generated-themes/'.sf_theme_slug((string)$theme['theme_slug']).'/'.$file;
  sf_theme_db()->prepare('UPDATE show_theme_images SET generated_path=?, last_generated_at=NOW(), status=? WHERE id=?')->execute([$rel,'generated',$imageId]); sf_theme_create_job($themeId,$imageId,'generate_one','complete',$payload,$rel,'');
  return ['ok'=>true,'path'=>$rel,'message'=>'Image generated. Review and approve before publishing.'];
}
function sf_theme_generate_all(int $themeId): array { $theme=sf_theme_find($themeId); if(!$theme)return ['ok'=>false,'message'=>'Theme not found.']; $results=[]; foreach(sf_theme_images($themeId) as $image){ $results[$image['image_key']]=sf_theme_generate_image($theme,$image); } return ['ok'=>true,'results'=>$results]; }
?>
