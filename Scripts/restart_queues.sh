#!/bin/bash

# Load environment variables safely
export $(grep -v '^#' /var/www/AbsaApi/.env | tr '\n' '\0' | xargs -0)

# Convert comma-separated ACTIVE_SERVICE_LIST into an array
IFS=',' read -r -a queues <<< "$ACTIVE_SERVICE_LIST"

# Reload systemd to apply any changes to service files
sudo systemctl daemon-reload

# Loop through each queue and restart/start its systemd service
for queue in "${queues[@]}"; do
    service_name="$queue"

    # Check if the service is active
    systemctl is-active --quiet "$service_name"
    if [ $? -eq 0 ]; then
        echo "Restarting $service_name..."
        sudo systemctl restart "$service_name"
    else
        echo "Starting $service_name..."
        sudo systemctl start "$service_name"
    fi
done

echo "All queue workers processed."
