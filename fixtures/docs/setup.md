# Tidepool setup guide

This guide explains how to configure a Tidepool installation.

## Settings file

All configuration lives in config/settings.ini inside the
installation folder. Restart Tidepool after every change.

## Retry limit

The retry_limit setting in config/settings.ini controls how
many times Tidepool retries a failed sync before it reports
an error. Allowed values are 0 to 5, and the default is 3.
Set it to 0 to disable retries.

```ini
retry_limit = 3
```

## Sync schedule

The sync_interval setting is the number of minutes between
two syncs. The minimum is 5 minutes, and the default is 15.
