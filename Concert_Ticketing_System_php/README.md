<img src="https://github.com/user-attachments/assets/8cc3904f-582e-4177-8205-e78ccea0500a" alt="Alt Text" width="180" height="180"></img>
# Concert Ticket System API Documentation (PHP Version)
## Introduction

The Concert Ticket System is a comprehensive PHP-based backend application that enables users to browse concerts, purchase tickets, and process payments via M-Pesa. This system is designed as a single-file PHP application that handles all aspects of ticket management and payment processing.

Built with modern PHP practices, this system provides a RESTful API that can be integrated with any frontend technology. It uses PDO for database connectivity, ensuring secure and efficient data handling. The M-Pesa integration makes it particularly suitable for the Kenyan market, but it can be adapted for other payment gateways.

Key features include:
- Concert management (add, retrieve)
- Ticket purchasing
- M-Pesa payment integration with STK Push
- Payment status tracking
- Ticket availability management

## Getting Started

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache or Nginx web server
- SSL certificate (for production)
- M-Pesa developer account (for Safaricom API access)

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/MutisoBrian/mpesa_concert_system_php.git
   cd mpesa_concert_system_php
   ```

2. Create a MySQL database:
   ```sql
   CREATE DATABASE concert_tickets;
   ```

3. Import the database schema (use the provided `database.sql` file):
   ```bash
   mysql -u username -p concert_tickets < database.sql
   ```

4. Configure the application:
   - Open `index.php` and update the database configuration:
     ```php
     $db_config = [
         'host' => 'localhost',
         'dbname' => 'concert_tickets',
         'username' => 'your_username',
         'password' => '',///no password required
         'charset' => 'utf8mb4'
     ];
     ```
   - Update the M-Pesa configuration with your Safaricom credentials:
     ```php
     $mpesa_config = [
         'base_url' => 'https://sandbox.safaricom.co.ke',
         'consumer_key' => 'your_consumer_key',
         'consumer_secret' => 'your_consumer_secret',
         // ... other settings
     ];
     ```

5. Deploy to your web server:
   - Copy the `index.php` file to your web server's document root or a subdirectory
   - Ensure the web server has write access to create or modify the database

6. Set up your web server:
   - For Apache, ensure mod_rewrite is enabled and use the following .htaccess file:
     ```
     RewriteEngine On
     RewriteCond %{REQUEST_FILENAME} !-f
     RewriteCond %{REQUEST_FILENAME} !-d
     RewriteRule ^(.*)$ index.php [QSA,L]
     ```
   - For Nginx, use the following configuration:
     ```
     location / {
         try_files $uri $uri/ /index.php?$query_string;
     }
     ```

The server will be accessible at your domain or localhost depending on your setup.

## Database Schema

The system uses three main tables:

### Concert
- `concertId`: Primary key (integer, auto-increment)
- `title`: concert title (varchar(255), required)
- `description`: concert description (text)
- `showTime`: Date and time of the concert (datetime, required)
- `price`: Ticket price (decimal(10,2), required)
- `max_tickets`: Maximum number of available tickets (integer, default: 100)
- `imageUrl`: URL to the concert poster image (varchar(255))
- `dateCreated`: Record creation timestamp (timestamp)
- `lastUpdated`: Record last updated timestamp (timestamp)

### Ticket
- `ticketId`: Primary key (integer, auto-increment)
- `concertId`: Foreign key to concert (integer, required)
- `customerName`: Name of the customer (varchar(255), required)
- `phoneNumber`: Customer phone number (varchar(20), required)
- `quantity`: Number of tickets purchased (integer, required)
- `totalAmount`: Total amount paid (decimal(10,2), required)
- `paymentStatus`: Status of payment (enum: 'Pending', 'Paid', 'Failed')
- `mpesaReceiptNumber`: M-Pesa receipt number (varchar(100))
- `transactionDate`: Date and time of the transaction (datetime)
- `dateCreated`: Record creation timestamp (timestamp)
- `lastUpdated`: Record last updated timestamp (timestamp)

### PushRequest
- `pushRequestId`: Primary key (integer, auto-increment)
- `ticketId`: Foreign key to Ticket (integer, required)
- `checkoutRequestId`: M-Pesa checkout request ID (varchar(255), required)
- `dateCreated`: Record creation timestamp (timestamp)
- `lastUpdated`: Record last updated timestamp (timestamp)

## API Endpoints

### Concert Management

#### Get All Concerts
- **URL**: `/api/concerts`
- **Method**: `GET`
- **Description**: Retrieve all concerts
- **Response**: JSON object containing an array of concerts
  ```json
  {
    "concert": [
      {
        "concertId": 1,
        "title": "Rock Festival",
        "description": "An amazing rock music festival featuring top bands...",
        "showTime": "2025-06-15 18:30:00",
        "price": 2000.0,
        "max_tickets": 200,
        "imageUrl": "http://localhost/Concert_Ticketing_System_php/images/rock.png",
        "dateCreated": "2025-05-21 12:00:00",
        "lastUpdated": "2025-05-21 12:00:00"
      },
      ...
    ]
  }
  ```

#### Get Concert by ID
- **URL**: `/api/concerts/{id}`
- **Method**: `GET`
- **Description**: Retrieve a specific concert by ID
- **URL Parameters**: `id` - ID of the concert to retrieve
- **Response**: JSON object containing the concert details
  ```json
   {
    "concert": [
      {
        "concertId": 1,
        "title": "Rock Festival",
        "description": "An amazing rock music festival featuring top bands...",
        "showTime": "2025-06-15 18:30:00",
        "price": 2000.0,
        "max_tickets": 200,
        "imageUrl": "http://localhost/Concert_Ticketing_System_php/images/rock.png",
        "dateCreated": "2025-05-21 12:00:00",
        "lastUpdated": "2025-05-21 12:00:00"
      },
      ...
    ]
  }
  ```

#### Add New Concert
- **URL**: `/api/concerts`
- **Method**: `POST`
- **Description**: Add a new concert
- **Request Body**:
  ```json
  {
    "title": "Jazz Night",
    "description": "A smooth night of jazz music with renowned perform...",
    "showTime": "2025-07-15 20:00:00",
    "price": 600,
    "max_tickets": 150,
    "imageUrl": "http://localhost/Concert_Ticketing_System_php/images/jazz.png"
  }
  ```
- **Required Fields**: `title`, `showTime`, `price`
- **Response**: JSON object containing the added concert details
  ```json
  {
    "message": "Concert added successfully",
    "concert": {
      "concertId": 2,
      "title": "Jazz Night",
      "description": "A smooth night of jazz music with renowned perform...",
      "showTime": "2025-07-15 20:00:00",
      "price": 600.0,
      "max_tickets": 150,
      "imageUrl": "http://localhost/Concert_Ticketing_System_php/images/jazz.png",
      "dateCreated": "2025-05-21 14:30:00",
      "lastUpdated": "2025-05-21 14:30:00"
    }
  }
  ```

### Ticket Management

#### Purchase Ticket (Get Available Concert)
- **URL**: `/api/purchase-ticket`
- **Method**: `GET`
- **Description**: Retrieve all concert for ticket purchase
- **Response**: Same as `GET /api/concerts`

#### Make Payment (Initiate M-Pesa STK Push)
- **URL**: `/api/make-payment`
- **Method**: `POST`
- **Description**: Initiate an M-Pesa payment for ticket purchase
- **Request Body**:
  ```json
  {
    "concertId": 1,
    "customerName": "John Doe",
    "phoneNumber": "254712345678",
    "quantity": 2
  }
  ```
- **Required Fields**: `concertId`, `customerName`, `phoneNumber`, `quantity`
- **Response**: JSON object containing payment initiation details
  ```json
  {
    "message": "Payment initiated successfully",
    "ticketId": 1,
    "checkoutRequestId": "ws_CO_DMZ_12345678901234567",
    "responseDescription": "Success. Request accepted for processing"
  }
  ```

#### Query Payment Status
- **URL**: `/api/query-payment-status`
- **Method**: `POST`
- **Description**: Query the status of an M-Pesa STK push transaction
- **Request Body**:
  ```json
  {
    "checkoutRequestId": "ws_CO_DMZ_12345678901234567"
  }
  ```
- **Required Fields**: `checkoutRequestId`
- **Response**: JSON object containing the payment status
  ```json
  {
    "ResponseCode": "0",
    "ResponseDescription": "The service request has been accepted successfully",
    "MerchantRequestID": "12345-67890-1",
    "CheckoutRequestID": "ws_CO_DMZ_12345678901234567",
    "ResultCode": "0",
    "ResultDesc": "The service request is processed successfully"
  }
  ```

#### M-Pesa Callback
- **URL**: `/api/mpesa-callback`
- **Method**: `POST`
- **Description**: Callback endpoint for M-Pesa payment notifications
- **Notes**: 
  - This endpoint is called by Safaricom's M-Pesa API
  - Should be exposed via a publicly accessible URL
  - Updates ticket status based on payment result

#### Get Ticket Details
- **URL**: `/api/tickets/{id}`
- **Method**: `GET`
- **Description**: Retrieve details of a specific ticket
- **URL Parameters**: `id` - ID of the ticket to retrieve
- **Response**: JSON object containing ticket details including concert information
  ```json
  {
    "ticket": {
      "ticketId": 1,
      "concertId": 1,
      "customerName": "John Doe",
      "phoneNumber": "254712345678",
      "quantity": 2,
      "totalAmount": 1000.0,
      "paymentStatus": "Paid",
      "mpesaReceiptNumber": "PBH234TYGD",
      "transactionDate": "2025-05-21 15:30:45",
      "dateCreated": "2025-05-21 15:25:10",
      "lastUpdated": "2025-05-21 15:30:50",
      "concert": {
        "concertId": 1,
        "title": "Rock Festival",
        "description": "An amazing rock music festival featuring top bands...",
        "showTime": "2025-06-15 18:30:00",
        "price": 500.0,
        "max_tickets": 200,
        "imageUrl": "http://localhost/Concert_Ticketing_System_php/images/rock.png"
      }
    }
  }
  ```

## Payment Flow

The system implements M-Pesa's STK Push functionality with the following flow:

1. **Ticket Creation**:
   - User selects a concert and provides details (name, phone number, ticket quantity)
   - System creates a ticket record with 'Pending' status

2. **Payment Initiation**:
   - System calculates the total amount
   - System formats the phone number for M-Pesa
   - System sends an STK Push request to Safaricom

3. **STK Push Request**:
   - System calls Safaricom's STK Push API
   - User receives a prompt on their phone to enter M-Pesa PIN

4. **Payment Processing**:
   - User enters PIN on their phone
   - Safaricom processes the payment

5. **Payment Callback**:
   - Safaricom sends a callback to the system
   - System updates the ticket status based on payment result

6. **Ticket Confirmation**:
   - System provides ticket details to the user if payment is successful

## Implementation Details

### Code Structure

The entire application is contained in a single PHP file (`index.php`) for simplicity, but follows a structured approach:

1. **Configuration Section**:
   - Database settings
   - M-Pesa API configuration

2. **Helper Functions**:
   - `getDbConnection()`: Establishes PDO database connection
   - `createDatabaseTables()`: Creates tables if they don't exist
   - `jsonResponse()`: Standardizes JSON responses
   - `getMpesaAccessToken()`: Gets M-Pesa OAuth token
   - `formatPhoneNumber()`: Formats phone numbers for M-Pesa

3. **Route Handlers**:
   - Functions for each endpoint (e.g., `handleGetAllconcerts()`)
   - Each function handles a specific API route

4. **Request Router**:
   - `handleRequest()`: Routes incoming requests to appropriate handlers

### Database Handling

The application uses PDO (PHP Data Objects) for database operations, providing:
- Prepared statements for SQL injection protection
- Error handling via exceptions
- Transaction support for data integrity

### M-Pesa Integration

The system integrates with M-Pesa through:
1. OAuth token authentication
2. STK Push API for payment initiation
3. STK Query API for payment status checking
4. Callback handling for payment notifications

### Error Handling

The application implements comprehensive error handling:
- Try-catch blocks around all database operations
- HTTP status codes for different error types
- Consistent error response format
- Transaction rollback on failures

## Security Considerations

For production deployment, consider implementing:

1. **Input Validation**: The application validates required fields, but additional validation could be added
2. **Authentication**: Implement JWT or API key authentication for admin endpoints
3. **HTTPS**: Ensure all endpoints are served over HTTPS
4. **Environment Variables**: Move sensitive configuration to environment variables
5. **Rate Limiting**: Implement rate limiting to prevent abuse
6. **Logging**: Add detailed logging for security auditing

## Performance Optimization

For high-traffic implementations, consider:

1. **Database Indexing**: Add indices to frequently queried columns
2. **Caching**: Implement Redis or Memcached for frequently accessed data
3. **Connection Pooling**: Configure database connection pooling
4. **Load Balancing**: Distribute traffic across multiple servers
5. **CDN**: Use a CDN for serving concert images

## Extending the System

The system can be extended in several ways:

1. **Additional Payment Methods**: Add support for other payment gateways
2. **User Authentication**: Implement user accounts and login
3. **Admin Dashboard**: Create an admin interface for concert management
4. **Email Notifications**: Send email confirmations for tickets
5. **Analytics**: Add reporting and analytics features
6. **API Versioning**: Implement versioning for API endpoints

## Troubleshooting

### Common Issues

1. **Database Connection Errors**:
   - Check database credentials
   - Verify MySQL is running
   - Ensure the database exists

2. **M-Pesa API Issues**:
   - Validate M-Pesa credentials
   - Check that your callback URL is publicly accessible
   - Verify phone number format

3. **Missing Routes**:
   - Ensure mod_rewrite is enabled (Apache)
   - Check URL rewriting configuration
   - Verify .htaccess file is present

### Debugging

For debugging issues:

1. Enable error display in development (already enabled in the code)
2. Check PHP and web server error logs
3. Add logging statements to track execution flow
4. Use tools like Postman to test API endpoints

## Conclusion

This PHP implementation of the concert Ticket System provides a robust backend for concert ticket sales with M-Pesa integration. The single-file architecture makes it easy to deploy while maintaining a structured approach through well-organized functions. By following this documentation, you can set up, customize, and extend the system to meet your specific requirements.

For further assistance or to report issues, please contact Barasa Michael Murunga: 
brian.omoro@strathmore.edu
