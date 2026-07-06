# SQL Note

This PR expands migration `013_gateway_publishing_workflow_v1.sql` rather than adding a new installer key.

Fresh installation behavior:

- Installer runs migration `013`.
- Migration `013` creates publishing, member library, search discovery, activity feed, and content ops tables.

Existing installed database behavior:

- If migration `013` was already imported before this PR, manually apply the new `member_activity_events` and `content_ops_tasks` table definitions from migration `013`.
