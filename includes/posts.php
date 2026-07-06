<?php
require_once __DIR__ . '/engagement.php';

function sf_posts_static(): array {
  global $episodes, $catalogSongs, $musicAlbum;
  $episode = $episodes[0] ?? ['title'=>'First to Fall','slug'=>'first-to-fall','image'=>'images/episodes/episode-01.png'];
  $song = $catalogSongs[0] ?? ['title'=>'Born to Burn','slug'=>'born-to-burn','cover'=>'images/music/soundtrack-cover.png'];
  return [
    ['id'=>1,'title'=>'Behind the first chapter','slug'=>'behind-the-first-chapter','excerpt'=>'A creator note on the first Stonefellow episode, the band conflict, and the soundtrack thread running through the story.','body'=>'Stonefellow is built to connect the show, soundtrack, fan reactions, and member-only streaming into one ongoing world. This post is a preview of the creator/news feed layer.','post_type'=>'news','status'=>'published','image_path'=>$episode['image'] ?? 'images/episodes/episode-01.png','published_at'=>date('Y-m-d H:i:s', time()-86400),'author_name'=>'Stonefellow','comment_count'=>2,'reaction_count'=>8,'linked_content_type'=>'episode','linked_content_slug'=>$episode['slug'] ?? 'first-to-fall'],
    ['id'=>2,'title'=>'Soundtrack spotlight: ' . ($song['title'] ?? 'Stonefellow Song'),'slug'=>'soundtrack-spotlight','excerpt'=>'A quick spotlight for the music side of the story and the member audio experience.','body'=>'The music player, album pages, song pages, member library, and comments now connect into the same engagement system.','post_type'=>'music','status'=>'published','image_path'=>$song['cover'] ?? ($musicAlbum['cover'] ?? 'images/music/soundtrack-cover.png'),'published_at'=>date('Y-m-d H:i:s', time()-43200),'author_name'=>'Stonefellow','comment_count'=>1,'reaction_count'=>5,'linked_content_type'=>'song','linked_content_slug'=>$song['slug'] ?? 'born-to-burn'],
  ];
}
function sf_posts_all(string $status = 'published', int $limit = 100): array {
  $limit = max(1, min(200, $limit));
  if (!sf_eng_table_exists('creator_posts')) return sf_posts_static();
  $where = $status !== '' ? 'WHERE p.status = ?' : '';
  $params = $status !== '' ? [$status] : [];
  return sf_eng_fetch_all("SELECT p.*, COALESCE(u.display_name, u.email, 'Stonefellow') AS author_name, (SELECT COUNT(*) FROM fan_comments fc WHERE fc.content_type='post' AND fc.content_id=p.id AND fc.status='approved') AS comment_count, (SELECT COUNT(*) FROM fan_reactions fr WHERE fr.target_type='post' AND fr.target_id=p.id) AS reaction_count FROM creator_posts p LEFT JOIN users u ON u.id=p.author_user_id {$where} ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC LIMIT {$limit}", $params);
}
function sf_post_by_slug(string $slug): ?array {
  $slug = trim($slug);
  if ($slug === '') return null;
  if (!sf_eng_table_exists('creator_posts')) { foreach (sf_posts_static() as $post) if (($post['slug'] ?? '') === $slug) return $post; return null; }
  return sf_eng_fetch_one("SELECT p.*, COALESCE(u.display_name, u.email, 'Stonefellow') AS author_name FROM creator_posts p LEFT JOIN users u ON u.id=p.author_user_id WHERE p.slug=? LIMIT 1", [$slug]);
}
function sf_post_slugify(string $value): string { $value = strtolower(trim($value)); $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?: 'post'; return trim($value, '-') ?: 'post-' . substr(bin2hex(random_bytes(4)), 0, 8); }
function sf_post_save(array $data, int $id = 0): int {
  if (!sf_eng_table_exists('creator_posts')) return 0;
  $title = trim((string)($data['title'] ?? ''));
  if ($title === '') return 0;
  $slug = trim((string)($data['slug'] ?? '')) ?: sf_post_slugify($title);
  $payload = [$data['author_user_id'] ?? sf_current_user_id(), $data['post_type'] ?? 'news', $title, $slug, $data['excerpt'] ?? null, $data['body'] ?? '', $data['image_path'] ?? null, $data['status'] ?? 'draft', $data['is_featured'] ?? 0, $data['published_at'] ?: null, $data['linked_content_type'] ?? null, $data['linked_content_id'] ?: null, $data['linked_content_slug'] ?? null];
  if ($id > 0) {
    sf_eng_execute('UPDATE creator_posts SET author_user_id=?, post_type=?, title=?, slug=?, excerpt=?, body=?, image_path=?, status=?, is_featured=?, published_at=?, linked_content_type=?, linked_content_id=?, linked_content_slug=?, updated_at=NOW() WHERE id=?', array_merge($payload, [$id]));
    return $id;
  }
  sf_eng_execute('INSERT INTO creator_posts (author_user_id, post_type, title, slug, excerpt, body, image_path, status, is_featured, published_at, linked_content_type, linked_content_id, linked_content_slug) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $payload);
  return (int)(sf_eng_db()?->lastInsertId() ?: 0);
}
function sf_post_update_status(int $id, string $status): bool { if (!sf_eng_table_exists('creator_posts') || !in_array($status, ['draft','scheduled','published','archived'], true)) return false; return sf_eng_execute('UPDATE creator_posts SET status=?, updated_at=NOW() WHERE id=?', [$status, $id]); }
function sf_post_delete(int $id): bool { return sf_eng_table_exists('creator_posts') && $id > 0 ? sf_eng_execute('DELETE FROM creator_posts WHERE id=?', [$id]) : false; }
function sf_post_media(int $postId): array { if (!sf_eng_table_exists('creator_post_media') || $postId <= 0) return []; return sf_eng_fetch_all('SELECT * FROM creator_post_media WHERE post_id=? ORDER BY sort_order ASC, id ASC', [$postId]); }
function sf_post_link_url(array $post): string { $type = (string)($post['linked_content_type'] ?? ''); $slug = (string)($post['linked_content_slug'] ?? ''); if ($type === '') return sf_url('post.php?slug=' . urlencode((string)($post['slug'] ?? ''))); return sf_eng_content_url($type, $slug, (int)($post['linked_content_id'] ?? 0)); }
function sf_post_comment_count(string $type, int $id = 0, string $slug = ''): int { if (!sf_eng_table_exists('fan_comments')) return count(sf_comment_static($type, $id)); $where='WHERE content_type=? AND status="approved"'; $params=[$type]; if ($id>0){$where.=' AND content_id=?';$params[]=$id;} if($slug!==''){$where.=' AND content_slug=?';$params[]=$slug;} return (int)(sf_eng_fetch_one("SELECT COUNT(*) AS total FROM fan_comments {$where}", $params)['total'] ?? 0); }
function sf_inline_comment_widget(string $type, int $id = 0, string $slug = '', string $title = 'Comments'): void {
  $comments = sf_comments_for($type, $id, $slug, 'approved', 5);
  $count = sf_post_comment_count($type, $id, $slug);
  $threadUrl = sf_url('comments.php?content_type=' . urlencode($type) . '&content_id=' . $id . ($slug !== '' ? '&slug=' . urlencode($slug) : ''));
  echo '<section class="sf-member-section sf-inline-comments"><div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Fan Thread</span><h2>' . sf_eng_h($title) . ' <small>(' . (int)$count . ')</small></h2></div><a href="' . sf_eng_h($threadUrl) . '">Open full thread</a></div>';
  if (sf_auth_user()) {
    echo '<form class="sf-admin-form sf-inline-comment-form" method="post" action="' . sf_eng_h($threadUrl) . '">' . sf_csrf_field() . '<input type="hidden" name="content_type" value="' . sf_eng_h($type) . '"><input type="hidden" name="content_id" value="' . (int)$id . '"><input type="hidden" name="slug" value="' . sf_eng_h($slug) . '"><label>Add a quick comment<textarea name="body" rows="3" maxlength="2000" placeholder="Share your reaction..." required></textarea></label><div class="sf-admin-form-actions"><button type="submit">Post</button><a href="' . sf_eng_h($threadUrl) . '">View thread</a></div></form>';
  } else {
    echo '<div class="sf-access-gate"><span>Member comments</span><h2>Sign in to comment.</h2><p>Fans can react and reply once signed in.</p><div class="sf-episode-action-row"><a class="sf-primary-action" href="' . sf_url('signin.php') . '">Sign In</a><a class="sf-secondary-action" href="' . sf_url('signup.php') . '">Create Account</a></div></div>';
  }
  echo '<div class="sf-admin-list">';
  foreach ($comments as $comment) echo '<article class="sf-admin-list-row"><strong>' . sf_eng_h($comment['author_name'] ?? $comment['display_name'] ?? $comment['email'] ?? 'Member') . '</strong><span>' . sf_eng_h($comment['created_at'] ?? '') . '</span><p>' . nl2br(sf_eng_h($comment['body'] ?? '')) . '</p><em>' . (int)($comment['reaction_count'] ?? 0) . ' reactions</em></article>';
  if (!$comments) echo '<article class="sf-admin-list-row"><strong>No comments yet.</strong><p>Start the conversation.</p></article>';
  echo '</div></section>';
}
?>
