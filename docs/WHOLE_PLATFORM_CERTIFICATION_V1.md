# Stonefellow Whole-Platform Certification v1

## Purpose

Stonefellow now has separate code audits for platform security, AI governance, revenue and media access, operations and recovery, front-end quality, authentication and privacy, content integrity, delivery, staging launch certification, integration scenarios, and production promotion. This final phase provides one authoritative view of those controls.

## Two scores

### Source / CI certification

The source score verifies ten cumulative areas:

1. Core platform and security
2. AI and agentic governance
3. Revenue, membership, and media
4. Data, operations, and recovery
5. Front-end and accessibility
6. Authentication, privacy, and abuse prevention
7. Publishing, search, import, and moderation
8. Delivery, scheduler, campaigns, and webhooks
9. Staging certification and integration scenarios
10. Release candidate and production launch

A 10/10 source score means every required audit artifact is present and the complete GitHub Actions suite passes PHP lint, smoke tests, and static audits.

### Operational certification

The operational score requires evidence that static code cannot provide:

- all required certification SQL tables are installed;
- AI staging certification passes at 100%;
- the whole-platform staging certificate passes at 100%;
- every required staging integration scenario has a passed execution;
- a fresh evidence-backed backup is available;
- the deployment release gate passes;
- the exact deployed commit is configured; and
- a production launch promotion is approved, deployed, or verified.

The platform must never describe itself as operationally certified solely because the source score is 10/10.

## Admin workflow

Open `/admin/platform-certification.php` to view both scores and navigate to:

- AI staging certification
- staging launch certification
- staging integration matrix
- production launch promotion
- operations and recovery
- deployment releases
- deployment preflight

The operations dashboard also links to this certification hub.

## Initial score

After the prior phases, subsystem quality was high but there was no single cumulative score or explicit separation between source and deployed evidence: **8.1/10**.

## Final source score

The final smoke test and audit require all ten cumulative source sections to score **10/10**.

## SQL

No new SQL is required for this final aggregation phase. It depends on the three earlier SQL packages:

1. `database/staging_launch_certification_v1.sql`
2. `database/staging_integration_matrix_v1.sql`
3. `database/production_launch_promotion_v1.sql`
