# Stonefellow Member Auth + Access Gates v1

This phase turns the membership foundation into a working account/access layer.

## New / upgraded files

- `includes/auth.php` — signup, signin, logout, remember-me token, CSRF, password reset, current user helpers, subscription activation helpers.
- `includes/membership.php` — access level checks, member snapshot, subscription lookup, direct grant lookup.
- `signin.php` — database-backed login.
- `signup.php` — database-backed registration. The first registered user becomes `admin`.
- `logout.php` — session and remember-me cleanup.
- `forgot-password.php` — creates secure single-use reset tokens.
- `reset-password.php` — validates reset token and updates password hash.
- `account.php` — member account dashboard with profile, plan, grants, audio progress, and video progress.
- `admin/members.php` — admin member list, role/status editing, manual plan assignment, and plan cancellation.

## Access behavior

- Logged-out visitors are `public`.
- Signed-in users without an active paid subscription are `free_account`.
- Active Monthly/Annual subscription users are `subscriber`.
- Founding Fan users are `founding_fan`.
- Admin users are `admin`.

## Paying-member features now gated

- `member.php` requires login.
- `playlists.php` requires login and only allows private playlist actions for paid subscribers.
- `api/playlist.php` now rejects non-subscribers with `subscription_required`.
- `watch.php` already checks video access level and shows the membership lock overlay when needed.
- `player.php`, `album.php`, and `song.php` now choose full audio only for paying members when the full audio file actually exists; otherwise they safely fall back to preview audio.

## Subscription activation note

`subscribe.php` now supports sandbox/manual plan activation when the database is configured. This is useful for testing access gates before a real payment gateway exists.

Before production, replace direct plan activation with a Stripe/payment provider checkout and activate `user_subscriptions` from verified webhooks only.

## Database requirements

No new SQL migration is required for this phase. It uses tables already present in the base SQL and prior migrations:

- `users`
- `user_auth_tokens`
- `login_attempts`
- `subscription_plans`
- `user_subscriptions`
- `content_access_grants`
- `audio_play_events`
- `user_song_progress`
- `video_watch_events`
- `user_video_progress`
- `user_episode_progress`
- `playlists`
- `playlist_songs`

## Setup flow

1. Configure `SF_DB_HOST`, `SF_DB_NAME`, `SF_DB_USER`, and `SF_DB_PASS`.
2. Run the base SQL.
3. Run migrations `001` and `002`.
4. Visit `signup.php` and create the first user. The first user is automatically assigned `admin`.
5. Visit `admin/members.php` to manage members and assign plans.
6. Visit `subscribe.php` to test sandbox plan activation from the public member flow.
