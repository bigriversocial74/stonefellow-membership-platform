# Streaming Analytics v2

This phase upgrades analytics from basic tracking reports into engagement intelligence.

## Added

- `includes/analytics_v2.php`
- `admin/streaming-analytics.php`
- `api/analytics-summary.php`

## Measures

- Total audio + video events
- Total streaming time
- Average seconds per event
- Unique engaged members
- Subscriber conversion rate
- Revenue per member
- Library saves, watchlist items, likes, and completed content
- Top songs and videos
- Daily audio/video activity

## API

```txt
api/analytics-summary.php?days=30
```

Allowed ranges: `7`, `30`, `90`, `365`.

## Database behavior

No new SQL is required. The v2 layer reads existing analytics, billing, commerce, and library/search tables when they are installed, and safely falls back to available catalog data before installation.
