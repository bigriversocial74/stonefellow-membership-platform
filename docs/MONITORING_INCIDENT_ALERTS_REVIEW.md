# Monitoring + Incident Alerts Review Notes

Branch: `feature/monitoring-incident-alerts-v1`

Post-merge checks:

1. Apply migration `020` after migration `019`.
2. Open `admin/monitoring.php` as an admin with ops permission.
3. Run a monitoring snapshot.
4. Confirm service checks update.
5. Record a manual error event.
6. Open an incident from the error event.
7. Open `admin/incidents.php`.
8. Update incident status/severity.
9. Add an incident timeline event.
10. Route admin alerts for the incident.
11. Mark alert read/dismissed.
12. Open `api/monitoring.php`.
13. Open `api/incidents.php`.
14. Confirm `install.php` lists migration `020`.

SQL:

- New migration required: `database/migrations/020_monitoring_incident_alerts.sql`.
- Installer now runs through migration `020`.
