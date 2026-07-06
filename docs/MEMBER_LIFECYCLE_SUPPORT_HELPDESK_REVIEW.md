# Member Lifecycle + Support Help Desk Review Notes

Branch: `feature/member-lifecycle-support-v1`

Post-merge checks:

1. Apply migration `016` after migration `015`.
2. Open `admin/member-lifecycle.php` as admin.
3. Add a lifecycle note to a member.
4. Create a retention task for a member.
5. Mark a retention task done.
6. Open `support.php` as a signed-in member.
7. Create a member support ticket.
8. Reply to the ticket from the member view.
9. Open `admin/support.php` as admin.
10. Update ticket priority/status.
11. Add an admin reply and an internal note.
12. Open `api/member-lifecycle.php` as admin.
13. Open `api/support-tickets.php` as member and admin.
14. Confirm `install.php` lists migration `016`.

SQL:

- New migration required: `database/migrations/016_member_lifecycle_support_helpdesk.sql`.
- Installer now runs through migration `016`.
