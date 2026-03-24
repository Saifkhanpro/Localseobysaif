# cPanel Deployment Guide

Follow these steps to deploy LocalSEO.pk to your cPanel hosting.

## 1. Prepare Your Codebase
You can either upload the `LocalSEO_Deployment.zip` file or zip the current directory contents (excluding `.git`, `node_modules`, `config.php`, etc.).

## 2. Upload Files to cPanel
1. Log in to your cPanel account.
2. Open **File Manager**.
3. Navigate to your target directory (usually `public_html` or a subdomain folder).
4. Click **Upload** and select your zip file.
5. Once uploaded, right-click the zip file and select **Extract**.

## 3. Create a MySQL Database
1. In cPanel, go to **MySQL® Databases**.
2. Create a new database (e.g., `user_localseo`).
3. Create a new database user and a strong password.
4. **Add the User to the Database** and grant **ALL PRIVILEGES**.

## 4. Run the Installer
1. Open your browser and navigate to `https://yourdomain.com/install.php`.
2. The system will perform a requirement check.
3. Enter your **Database Details** (Host, Name, User, Password).
4. Create your **Administrator Account**.
5. Click **START INSTALLATION**.

## 5. Post-Installation Cleanup
1. Once installation is complete, verify you can access the Admin Dashboard at `/admin/login`.
2. **CRITICAL:** Use File Manager to delete `install.php` to prevent unauthorized re-installation.

> [!WARNING]
> Ensure your PHP version is **8.1 or higher** for full compatibility.
