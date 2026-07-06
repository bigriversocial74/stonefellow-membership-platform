# Feed Personalization / Follow System + Member Engagement Analytics v2

This combined phase adds:

1. Phase 21: Feed Personalization / Follow System v1
2. Phase 22: Member Engagement Analytics v2

## Added

- `includes/feed_personalization.php`
- `api/feed-preferences.php`
- `api/engagement-analytics.php`
- `admin/engagement-analytics.php`
- `database/migrations/014_feed_personalization_engagement_analytics.sql`

## Updated

- `feed.php`
- `api/posts.php`
- `admin/index.php`
- `includes/installer.php`

## Feed Personalization / Follow System

Capabilities:

- follow creator/content-type targets
- unfollow targets
- tune feed preferences for episode updates, music posts, behind-scenes posts, and merch drops
- personalized member feed ranking
- save feed items
- hide/dismiss feed items
- ranking reasons shown on the member feed
- personalized mode in `api/posts.php`
- member feed preferences API

## Engagement Analytics v2

Capabilities:

- engagement analytics dashboard
- top content by comments, reactions, and saves
- top engaged members
- member score recalculation
- comment, reaction, follow, save, and hide totals
- JSON analytics endpoint
- future-ready daily rollup table

## SQL

A new migration is added:

```txt
014_feed_personalization_engagement_analytics.sql
```

It creates:

- `member_follows`
- `member_feed_preferences`
- `member_feed_items`
- `engagement_analytics_daily`
- `member_engagement_scores`

The installer plan now runs migrations `001` through `014`.

## Review

After merging, run:

1. `install.php` on a clean environment and confirm migration `014` is listed.
2. `feed.php` as a guest and as a signed-in member.
3. Save preferences from `feed.php`.
4. Follow/unfollow feed categories from `feed.php`.
5. Save and hide feed items.
6. Open `api/feed-preferences.php` as a signed-in member.
7. Open `admin/engagement-analytics.php` as admin.
8. Run member score recalculation.
9. Open `api/engagement-analytics.php` as admin.
