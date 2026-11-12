#!/bin/bash

# Define the web server user and the application path
WEB_SERVER_USER="ec2-user"
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
sudo chown -R ${WEB_SERVER_USER}:${APACHE_USER} ${STORAGE_PATH}/
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

# Set special directory permissions for storage WITH SGID bit (2775)
echo "Setting special directory permissions to 2775 for storage"
sudo find ${STORAGE_PATH} -type d -exec chmod 2775 {} \;
check_last_command

# Set file permissions to 664 for storage so both users can write
echo "Setting file permissions to 664 for storage files"
sudo find ${STORAGE_PATH} -type f -exec chmod 664 {} \;
check_last_command

# Set special directory permissions for bootstrap/cache WITH SGID bit (2775)
echo "Setting special directory permissions to 775 for bootstrap/cache"
sudo find ${CACHE_PATH} -type d -exec chmod 2775 {} \;
check_last_command

# Set special file permissions for bootstrap/cache
echo "Setting special file permissions to 664 for bootstrap/cache"
sudo find ${CACHE_PATH} -type f -exec chmod 664 {} \;
check_last_command

# ... (your existing chown and chmod blocks) ...

# Ensure SELinux allows web server write access to storage and cache
if command -v restorecon &> /dev/null; then
    echo "Applying SELinux contexts for Amazon Linux..."
    sudo semanage fcontext -a -t httpd_sys_rw_content_t "${STORAGE_PATH}(/.*)?" 2>/dev/null || true
    sudo semanage fcontext -a -t httpd_sys_rw_content_t "${CACHE_PATH}(/.*)?" 2>/dev/null || true
    sudo restorecon -Rv ${STORAGE_PATH} ${CACHE_PATH}
    check_last_command
fi

echo "Permissions set successfully."
