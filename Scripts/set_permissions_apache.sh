#!/bin/bash

# Define the web server user and the application path
WEB_SERVER_USER="apache"
APACHE_USER="apache"
ENV_PATH="/var/www/AbsaApi/.env"
APP_PATH="/var/www/AbsaApi/current" # The main active app path
STORAGE_PATH="/var/www/AbsaApi/storage" # The Storage Path
CACHE_PATH="${APP_PATH}/bootstrap/cache" # The Cache Path

# Function to check if the last command succeeded
check_last_command() {
    if [ $? -eq 0 ]; then
        echo "Success"
    else
        echo "Failed"
    fi
}

# Set ownership for the application path
echo "Setting ownership to ${WEB_SERVER_USER}:${APACHE_USER} for ${APP_PATH}"
sudo chown -R ${WEB_SERVER_USER}:${APACHE_USER} ${APP_PATH}
check_last_command

# Set ownership for the storage path
echo "Setting ownership to ${WEB_SERVER_USER}:${APACHE_USER} for ${STORAGE_PATH}"
sudo chown -R ${WEB_SERVER_USER}:${APACHE_USER} ${STORAGE_PATH}
check_last_command

# Set ownership for the .env
echo "Setting ownership to ${WEB_SERVER_USER}:${APACHE_USER} for ${ENV_PATH}"
sudo chown ${WEB_SERVER_USER}:${APACHE_USER} ${ENV_PATH}
check_last_command

# Set directory permissions for the application path
echo "Setting directory permissions to 755 for ${APP_PATH}"
sudo find ${APP_PATH} -type d -exec chmod 755 {} \;
check_last_command

# Set file permissions for the application path
echo "Setting file permissions to 644 for ${APP_PATH}"
sudo find ${APP_PATH} -type f -exec chmod 644 {} \;
check_last_command

# Set special permissions for storage
echo "Setting special directory permissions for storage"
sudo chmod -R 775 ${STORAGE_PATH}
check_last_command
sudo chmod g+s ${STORAGE_PATH}
check_last_command

# Set special permissions for bootstrap/cache
echo "Setting special directory permissions for bootstrap/cache"
sudo chmod -R 775 ${CACHE_PATH}
check_last_command
sudo chmod g+s ${CACHE_PATH}
check_last_command

echo "Permissions set successfully."
