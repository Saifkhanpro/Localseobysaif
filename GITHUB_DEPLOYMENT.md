# GitHub Deployment Guide

This guide describes how to push your LocalSEO.pk codebase to a new GitHub repository.

## 1. Initialize Git Repository
Open your terminal in the project root (`d:\Apeak out put\LocalSEO`) and run:
```bash
git init
```

## 2. Check `.gitignore`
Ensure you have a `.gitignore` file to prevent sensitive data from being pushed. It should at least include:
```text
config.php
cache/
uploads/
backups/
temp/
.vscode/
.DS_Store
```

## 3. Add and Commit Files
```bash
git add .
git commit -m "Initial commit: LocalSEO.pk CMS v1.0.0"
```

## 4. Create Repository on GitHub
1. Go to [github.com/new](https://github.com/new).
2. Name your repository (e.g., `localseo-pk`).
3. Set it to **Private** or **Public**.
4. **Do NOT** initialize with a README, license, or .gitignore.

## 5. Push to GitHub
Copy the commands from the GitHub instruction page:
```bash
git remote add origin https://github.com/YOUR_USERNAME/localseo-pk.git
git branch -M main
git push -u origin main
```

> [!IMPORTANT]
> Since `config.php` is ignored, you will need to run the `install.php` script on your server or manually recreate the configuration in the new environment.
