# CHAPTER III: TECHNICAL BACKGROUND

## 3.1 Technicality of the Project

### 3.1.1 System Overview

The Smart Scheduling System is a web-based application designed to automate and optimize the academic scheduling process for educational institutions. The system employs a three-tier architecture consisting of:

1. **Presentation Layer**: Handles user interface and user interactions through responsive web pages
2. **Business Logic Layer**: Processes scheduling algorithms, conflict detection, and business rules
3. **Data Access Layer**: Manages database operations and data persistence

### 3.1.2 System Architecture

The application follows the **Model-View-Controller (MVC)** architectural pattern, which provides:

- **Separation of Concerns**: Clear division between data handling, business logic, and presentation
- **Maintainability**: Easier code management and updates
- **Scalability**: Ability to grow and adapt to increasing demands
- **Testability**: Simplified unit and integration testing

```
┌─────────────────────────────────────────────────────┐
│                  Presentation Layer                 │
│              (Twig Templates + CSS)                 │
└────────────────────┬────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────┐
│                 Application Layer                   │
│           (Symfony Controllers + Forms)             │
└────────────────────┬────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────┐
│                  Business Layer                     │
│        (Services + Event Subscribers)               │
└────────────────────┬────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────┐
│                   Data Layer                        │
│       (Doctrine ORM + Repositories)                 │
└────────────────────┬────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────┐
│                    Database                         │
│              (PostgreSQL/MySQL)                     │
└─────────────────────────────────────────────────────┘
```

### 3.1.3 Core Features

#### Schedule Management
- **Automated Schedule Creation**: Generate class schedules based on constraints and preferences
- **Conflict Detection**: Real-time identification of scheduling conflicts (room, faculty, time)
- **Multi-Section Support**: Handle multiple sections for the same subject
- **Status Tracking**: Monitor schedule approval workflow (pending, approved, rejected)

#### Resource Management
- **Room Allocation**: Optimize room utilization based on capacity and type
- **Faculty Assignment**: Assign instructors to classes with load balancing
- **Department Organization**: Manage schedules across different departments and colleges

#### User Role Management
- **Role-Based Access Control (RBAC)**:
  - **Administrator**: Full system access and configuration
  - **Department Head**: Department-specific schedule management
  - **Faculty**: View assigned schedules and availability

#### Conflict Resolution
The system implements intelligent conflict detection for:
- **Room Conflicts**: Same room assigned to multiple classes at overlapping times
- **Faculty Conflicts**: Same instructor scheduled for multiple classes simultaneously
- **Capacity Violations**: Enrolled students exceeding room capacity
- **Time Block Conflicts**: Overlapping time periods for the same resources

---

## 3.2 Technologies Used

### 3.2.1 Backend Technologies

#### Symfony 7.x Framework
**Purpose**: Primary PHP framework for backend development

**Key Features**:
- **Robust MVC Architecture**: Organized code structure
- **Dependency Injection**: Loose coupling and testability
- **Event Dispatcher**: Decoupled component communication
- **Security Component**: Built-in authentication and authorization
- **Form Component**: Simplified form handling and validation

**Justification**: Symfony provides enterprise-grade features, extensive documentation, and long-term support, making it ideal for complex academic scheduling systems.

#### Doctrine ORM (Object-Relational Mapping)
**Purpose**: Database abstraction and object-relational mapping

**Key Features**:
- **Entity Mapping**: PHP objects mapped to database tables
- **Query Builder**: Type-safe database queries
- **Migration System**: Version-controlled database schema changes
- **Repository Pattern**: Centralized data access logic

**Justification**: Doctrine simplifies database operations and ensures data integrity through its powerful ORM capabilities.

#### PHP 8.2+
**Purpose**: Server-side programming language

**Key Features Used**:
- **Type Declarations**: Enhanced code reliability
- **Attributes**: Metadata for routing and validation
- **Named Arguments**: Improved code readability
- **Constructor Property Promotion**: Cleaner entity definitions

### 3.2.2 Frontend Technologies

#### Twig Template Engine
**Purpose**: Server-side template rendering

**Key Features**:
- **Template Inheritance**: Reusable layout structures
- **Filters and Functions**: Data manipulation in views
- **Auto-escaping**: Protection against XSS attacks
- **Template Caching**: Improved performance

**Justification**: Twig provides a secure, flexible, and designer-friendly templating system.

#### Tailwind CSS
**Purpose**: Utility-first CSS framework for styling

