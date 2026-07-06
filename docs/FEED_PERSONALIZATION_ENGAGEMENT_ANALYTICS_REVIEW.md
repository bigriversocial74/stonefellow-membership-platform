# Feed Personalization + Engagement Analytics Review Notes

Branch: `feature/feed-personalization-analytics-v1`

Post-merge checks:

1. Open `feed.php` as a guest.
2. Open `feed.php` as a signed-in member.
3. Save feed preferences.
4. Follow and unfollow post categories.
5. Save a feed item.
6. Hide a feed item.
7. Open `api/feed-preferences.php` as a signed-in member.
8. Open `api/posts.php?personalized=1` as a signed-in member.
9. Open `admin/engagement-analytics.php` as admin.
10. Run score recalculation.
11. Open `api/engagement-analytics.php` as admin.
12. Confirm installer lists and runs migration `014`.

SQL:

- New migration key: `014`.
- Existing installs should apply `database/migrations/014_feed_personalization_engagement_analytics.sql`.
