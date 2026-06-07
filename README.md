# ParcelPro – Web-based Parcel Management System

## Overview

ParcelPro is a web-based parcel delivery management system developed using PHP and PostgreSQL. The system allows users to create parcel delivery orders, complete payments, track deliveries, and provides dedicated interfaces for staff and administrators. This project was developed and deployed on a Debian-based virtual machine using Apache. 

Note: Public demo unavailable due to university VM access restrictions. Local setup instructions and test accounts are provided below. I can demonstrate the system locally during interview.

---

## Structure

- technical-work/ → full system source code and database backup
- documents/ → report source file, diagrams, and testing document
- ai-prompts/ → summary of AI usage

---

## System Requirements

- PHP (8.x recommended)
- PostgreSQL
- Apache (or compatible web server)
- Web browser

---

## Setup Instructions

1. Place the project files into a web server directory (e.g. `/var/www/html/`).
2. Configure database connection in:
   - `includes/db.php`

3. Import the database:

   ```
   psql -U <username> -d <database_name> -f parcel_system_backup.sql
   ```

4. Start the web server.
5. Access the system via browser:

   ```
   http://localhost/index.php
   ```

For demonstration purposes, it is recommended to use the provided test accounts and example postcodes listed below.

The database structure includes tables such as users, orders, payments, tracking_history, operating_areas, and sms_notifications.

---

## Access

The system is deployed on a university-hosted virtual machine and is accessible via:

https://debproj-ard38.teachvm.aber.ac.uk/index.php

Note:
Access is restricted to authorised users within the university network or via VPN. External public access is not available. Markers have been granted access to the deployed system via university VM permissions.

---

## Test Accounts

### Admin

- Email: admin@test.com
- Password: Admin2026!

### Staff

- Email: staff@test.com
- Password: Staff2026!

### Customer

- Email: user@test.com
- Password: User2026!

(Note: These accounts may be adjusted depending on database content.)

---

## Stripe Test Payment

Use the following card details for testing:

- Card Number: **4242 4242 4242 4242**
- Expiry: Any future date
- CVC: Any 3 digits

---

## Supported Postcodes

The system only accepts postcodes stored in the database. Example valid postcodes:

- SY23 1AA
- YO222QQ
- YO16 6NN
- YO13 3NN
- WW134LL

The system validates postcodes against the operating_areas table in the database. Only postcodes stored in this table are accepted. You can extend database using admin account - admindashboard -> operating areas section by adding a new postcode.

---

## SMS Notifications

The system integrates with Twilio for SMS notifications.

Note:

- Messages are logged in the database.
- Due to Twilio trial and regional restrictions, some messages may not be delivered, although all attempts are recorded in the database.

---

## Known Limitations

- SMS delivery restricted by Twilio trial account
- System accessible only via university VM / VPN
- No account deletion functionality (GDPR limitation)

---

## Notes

- All inputs are validated on client-side and server-side
- Role-based access control is implemented
- Session handling is used for multi-step processes