**Key Features**:
- **Responsive Design**: Mobile-first approach
- **Custom Components**: Reusable UI elements
- **Color Schemes**: Consistent visual design
- **Hover Effects**: Enhanced user interactions

**Justification**: Tailwind enables rapid UI development with consistent design patterns and minimal custom CSS.

#### JavaScript (Vanilla)
**Purpose**: Client-side interactivity

**Key Features Used**:
- **DOM Manipulation**: Dynamic content updates
- **Event Handling**: User interaction management
- **Modal Controls**: Popup dialogs and overlays
- **Filter Functions**: Real-time schedule filtering

### 3.2.3 Database Technology

#### PostgreSQL/MySQL
**Purpose**: Relational database management system

**Key Features**:
- **ACID Compliance**: Data integrity and consistency
- **Complex Queries**: Advanced join operations and aggregations
- **Indexing**: Optimized query performance
- **Foreign Keys**: Referential integrity constraints

**Database Schema** includes:
- **Schedules**: Core scheduling data
- **Subjects**: Course information
- **Rooms**: Facility details
- **Faculty**: Instructor information
- **Departments**: Organizational structure
- **Academic Years**: Term management

### 3.2.4 Development Tools

#### Composer
**Purpose**: PHP dependency management

**Benefits**:
- Autoloading of classes
- Version management
- Third-party package integration

#### Symfony Asset Mapper
**Purpose**: Asset management without Node.js bundlers

**Benefits**:
- Simplified asset pipeline
- Native importmap support
- Reduced build complexity

#### Railway (Nixpacks)
**Purpose**: Cloud deployment platform

**Benefits**:
- Automatic build detection
- Simple deployment process
- Environment management
- Scalable infrastructure

---

## 3.3 How the Project Works

### 3.3.1 System Workflow

#### 1. User Authentication Flow
```
User Login → Symfony Security → Authentication Provider
    ↓
Role Assignment (Admin/Dept Head/Faculty)
    ↓
Dashboard Redirect based on Role
```

#### 2. Schedule Creation Workflow
```
1. Admin/Dept Head selects Department
    ↓
2. Fills Schedule Form:
   - Subject Selection
   - Section Assignment
   - Room Selection
   - Time Slot Definition
   - Faculty Assignment
   - Day Pattern Selection
    ↓
3. System Validation:
   - Input validation
   - Business rule checking
    ↓
4. Conflict Detection Service:
   - Room conflict check
   - Faculty conflict check
   - Capacity validation
   - Time overlap detection
    ↓
5. Database Persistence (if no conflicts)
    ↓
6. Status Update → Notification
```

#### 3. Conflict Detection Process
```
New Schedule Submission
    ↓
Query Existing Schedules with:
   - Same Room + Overlapping Time
   - Same Faculty + Overlapping Time
   - Same Day Pattern
    ↓
Conflict Analysis:
   ├─ Room Conflict? → Flag Schedule
   ├─ Faculty Conflict? → Flag Schedule
   ├─ Capacity Exceeded? → Flag Schedule
   └─ Time Block Conflict? → Flag Schedule
    ↓
Return Conflict Report with Details
```

### 3.3.2 Data Flow Diagram

```
┌──────────┐         ┌──────────────┐         ┌──────────┐
│  User    │────────>│  Controller  │────────>│ Service  │
│ Request  │         │  (Routing)   │         │  Layer   │
└──────────┘         └──────────────┘         └────┬─────┘
                                                    │
                                                    ▼
                                              ┌──────────┐
                                              │Repository│
                                              │  Layer   │
                                              └────┬─────┘
                                                   │
                                                   ▼
                                              ┌──────────┐
                                              │ Database │
                                              │          │
                                              └────┬─────┘
                                                   │
     ┌─────────────────────────────────────────────┘
     │
     ▼
┌──────────┐         ┌──────────────┐         ┌──────────┐
│   View   │<────────│   Twig       │<────────│  Data    │
│ (HTML)   │         │  Template    │         │ Transform│
└──────────┘         └──────────────┘         └──────────┘
```

### 3.3.3 Key Algorithms

