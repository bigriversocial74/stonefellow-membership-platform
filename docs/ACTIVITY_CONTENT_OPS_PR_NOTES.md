# PR Notes

Phase pair:

- Phase 13: Notifications v2 / Member Activity Feed
- Phase 14: Creator/Admin Content Ops Dashboard

SQL:

- No new installer key.
- Existing migration `013_gateway_publishing_workflow_v1.sql` is expanded with:
  - `member_activity_events`
  - `content_ops_tasks`

Fresh installs receive these through the current installer migration plan.
