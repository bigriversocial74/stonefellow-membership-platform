# SQL Note

This phase adds a new migration key instead of expanding migration `013` again.

New migration:

```txt
database/migrations/014_feed_personalization_engagement_analytics.sql
```

Tables:

- `member_follows`
- `member_feed_preferences`
- `member_feed_items`
- `engagement_analytics_daily`
- `member_engagement_scores`

Installer:

- `includes/installer.php` now runs migrations through `014`.

Existing installs:

- Apply migration `014` after the current `013` schema is present.
