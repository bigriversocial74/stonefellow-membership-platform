# Runtime Storage

The web installer writes `storage/install.lock` here after installation is complete.

`storage/install.lock` is ignored by Git. Remove it manually only when you intentionally need to rerun the installer after taking a database backup.

Keep this directory writable during installation. After install, permissions can be tightened.
