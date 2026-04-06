# Real Estate Pictures (Listings Manager)

## Overview
This project is a bespoke web-based Real Estate Listing Management application. Built with modern, secure PHP using PDO, it enables users to seamlessly manage property portfolios including their respective addresses, media assets (like images, video links, etc.), and other essential details. The user interface leverages TailwindCSS to provide a responsive, elegant, and modern experience out of the box.

<img width="1908" height="990" alt="Screenshot from 2026-04-06 16-35-03" src="https://github.com/user-attachments/assets/0de1c852-4251-4fdc-8272-3cf8a90acaf1" />


### Key Features
*   **Secure Authentication:** Features robust login security with session fixation prevention, hashed passwords, and brute-force protection which temporarily locks accounts after too many failed login attempts.
*   **User Management & Roles:** Supports administrative functionality alongside standard user capabilities, ensuring distinct access levels
*   **Listings Management:** Full CRUD (Create, Read, Update, Delete) capability for real property datasets, mapping out addresses, postal codes, cities, and video tour links.
  <img width="1908" height="990" alt="Screenshot from 2026-04-06 16-35-30" src="https://github.com/user-attachments/assets/c60af41e-da91-48a1-95d1-e44ce8ac8dd1" />
  <img width="1908" height="990" alt="Screenshot from 2026-04-06 16-48-48" src="https://github.com/user-attachments/assets/d288a62a-658c-4423-9fb5-86e99b78f0e7" />
  <img width="1908" height="990" alt="Screenshot from 2026-04-06 16-35-42" src="https://github.com/user-attachments/assets/7769c752-0968-40f3-91e1-796e8b91e79e" />

*   **Media Support:** Each property listing can feature an ordered gallery of high-quality uploaded images.
*   **Dashboard & Search:** A centralized, accessible dashboard featuring quick search functionality and pagination to manage large portfolios efficiently.
*   **Data Security:** Extensive use of CSRF tokens to restrict cross-site request forgery attacks across all data-mutating endpoints, and PDO prepared statements to reliably block SQL injection.

---
<img width="1908" height="990" alt="Screenshot from 2026-04-06 16-34-46" src="https://github.com/user-attachments/assets/1872e2bd-cc94-4c1a-b816-81cd4ea3caa5" />


## Installation Tutorial

Setting up the project on your live or local environment is designed to be frictionless, thanks to an integrated one-time setup wizard. 

### Prerequisites
*   A web server (Apache/Nginx) running PHP 7.4+ or 8.x.
*   A MySQL or MariaDB server.
*   Server permissions correctly configured so PHP can write to the directory **one level above** your public web root (for safely storing database credentials).

### Step-by-Step Installation

1.  **Deploy Files to Web Server**
    *   Upload or clone all project files into your desired web-accessible directory (e.g., `public_html/listings` or `/var/www/html/2026`).

2.  **Create an Empty Database**
    *   Log in to your MySQL/cPanel account and create a bare, empty database.
    *   Make a note of the Database Name, Database Username, and Database Password.

3.  **Run the Installation Wizard**
    *   Open your web browser and navigate directly to the installer file located at `yourdomain.com/path-to-folder/install.php` (e.g., `localhost/2026/install.php`).
    *   You will see an Installation Wizard UI.

4.  **Fill in Credentials**
    *   **Database Config:** Enter the database credentials you obtained from Step 2.
    *   **Admin Account:** Detail the username, email, and strong password for the initial administrative account. 

5.  **Complete Installation**
    *   Click **"Install Now →"**.
    *   The wizard will automatically connect to your database, generate the required schemas (`users`, `listings`, `listing_images`), register your admin user, and securely output your `db_config.php` file to a non-public folder (one level above your web root).
    *   *Security feature:* Upon successful completion, `install.php` automatically self-deletes to prevent any malicious actors from restarting the installation or overriding your database

6.  **Log In!**
    *   You will be redirected automatically to `login.php`. Log in with your new admin account details and start building your listings!
  
<img width="1908" height="990" alt="Screenshot from 2026-04-06 16-29-13" src="https://github.com/user-attachments/assets/eea4bc69-39e3-44b1-b6b6-55bde495243a" />

<img width="1908" height="990" alt="Screenshot from 2026-04-06 16-37-16" src="https://github.com/user-attachments/assets/d53dfbcb-9c2b-47e1-80e5-8c25e55df1c0" />

<img width="1908" height="990" alt="Screenshot from 2026-04-06 16-37-21" src="https://github.com/user-attachments/assets/e6642fa5-1c8c-43e4-b224-165334f8bc91" />

<img width="1908" height="990" alt="Screenshot from 2026-04-06 16-37-10" src="https://github.com/user-attachments/assets/a0d22883-5c2e-4aea-9fe4-4eeaa1f97a91" />

      