#### Conflict Detection Algorithm
```
Function detectScheduleConflicts(newSchedule):
    conflicts = []
    
    // Get overlapping schedules
    existingSchedules = repository.findByTimeAndDay(
        newSchedule.startTime,
        newSchedule.endTime,
        newSchedule.dayPattern
    )
    
    For each schedule in existingSchedules:
        // Room conflict
        If schedule.room == newSchedule.room:
            conflicts.add({
                type: "ROOM_CONFLICT",
                message: "Room already occupied"
            })
        
        // Faculty conflict
        If schedule.faculty == newSchedule.faculty:
            conflicts.add({
                type: "FACULTY_CONFLICT",
                message: "Faculty double-booked"
            })
    
    // Capacity check
    If newSchedule.enrolledStudents > newSchedule.room.capacity:
        conflicts.add({
            type: "CAPACITY_EXCEEDED",
            message: "Room capacity exceeded"
        })
    
    Return conflicts
```

### 3.3.4 Request-Response Cycle

**Example: Creating a New Schedule**

1. **User Action**: Admin clicks "Create Schedule" button
2. **HTTP Request**: GET /admin/schedule/new?department={id}
3. **Controller**: ScheduleController::new()
   - Validates user permissions
   - Loads department data
   - Prepares form
4. **View Rendering**: Twig renders form template
5. **User Submission**: User fills form and submits
6. **HTTP Request**: POST /admin/schedule/new
7. **Controller**: ScheduleController::new() (POST handler)
   - Validates form data
   - Calls conflict detection service
8. **Service Layer**: ScheduleConflictService::detectConflicts()
   - Queries database for conflicts
   - Returns conflict analysis
9. **Decision**:
   - If conflicts: Return to form with error messages
   - If no conflicts: Persist to database
10. **Database**: INSERT schedule record
11. **Response**: Redirect to schedule index with success message
12. **Flash Message**: "Schedule created successfully"

---

## 3.4 Theoretical/Conceptual Framework

### 3.4.1 Course Scheduling Problem (CSP)

The academic scheduling system is based on the **Course Scheduling Problem**, a well-known constraint satisfaction problem in computer science.

**Definition**: The CSP involves assigning courses to time slots and rooms while satisfying various constraints.

**Hard Constraints** (Must be satisfied):
- No room can host two classes simultaneously
- No instructor can teach two classes at the same time
- Room capacity must not be exceeded
- Each section must be scheduled within available time blocks

**Soft Constraints** (Preferred but not mandatory):
- Minimize gaps in instructor schedules
- Balance room utilization
- Consider instructor preferences
- Optimize building transitions

### 3.4.2 Model-View-Controller (MVC) Pattern

**Concept**: Separation of application logic into three interconnected components.

**Model**:
- Represents data and business logic
- Implements data validation rules
- Handles database interactions
- Independent of user interface

**View**:
- Presents data to users
- Handles user interface rendering
- No business logic
- Receives data from controllers

**Controller**:
- Handles user requests
- Coordinates between Model and View
- Processes input and output
- Manages application flow

**Application in Project**:
```
User clicks "Create Schedule"
    ↓
Controller receives request
    ↓
Controller validates input
    ↓
Controller calls Model (Service/Repository)
    ↓
Model performs business logic and database operations
    ↓
Model returns data to Controller
    ↓
Controller passes data to View
    ↓
View renders response (Twig template)
    ↓
HTML sent to browser
```

### 3.4.3 Repository Pattern

**Concept**: Abstraction layer between business logic and data access.

**Benefits**:
- **Centralized Data Access**: Single point for database queries
- **Testability**: Easy to mock for unit tests
- **Maintainability**: Database changes isolated from business logic
- **Query Reusability**: Common queries defined once

**Implementation in Project**:
```php
// Repository provides clean interface
$schedules = $scheduleRepository->findByDepartment($department);
$conflicts = $scheduleRepository->findConflictingSchedules($time, $room);

// Business logic doesn't know about database details
```

### 3.4.4 Dependency Injection Pattern

**Concept**: Objects receive dependencies from external sources rather than creating them.

**Benefits**:
- **Loose Coupling**: Classes don't depend on concrete implementations
- **Testability**: Easy to inject mock objects
- **Flexibility**: Change implementations without modifying code
- **Configuration Management**: Dependencies managed centrally

**Application in Project**:
```php
// Service receives dependencies via constructor
class ScheduleService {
    public function __construct(
        private ScheduleRepository $repository,
        private ConflictDetector $conflictDetector,
        private EntityManagerInterface $entityManager
    ) {}
}

// Symfony's container automatically injects dependencies
```

### 3.4.5 Event-Driven Architecture

**Concept**: Components communicate through events rather than direct calls.

**Benefits**:
- **Decoupling**: Components don't need to know about each other
- **Extensibility**: Easy to add new event listeners
- **Flexibility**: Modify behavior without changing core code

