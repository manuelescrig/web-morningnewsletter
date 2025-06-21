#!/bin/bash

# MorningNewsletter Cron Job Setup Script
# This script helps you set up the cron job for automated newsletter sending

echo "🚀 MorningNewsletter Cron Job Setup"
echo "=================================="

# Get the current directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
CRON_SCRIPT="$SCRIPT_DIR/cron/send_emails.php"
LOGS_DIR="$SCRIPT_DIR/logs"

echo "📁 Project directory: $SCRIPT_DIR"
echo "📄 Cron script: $CRON_SCRIPT"

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "❌ Error: PHP is not installed or not in PATH"
    echo "   Please install PHP first"
    exit 1
fi

echo "✅ PHP found: $(php --version | head -n1)"

# Check if the cron script exists
if [ ! -f "$CRON_SCRIPT" ]; then
    echo "❌ Error: Cron script not found at $CRON_SCRIPT"
    exit 1
fi

echo "✅ Cron script found"

# Create logs directory if it doesn't exist
if [ ! -d "$LOGS_DIR" ]; then
    echo "📁 Creating logs directory..."
    mkdir -p "$LOGS_DIR"
fi

# Make sure the cron script is executable
chmod +x "$CRON_SCRIPT"
echo "✅ Made cron script executable"

# Test the cron script
echo ""
echo "🧪 Testing cron script..."
echo "Running health check..."

if php "$CRON_SCRIPT" --health-check; then
    echo "✅ Health check passed!"
else
    echo "❌ Health check failed!"
    echo "   Please fix any issues before setting up the cron job"
    exit 1
fi

echo ""
echo "📋 Cron Job Configuration"
echo "========================"

# Generate the cron line
CRON_LINE="*/15 * * * * php $CRON_SCRIPT >> $LOGS_DIR/cron.log 2>&1"

echo "Add this line to your crontab:"
echo ""
echo "$CRON_LINE"
echo ""

# Offer to add it automatically
read -p "Would you like me to add this to your crontab automatically? (y/n): " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    # Check if crontab exists
    if crontab -l &> /dev/null; then
        # Backup existing crontab
        echo "📋 Backing up existing crontab..."
        crontab -l > "$SCRIPT_DIR/crontab.backup.$(date +%Y%m%d_%H%M%S)"
        
        # Check if our cron job is already there
        if crontab -l | grep -q "send_emails.php"; then
            echo "⚠️  MorningNewsletter cron job already exists in crontab"
            echo "   Skipping automatic addition"
        else
            # Add to existing crontab
            (crontab -l; echo "$CRON_LINE") | crontab -
            echo "✅ Added cron job to existing crontab"
        fi
    else
        # Create new crontab
        echo "$CRON_LINE" | crontab -
        echo "✅ Created new crontab with MorningNewsletter job"
    fi
    
    echo ""
    echo "📋 Current crontab:"
    crontab -l | grep -E "(send_emails|#)"
    
else
    echo ""
    echo "📋 Manual Setup Instructions:"
    echo "1. Open crontab editor: crontab -e"
    echo "2. Add the line shown above"
    echo "3. Save and exit"
fi

echo ""
echo "🔍 Next Steps"
echo "============"
echo "1. Monitor logs: tail -f $LOGS_DIR/cron.log"
echo "2. Test manually: php $CRON_SCRIPT --dry-run"
echo "3. Check email delivery in the database email_logs table"
echo ""
echo "📝 Useful Commands:"
echo "   View cron jobs:     crontab -l"
echo "   Edit cron jobs:     crontab -e"
echo "   Remove cron jobs:   crontab -r"
echo "   Test health:        php $CRON_SCRIPT --health-check"
echo "   Dry run:            php $CRON_SCRIPT --dry-run"
echo "   Force send:         php $CRON_SCRIPT --force-send USER_ID"
echo ""
echo "🎉 Setup complete!"