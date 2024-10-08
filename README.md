# WordPress Sync Pterodactyl

This extension for WordPress automatically syncs users who register on your WordPress site with the Pterodactyl panel, ensuring seamless account creation and synchronization.

## Installation Guide

1. **Upload the Plugin**  
   Connect to your WordPress instance and upload the `pterodactyl-wordpress` folder to `wp-content/plugins/`.

2. **Activate the Plugin**  
   Navigate to your WordPress Dashboard, go to the **Plugins** section, and activate the "Pterodactyl Integration" plugin.

3. **Configure the Plugin**  
   After activation, a new menu item **"Pterodactyl Settings"** will appear in your WordPress Dashboard.

4. **Enter the Pterodactyl API Key**  
   - Create a new **Application API Key** in your Pterodactyl panel with the following permissions:
     - `Read/Write` for **Users**
     - Set all other permissions to `None`.
   - Enter the generated API Key into the **API Key** field in the **Pterodactyl Settings** page.

5. **Set the Pterodactyl Panel URL**  
   Enter your Pterodactyl panel URL in the **Panel URL** field using the format:  https://panel-url.com

## Optional Configuration

By default, WordPress does not include "First Name" and "Last Name" fields on the registration page. If you want to include these fields and synchronize them with Pterodactyl, enable this option in the **Pterodactyl Settings** page.

## How It Works

Whenever a new user registers on your WordPress site, the plugin will automatically create a corresponding account in the Pterodactyl panel using the same credentials.

## Troubleshooting

If user creation fails:

1. **Check API Key Permissions**: Ensure it has `Read/Write` permissions for Users.
2. **Verify Panel URL**: Confirm that the panel URL is correctly formatted.
3. **Review WordPress Logs**: Check `debug.log` in WordPress for plugin-related errors.
