# Production Monitoring / Error Log Center v1 + System Notifications / Incident Alerts v1

This combined phase adds:

1. Phase 33: Production Monitoring / Error Log Center v1
2. Phase 34: System Notifications + Incident Alerts v1

## Added

- `includes/monitoring_alerts.php`
- `admin/monitoring.php`
- `admin/incidents.php`
- `api/monitoring.php`
- `api/incidents.php`
- `database/migrations/020_monitoring_incident_alerts.sql`

## Updated

- `admin/index.php`
- `includes/installer.php`

## Production Monitoring / Error Log Center v1

Capabilities:

- health snapshots
- service checks
- failed notification counter
- failed scheduler/job counter
- failed payment counter
- open incident counter
- PHP error log metadata
- monitoring error records
- error severity/status workflow
- manual error capture
- automatic critical health incident creation
- monitoring JSON API

## System Notifications + Incident Alerts v1

Capabilities:

- incident records
- incident severity workflow
- incident status workflow
- incident event timeline
- alert rules
- admin alert records
- email alert routing through the existing notification queue
- alert read/dismiss workflow
- incident JSON API
- admin incident email template seed

## SQL

New migration:

```txt
database/migrations/020_monitoring_incident_alerts.sql
```

Creates:

- `monitoring_health_snapshots`
- `monitoring_error_events`
- `monitoring_service_checks`
- `incident_records`
- `incident_events`
- `alert_rules`
- `admin_alert_notifications`

Also seeds:

- default service checks
- default alert rules
- `admin_incident_alert` email template

Installer now runs migrations through `020`.

## Existing installs

Apply migration `020` after migration `019`.