**Application in Project**:
```
Schedule Created Event
    ↓
Event Dispatcher notifies subscribers:
    ├─ Email Notification Subscriber → Send email to faculty
    ├─ Audit Log Subscriber → Record creation in logs
    └─ Cache Invalidation Subscriber → Clear cached schedules
```

### 3.4.6 Role-Based Access Control (RBAC)

**Concept**: Access permissions based on user roles rather than individual users.

**Components**:
- **Roles**: Admin, Department Head, Faculty
- **Permissions**: Create, Read, Update, Delete schedules
- **Users**: Assigned one or more roles

**Access Matrix**:
```
| Action               | Admin | Dept Head | Faculty |
|---------------------|-------|-----------|---------|
| Create Schedule     |   ✓   |     ✓     |    ✗    |
| Edit Any Schedule   |   ✓   |     ✗     |    ✗    |
| Edit Own Dept       |   ✓   |     ✓     |    ✗    |
| View Schedules      |   ✓   |     ✓     |    ✓    |
| Delete Schedule     |   ✓   |     ✓     |    ✗    |
| Manage Users        |   ✓   |     ✗     |    ✗    |
```

### 3.4.7 Database Normalization

**Concept**: Organizing database to reduce redundancy and improve data integrity.

**Normal Forms Applied**:

**1st Normal Form (1NF)**:
- All attributes contain atomic values
- Each record is unique

**2nd Normal Form (2NF)**:
- Meets 1NF
- All non-key attributes fully depend on primary key

**3rd Normal Form (3NF)**:
- Meets 2NF
- No transitive dependencies

**Example in Project**:
```
Schedules Table:
- id (Primary Key)
- subject_id (Foreign Key to Subjects)
- room_id (Foreign Key to Rooms)
- faculty_id (Foreign Key to Faculty)
- time, date, etc.

Instead of storing subject details in schedules,
separate Subjects table maintains single source of truth.
```

### 3.4.8 RESTful Design Principles

**Concept**: Architectural style for web services using HTTP methods.

**Applied in Project**:
```
GET    /admin/schedule          → List all schedules
GET    /admin/schedule/new      → Show create form
POST   /admin/schedule          → Create schedule
GET    /admin/schedule/{id}     → Show specific schedule
GET    /admin/schedule/{id}/edit → Show edit form
PUT    /admin/schedule/{id}     → Update schedule
DELETE /admin/schedule/{id}     → Delete schedule
```

**Benefits**:
- **Standardization**: Predictable URL patterns
- **Statelessness**: Each request is independent
- **Cacheable**: Improved performance
- **Uniform Interface**: Consistent interactions

### 3.4.9 Constraint Satisfaction Problem (CSP) Framework

**Concept**: Mathematical framework for scheduling with constraints.

**Components**:
1. **Variables**: Time slots, rooms, faculty
2. **Domains**: Possible values for each variable
3. **Constraints**: Rules that must be satisfied

**CSP Formulation for Scheduling**:
```
Variables: {S1, S2, ..., Sn} (Schedules)
Domains: 
  - Time: {8:00 AM, 9:00 AM, ..., 5:00 PM}
  - Room: {R101, R102, ..., R250}
  - Days: {M-W-F, T-TH, etc.}

Constraints:
  - ∀ schedules Si, Sj: room(Si) ≠ room(Sj) if time_overlap(Si, Sj)
  - ∀ schedules Si, Sj: faculty(Si) ≠ faculty(Sj) if time_overlap(Si, Sj)
  - ∀ schedule Si: enrolled(Si) ≤ capacity(room(Si))
```

---

## 3.5 System Integration

### 3.5.1 Component Integration

The system integrates multiple components to provide comprehensive functionality:

```
┌─────────────────────────────────────────────────────┐
│              User Interface Layer                   │
│  (Twig Templates + Tailwind CSS + JavaScript)      │
└───────────────────┬─────────────────────────────────┘
                    │
┌───────────────────▼─────────────────────────────────┐
│            Symfony Framework Core                   │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────┐  │
│  │ Security │  │  Forms   │  │ Event Dispatcher │  │
│  └──────────┘  └──────────┘  └──────────────────┘  │
└───────────────────┬─────────────────────────────────┘
                    │
┌───────────────────▼─────────────────────────────────┐
│              Business Services                      │
│  ┌──────────────────┐  ┌──────────────────────┐    │
│  │ Schedule Service │  │ Conflict Detector    │    │
│  └──────────────────┘  └──────────────────────┘    │
└───────────────────┬─────────────────────────────────┘
                    │
┌───────────────────▼─────────────────────────────────┐
│           Doctrine ORM Layer                        │
│  ┌──────────────┐  ┌──────────────┐                │
│  │ Repositories │  │   Entities   │                │
│  └──────────────┘  └──────────────┘                │
└───────────────────┬─────────────────────────────────┘
                    │
┌───────────────────▼─────────────────────────────────┐
│              Database Layer                         │
│           (PostgreSQL/MySQL)                        │
└─────────────────────────────────────────────────────┘
```

