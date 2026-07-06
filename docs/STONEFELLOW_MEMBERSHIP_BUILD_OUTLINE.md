# Stonefellow Membership Platform Build Outline

This outline is the operating map for turning the current static Stonefellow site into a full membership platform with music streaming, member playlists, video access, episode progress tracking, merch commerce, and admin management.

## Current foundation

The current package already includes public pages for the brand, series, episodes, music, player, album, song, merch, cart, checkout, account entry, and subscription entry.

Current key pages:

- `index.php` — public home page.
- `series.php` — series overview.
- `episodes.php` — episode listing.
- `music.php` — original public music landing page.
- `player.php` — full music/audio player experience.
- `album.php` — full album page.
- `song.php` — full song page.
- `merch.php`, `product.php`, `cart.php`, `checkout.php`, `order-confirmation.php` — ecommerce foundation pages.
- `signup.php`, `signin.php`, `forgot-password.php`, `reset-password.php`, `subscribe.php` — membership entry pages.
- `includes/data.php` — static content catalog used by the current front end.
- `database/stonefellow_streaming_platform.sql` — current all-in-one SQL foundation.

## Build goal

Stonefellow should become a fully operational membership site where paying members can:

- Stream full audio tracks.
- View and resume episodes/videos.
- Track audio listening history.
- Track video/episode watch progress.
- Create and manage personal playlists.
- Save songs/albums.
- Access subscriber-only clips, episodes, live sessions, and merch drops.
- Maintain account, subscription, and payment status.

Admins should be able to:

- Manage albums, songs, audio files, artwork, lyrics, and featured music placements.
- Manage episodes, video files, clips, trailers, posters, access levels, and release status.
- Manage members, subscriptions, grants, playlist activity, watch/listen analytics, and merch orders.
- Review streaming usage, completion rates, most played songs, most watched episodes, subscriber conversion, and content performance.

## Phase 1 — Data model and SQL lock-in

Deliverables:

- Keep `database/stonefellow_streaming_platform.sql` as the base install file.
- Add migration files under `database/migrations/` for incremental upgrades.
- Add dedicated tables for video files, video watch events, episode progress, fine-grained audio events, and generalized content grants.
- Seed the subscription plans used by `subscribe.php`.
- Document every SQL file and table group in `docs/SQL_FILE_MAP.md`.

Important rule: after a production install exists, do not keep rewriting the base SQL file for every change. Add migrations instead.

## Phase 2 — Authentication and account system

Pages/endpoints:

- `signup.php`
- `signin.php`
- `forgot-password.php`
- `reset-password.php`
- `account.php`
- `logout.php`
- `includes/auth.php`
- `includes/db.php`
- `includes/session.php`

Core requirements:

- Password hashing with `password_hash()`.
- Secure login sessions.
- Email verification-ready token table.
- Password reset token flow.
- Login attempt logging.
- Role handling for `user` and `admin`.
- Helper functions such as `sf_current_user()`, `sf_require_login()`, `sf_require_admin()`, and `sf_user_has_entitlement()`.

## Phase 3 — Membership, billing, and entitlements

Pages/endpoints:

- `subscribe.php`
- `account-billing.php`
- `membership-success.php`
- `membership-cancel.php`
- `webhooks/stripe.php` or equivalent payment provider webhook.

Core requirements:

- Subscription plans: Monthly Access, Annual Access, Founding Fan.
- Membership status from `user_subscriptions`.
- Entitlement checks from `streaming_entitlements` plus `content_access_grants`.
- Feature gates for full music, premium music, video streaming, live sessions, founding fan access, downloads, playlists, and merch drops.
- Subscriber-only UI messaging without breaking public previews.

Recommended access model:

- Public visitors: landing pages, previews, trailers, merch browsing.
- Free account: save preferences, preview songs, limited watch trailers/clips.
- Monthly member: full music and released episodes.
- Annual member: early episode access and live session archive.
- Founding Fan: premium drops, credits/supporter wall, VIP assets, founding-fan content.
- Admin grant: manual access override.

## Phase 4 — Music streaming and listener tracking

Pages/endpoints:

- `player.php`
- `album.php`
- `song.php`
- `library.php`
- `playlist.php`
- `playlists.php`
- `api/audio/play-event.php`
- `api/audio/progress.php`
- `api/playlists/create.php`
- `api/playlists/update.php`
- `api/playlists/add-song.php`
- `api/playlists/remove-song.php`

Core requirements:

