# EcoPay Project Documentation

## Project Overview

EcoPay is a digital payment platform designed to facilitate various financial transactions. The project is structured into two main parts: the user interface (frontend) and the backend API.

## Directory Structure

- **EcoPay_user/**: This directory contains the frontend components of the EcoPay platform.
    - **assets/**:  Contains static assets such as images and fonts.
    - **css/**: Contains CSS stylesheets for styling the user interface.
    - **dashboard/**: Files related to the user dashboard.
    - **deposit/**: Files related to deposit functionality.
    - **history/**: Files related to transaction history.
    - **login/**: Files related to user login.
    - **p2p/**: Files related to peer-to-peer transfer functionality.
    - **popup/**: Files for popup elements.
    - **Profile/**: Files related to user profile management.
    - **qr/**: Files related to QR code functionality.
    - **register/**: Files related to user registration.
    - **withdraw/**: Files related to withdrawal functionality.
    - **axios.min.js, html5-qrcode.min.js, qrcode.min.js, universal.js, navbar.html, icon.png**:  Various JavaScript libraries, universal scripts, navbar component, and project icon.

- **EcoPay_backend/V2/**: This directory contains the backend API for the EcoPay platform, built using PHP.
    - **models/**: Contains model classes representing database entities.
    - **.php files (e.g., Admin.php, admin_login.php, deposit.php)**:  PHP API endpoints for various functionalities such as user authentication, wallet management, transactions, and admin operations.
    - **config.php**: Configuration file for database credentials, email settings, and API base URL.
    - **db_connection.php**:  File for establishing a database connection.
- **database_schema.sql**: SQL schema for the EcoPay database.
## ER Diagrame :

![ER_diagram](https://github.com/user-attachments/assets/4baad7e4-7316-4c9d-a28c-d43420b54aea)

## Components Diagrame :
![Component_diagram](https://github.com/user-attachments/assets/ad17600b-b79c-4d88-a640-b709ae4c4949)

## Hosting Details :
    - **IP address**: 52.47.95.15
    - **DNS**: http://ec2-52-47-95-15.eu-west-3.compute.amazonaws.com/
    - **URL**: http://52.47.95.15/EcoPay_user/login/login.html
    

## ER Diagrame :

![ER_diagram](https://github.com/user-attachments/assets/4baad7e4-7316-4c9d-a28c-d43420b54aea)

## Components Diagrame :
![Component_diagram](https://github.com/user-attachments/assets/ad17600b-b79c-4d88-a640-b709ae4c4949)

## Hosting Details :
    - **IP address**: 52.47.95.15
    - **DNS**: http://ec2-52-47-95-15.eu-west-3.compute.amazonaws.com/
    - **URL**: http://52.47.95.15/EcoPay_user/login/login.html
    


## Key API Endpoints

The following are some of the key API endpoints available in `EcoPay_backend/V2/`:

-   `/login.php`: Handles user login.
-   `/register.php`: Handles user registration.
-   `/deposit.php`: Handles deposit functionality.
-   `/withdraw.php`: Handles withdrawal functionality.
-   `/p2p_transfer.php`: Handles peer-to-peer transfers.
-   `/get_wallets.php`: Retrieves user wallets.
-   `/profile.php`: Handles user profile management.
-   `/create_qr_code.php`: Creates QR codes for transactions.

This documentation provides a high-level overview of the project structure. For detailed information about the API endpoints in `EcoPay_backend/V2/`, please refer to the `API.txt` file.