### 3.5.2 External System Integration Capability

The system is designed to integrate with:
- **Email Systems**: Notification delivery
- **Authentication Services**: LDAP/Active Directory support
- **Export Systems**: PDF generation, CSV exports
- **Logging Systems**: Centralized log aggregation

---

## 3.6 Security Framework

### 3.6.1 Authentication & Authorization

- **Password Hashing**: Bcrypt algorithm for secure password storage
- **CSRF Protection**: Token-based form protection
- **Session Management**: Secure session handling
- **Role-Based Access**: Granular permission control

### 3.6.2 Data Protection

- **Input Validation**: Server-side form validation
- **SQL Injection Prevention**: Doctrine ORM parameterized queries
- **XSS Prevention**: Twig auto-escaping
- **HTTPS Enforcement**: Secure communication

---

## 3.7 Performance Optimization

### 3.7.1 Database Optimization

- **Indexing**: Strategic index placement on frequently queried fields
- **Query Optimization**: Efficient JOIN operations
- **Connection Pooling**: Database connection reuse

### 3.7.2 Application Optimization

- **Caching**: Symfony cache for frequently accessed data
- **Lazy Loading**: Doctrine lazy loading for related entities
- **Asset Optimization**: Minification and compression

---

## 3.8 Deployment Architecture

### 3.8.1 Cloud Deployment with Railway

The application is deployed using Railway platform with Nixpacks build system:

```
┌─────────────────────────────────────────────┐
│           Railway Platform                  │
│                                             │
│  ┌─────────────────────────────────────┐   │
│  │     Nixpacks Build System           │   │
│  │  - Auto-detects PHP 8.5             │   │
│  │  - Installs required extensions     │   │
│  │  - Runs composer install            │   │
│  └─────────────────────────────────────┘   │
│                                             │
│  ┌─────────────────────────────────────┐   │
│  │     Application Server              │   │
│  │     (PHP Built-in Server)           │   │
│  └─────────────────────────────────────┘   │
│                                             │
│  ┌─────────────────────────────────────┐   │
│  │     Database Service                │   │
│  │     (PostgreSQL/MySQL)              │   │
│  └─────────────────────────────────────┘   │
└─────────────────────────────────────────────┘
```

**Build Process**:
1. Railway detects `nixpacks.toml` configuration
2. Installs PHP 8.5 and required extensions (zip, pdo_mysql, gd, intl, opcache, mbstring)
3. Runs `composer install` to install dependencies
4. Starts PHP built-in server on allocated port
5. Application is accessible via Railway-provided URL

### 3.8.2 Production Environment

- **Platform**: Railway Cloud Platform
- **Application Server**: PHP Built-in Server
- **Database Server**: Railway PostgreSQL/MySQL Service
- **Build System**: Nixpacks (automatic PHP detection)
- **Monitoring**: Railway logs and metrics dashboard

---

## References

1. Symfony Documentation. (2024). The Symfony Framework. Retrieved from https://symfony.com/doc
2. Doctrine Project. (2024). Doctrine ORM Documentation. Retrieved from https://www.doctrine-project.org
3. Tailwind CSS. (2024). Tailwind CSS Documentation. Retrieved from https://tailwindcss.com/docs
4. Teven, C. & Werra, L. (2020). Constraint Satisfaction Problems in Course Scheduling. Journal of Educational Technology Systems.
5. Gamma, E., Helm, R., Johnson, R., & Vlissides, J. (1994). Design Patterns: Elements of Reusable Object-Oriented Software.
6. Fowler, M. (2002). Patterns of Enterprise Application Architecture. Addison-Wesley.
6. Railway Corp. (2024). Railway Documentation. Retrieved from https://docs.railway.app
7. Nixpacks. (2024). Nixpacks Build System. Retrieved from https://nixpacks.com