- Public previews use `song_files.file_type = preview`.
- Full tracks require active entitlement.
- Log listen events to `audio_play_events`.
- Update resume/aggregate rows in `user_song_progress`.
- Save songs through `user_saved_songs`.
- User playlists through `playlists` and `playlist_songs`.
- Playlist creation should require a paid plan unless the business wants free accounts to create limited playlists.

Tracking events:

- `play`
- `pause`
- `seek`
- `progress`
- `complete`
- `skip`
- `replay`

Recommended progress rule:

- Send a progress event every 15–30 seconds while audio is playing.
- Mark completed when user reaches at least 90% of the track.
- Do not count rapid refreshes or duplicate events as meaningful plays.

## Phase 5 — Video access and episode tracking

Pages/endpoints:

- `episode.php`
- `watch.php`
- `clips.php`
- `api/video/watch-event.php`
- `api/video/progress.php`

Core requirements:

- Episodes and videos should not be the same table.
- `episodes` stores the story/episode metadata.
- `videos` stores playable content, including episodes, trailers, clips, behind-the-scenes videos, live sessions, and music videos.
- `video_files` stores stream/preview/download file variants.
- `video_watch_events` logs activity events.
- `user_video_progress` stores resume position by playable video.
- `user_episode_progress` stores episode-level completion and last watched state.
- Subscriber checks should happen before serving full video URLs.

Tracking events:

- `play`
- `pause`
- `seek`
- `progress`
- `complete`
- `rewatch`

Recommended progress rule:

- Send a progress event every 15–30 seconds while video is playing.
- Resume playback from `last_position_seconds`.
- Mark completed when user reaches at least 90% of runtime.
- Keep episode progress separate from clips/trailers so a user can complete the episode without completing every related clip.

## Phase 6 — Member library and personalization

Pages:

- `library.php`
- `account.php`
- `watch-history.php`
- `listen-history.php`
- `saved.php`

Core requirements:

- Continue watching.
- Recently played.
- Saved songs.
- Saved albums.
- My playlists.
- Recommended next episode.
- Recommended soundtrack songs by episode.
- Membership status and renewal information.

## Phase 7 — Admin section

Admin pages:

- `admin/index.php` — admin dashboard.
- `admin/music.php` — songs/albums overview.
- `admin/music-albums.php` — album manager.
- `admin/music-songs.php` — song manager.
- `admin/music-upload.php` — audio upload manager.
- `admin/videos.php` — video catalog manager.
- `admin/episodes.php` — episode manager.
- `admin/members.php` — member manager.
- `admin/subscriptions.php` — subscription manager.
- `admin/playlists.php` — playlist moderation/overview.
- `admin/analytics.php` — listen/watch analytics.
- `admin/orders.php` — merch order manager.

Admin requirements:

- Admin-only auth middleware.
- Create/edit/publish/archive content.
- Upload or assign cover art, posters, audio files, and video files.
- Manage content access level.
- Reorder album tracks.
- Link songs to episodes.
- Review member activity and subscription status.
- View analytics summaries.

## Phase 8 — Payments and production hardening

Core requirements:

- Payment provider integration.
- Webhook verification.
- Subscription lifecycle updates.
- Grace periods for failed payments.
- Download protection if downloads are enabled.
- Signed or protected file delivery for full audio/video files.
- Rate limiting on auth and tracking APIs.
- CSRF protection for forms.
- Admin audit log for sensitive changes.

## Phase 9 — Analytics and reporting

Core metrics:

- Total members.
- Active paying members.
- Trialing / past due / canceled members.
- Most played songs.
- Song completion rate.
- Most watched episodes.
- Episode completion rate.
- Continue watching abandonment points.
- Playlist creation count.
- Saved song count.
- Subscriber-only conversion rate.
- Merch conversion by membership tier.

## Phase 10 — Deployment and QA checklist

Before deployment:

- Run PHP syntax checks.
- Import base SQL into a clean database.
- Apply migrations in order.
- Confirm public pages load without login.
- Confirm subscriber-only pages redirect or gate correctly.
- Confirm admin pages require admin role.
- Confirm previews play publicly.
- Confirm full tracks require entitlement.
- Confirm video progress writes to the database.
- Confirm audio progress writes to the database.
- Confirm playlists are private by default.
- Confirm checkout/order pages still work.
- Confirm mobile layouts for player, album, song, episode, and watch pages.

## Immediate next implementation order

1. Apply the membership/video tracking migration.
2. Add `includes/db.php`, `includes/auth.php`, and `includes/entitlements.php`.
3. Build `episode.php` and `watch.php` as full pages.
4. Add tracking API endpoints for audio and video.
5. Add member library pages.
6. Build admin dashboard and catalog managers.
7. Connect static data in `includes/data.php` to database records page by page.
