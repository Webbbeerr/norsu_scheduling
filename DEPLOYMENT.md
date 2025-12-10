# Deployment Guide - Smart Scheduling System

## Initial Production Setup

When you deploy this system to a live server for the first time, you won't have any users in the database. Follow these steps to set up your initial admin account.

### Option 1: Create Admin User via Command (Recommended)

After deploying your application and setting up the database, run:

```bash
php bin/console app:create-admin
```

The command will interactively prompt you for:
- Admin username
- Admin email
- Admin password (min 8 characters)
- First name
- Last name

**Example:**
```bash
$ php bin/console app:create-admin

 Create Admin User
 ==================

 Enter admin username [admin]:
 > admin

 Enter admin email [admin@norsu.edu.ph]:
 > admin@norsu.edu.ph

 Enter admin password (min 8 characters):
 > ********

 Confirm password:
 > ********

 Enter first name [Admin]:
 > Juan

 Enter last name [User]:
 > Dela Cruz

 [OK] Admin user created successfully!
```

### Option 2: Non-Interactive Mode

You can also provide all parameters directly:

```bash
php bin/console app:create-admin admin admin@norsu.edu.ph YourSecurePassword123 --first-name=Juan --last-name=DelaCruz
```

### Option 3: Using Test Users (Development Only)

For development or testing purposes only, you can create sample users:

```bash
php bin/console app:create-test-users
```

This creates:
- 1 Admin user (username: `admin`, password: `password`)
- 2 Department Heads
- 3 Faculty members

**⚠️ WARNING:** Do NOT use test users in production! They have weak passwords and are meant for development only.

## Production Deployment Checklist

1. **Set up your database**
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

2. **Create your admin user**
   ```bash
   php bin/console app:create-admin
   ```

3. **Clear and warm up cache**
   ```bash
   php bin/console cache:clear
   php bin/console cache:warmup
   ```

4. **Set proper permissions** (Linux/Unix)
   ```bash
   chmod -R 777 var/cache var/log
   ```

5. **Login to the system**
   - Navigate to: `https://your-domain.com/login`
   - Use the credentials you created in step 2
   - You'll have full admin access to create other users

## Adding More Users After Initial Setup

Once logged in as admin, you can:
1. Navigate to Admin Panel > Users
2. Create additional users (admins, department heads, faculty)
3. Assign roles and departments as needed

## Security Recommendations

1. **Use strong passwords** - At least 12 characters with mixed case, numbers, and symbols
2. **Change default passwords** - If you used the test users command, change all passwords immediately
3. **Enable HTTPS** - Always use SSL/TLS in production
4. **Regular backups** - Set up automated database backups
5. **Monitor access logs** - Keep track of admin login attempts

## Troubleshooting

### "User already exists" error
If you try to create an admin user but get an error that the user exists, you can:
1. Check existing users in the database
2. Use a different username/email
3. Or reset the password of the existing user

### Can't access admin panel
Make sure your user has the `ROLE_ADMIN` role assigned. You can verify this in the database or create a new admin user with the command above.

## Environment Variables

Make sure these are set in your production `.env` file:

```env
APP_ENV=prod
APP_DEBUG=0
DATABASE_URL="mysql://user:password@localhost:3306/database_name"
```

## Support

For additional help, refer to the main README.md or contact your system administrator.
