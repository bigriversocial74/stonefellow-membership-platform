# Ops Scheduler + Member Messaging Review Notes

Branch: `feature/ops-scheduler-broadcasts-v1`

Post-merge checks:

1. Apply migration `017` after migration `016`.
2. Open `admin/ops-scheduler.php` as admin.
3. Run due jobs.
4. Run a single scheduler job.
5. Confirm job run history is written.
6. Open `api/ops-scheduler.php` as admin.
7. Open `admin/member-messaging.php`.
8. Create a draft member message campaign.
9. Queue recipients.
10. Send/process the campaign.
11. Open `messages.php` as a member and confirm in-app message appears.
12. Mark a message read/archive/dismiss.
13. Open `api/member-notices.php` as admin.
14. Open `api/member-messages.php` as a member.
15. Confirm `install.php` lists migration `017`.

SQL:

- New migration required: `database/migrations/017_ops_scheduler_member_messaging.sql`.
- Installer now runs through migration `017`.
