# Student Management System (SMS)

A comprehensive web-based application for educational institutions to manage students, courses, attendance, fees, and more. This system streamlines administrative tasks and provides an integrated platform for student information management.

## Features

- **User Management**
  - Role-based access control (Admin, Teacher, Student, Staff)
  - Secure authentication and authorization
  - Profile management

- **Student Management**
  - Student registration and profile management
  - Student search and filtering
  - Complete student information tracking

- **Course Management**
  - Course creation and management
  - Student enrollment tracking
  - Grade recording and management

- **Attendance Tracking**
  - Daily attendance marking by course
  - Attendance reports and statistics
  - Absence tracking

- **Fee Management**
  - Fee payment recording
  - Payment history and receipts
  - Outstanding fee tracking

- **Leave Management**
  - Student leave application submission
  - Leave approval workflow
  - Leave history tracking

- **Event Management**
  - Event creation and scheduling
  - Student registration for events
  - Event reminders and notifications

- **Admission Services**
  - Online application submission
  - Application status tracking
  - Document upload and management

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 4
- **Server**: Apache/Nginx

## Quick Start with Docker

### Prerequisites
- [Docker](https://www.docker.com/products/docker-desktop) installed on your machine
- [Docker Compose](https://docs.docker.com/compose/install/) (usually included with Docker Desktop)

### Installation

1. **Clone the repository or download the files**

2. **Start the Docker environment**

   For Windows:
   ```
   start-docker.bat
   ```

   For Mac/Linux:
   ```bash
   chmod +x start-docker.sh
   ./start-docker.sh
   ```

   Or manually with Docker Compose:
   ```bash
   docker-compose up -d
   ```

3. **Access the application**
   - Main application: http://localhost:8080
   - phpMyAdmin: http://localhost:8081

4. **Login with default credentials**
   - Username: admin
   - Password: admin123

### Docker Services

The Docker environment includes:
- PHP 7.4 with Apache (Web Server)
- MySQL 5.7 (Database)
- phpMyAdmin (Database Management)

### Docker Commands

- Start containers: `docker-compose up -d`
- Stop containers: `docker-compose down`
- View logs: `docker-compose logs`
- Rebuild containers: `docker-compose build`
- Access web container shell: `docker exec -it sms_web bash`
- Access database container shell: `docker exec -it sms_db bash`

## Standard Installation

For installation without Docker, please refer to the [SETUP.md](SETUP.md) document for detailed installation instructions.

## Project Structure

```
/SMS
├── admin/             # Admin panel files
├── student/           # Student portal files
├── teacher/           # Teacher portal files
├── assets/            # Static assets (CSS, JS, images)
├── includes/          # Shared PHP includes
├── schema.sql         # Database schema
├── dbms.php           # Database connection class
├── login.php          # Login system
├── index.php          # Main dashboard
├── studentServices.php # Student management services
├── admisstonServices.php # Admission services
├── courseTracker.php   # Course management services
├── feeTracker.php      # Fee management services
├── leaveServices.php   # Leave management services
├── event.php           # Event management services
├── profile.php         # User profile management
├── setting.php         # System settings
├── README.md           # This documentation
├── SETUP.md            # Standard installation guide
├── Dockerfile          # Docker configuration
├── docker-compose.yml  # Docker Compose configuration
├── start-docker.sh     # Docker startup script for Mac/Linux
└── start-docker.bat    # Docker startup script for Windows
```

## Usage Examples

### Student Registration

```php
// Create a new student
$studentServices = new StudentServices();
$studentData = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '1234567890',
    'address' => '123 Main St',
    'date_of_birth' => '2000-01-15',
    'gender' => 'Male',
    'create_account' => true // Also create a user account
];
$result = $studentServices->createStudent($studentData);
```

### Course Enrollment

```php
// Enroll a student in a course
$studentServices = new StudentServices();
$result = $studentServices->enrollCourse(1, 101); // student_id, course_id
```

### Mark Attendance

```php
// Mark attendance for a student
$studentServices = new StudentServices();
$result = $studentServices->markAttendance(1, 101, '2023-11-15', 'present', 'On time');
```

### Record Fee Payment

```php
// Record a fee payment
$studentServices = new StudentServices();
$result = $studentServices->addFeePayment(1, 5000, 'Tuition', '2023-11-01', 'paid', 'Cash', 'Term 1 payment');
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please contact the system administrator or open an issue in the repository. 