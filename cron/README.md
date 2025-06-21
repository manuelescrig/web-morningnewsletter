# Cron Job Setup

## Installation

Add this line to your crontab to run the email sending script every 15 minutes:

```bash
# Edit crontab
crontab -e

# Add this line (replace /path/to with your actual path)
*/15 * * * * php /path/to/morningnewsletter/cron/send_emails.php

# For logging output:
*/15 * * * * php /path/to/morningnewsletter/cron/send_emails.php >> /path/to/morningnewsletter/logs/cron.log 2>&1
```

## Manual Testing

### Health Check
```bash
php cron/send_emails.php --health-check
```

### Dry Run (see what would be sent)
```bash
php cron/send_emails.php --dry-run
```

### Force Send to Specific User
```bash
php cron/send_emails.php --force-send USER_ID
```

### Normal Operation
```bash
php cron/send_emails.php
```

## Log Files

- Cron logs: `logs/cron.log`
- PHP error logs: `logs/error.log`
- Email delivery logs: Stored in database `email_logs` table

## Monitoring

Check the logs regularly to ensure emails are being sent successfully:

```bash
tail -f logs/cron.log
```

## Troubleshooting

1. **No emails being sent**: Check that users have verified emails and configured sources
2. **Permission errors**: Ensure the cron script is executable and logs directory is writable
3. **Database errors**: Check database connection and file permissions
4. **Email delivery failures**: Check SMTP configuration and email sender settings

## Time Zone Considerations

The cron job runs every 15 minutes and checks for users whose local send time falls within the current 15-minute window. This ensures accurate delivery regardless of user timezone.

Example:
- Cron runs at 12:00 UTC
- Checks for users with send times between 12:00-12:15 in their local timezone
- Converts user local times to UTC for comparison