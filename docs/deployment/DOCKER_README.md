# Budget Control - Docker Setup

This guide explains how to run the Budget Control application using Docker for local development and testing.

## Prerequisites

- Docker installed on your system
- Docker Compose installed on your system

## Quick Start

1. **Build and start the application:**
   ```bash
   docker-compose up --build
   ```

2. **Access the application:**
   Open your browser and go to: http://localhost:8080

3. **Stop the application:**
   ```bash
   docker-compose down
   ```

## Docker Architecture

The Docker setup includes:

- **PHP 8.2** with Apache web server
- **SQLite database** (file-based, no separate database container needed)
- **Composer** for PHP dependency management
- **Persistent volumes** for database, uploads, and storage

## Environment Configuration

The application uses the following environment variables (configured in `docker-compose.yml`):

- `APP_NAME`: Application name
- `APP_DEBUG`: Debug mode (true for development)
- `APP_URL`: Application URL
- `DATABASE_PATH`: SQLite database file path
- `CURRENCY`: Default currency (CZK)
- `TIMEZONE`: Application timezone

## Volumes

The following directories are mounted as persistent volumes:

- `./budget-app/database`: SQLite database files
- `./budget-app/uploads`: CSV import files
- `./budget-app/storage`: Logs and cache files

## Development Workflow

### First Time Setup
```bash
# Build the Docker image
docker-compose build

# Start the application
docker-compose up -d

# View logs
docker-compose logs -f budget-app
```

### Database Initialization

The SQLite database will be created automatically when you first access the application. The database schema is defined in `database/schema.sql`.

### Making Code Changes

Since the application code is not mounted as a volume, you'll need to rebuild the Docker image when you make code changes:

```bash
# Stop the application
docker-compose down

# Rebuild and restart
docker-compose up --build -d
```

### Accessing the Container

For debugging or running commands inside the container:

```bash
# Access the running container
docker-compose exec budget-app bash

# Run PHP commands
docker-compose exec budget-app php -v

# Check Apache status
docker-compose exec budget-app apache2ctl status
```

## Troubleshooting

### Common Issues

1. **Port 8080 already in use:**
   - Change the port mapping in `docker-compose.yml`
   - Example: Change `"8080:80"` to `"8081:80"`

2. **Permission issues:**
   - The Dockerfile sets appropriate permissions for the web server
   - If you encounter issues, check the volume mounts

3. **Database connection issues:**
   - Ensure the `DATABASE_PATH` environment variable points to the correct location
   - Check that the database directory has write permissions

4. **Build failures:**
   - Clear Docker cache: `docker system prune -a`
   - Rebuild: `docker-compose build --no-cache`

### Logs

View application logs:
```bash
# View all logs
docker-compose logs

# Follow logs in real-time
docker-compose logs -f

# View specific service logs
docker-compose logs budget-app
```

### Database Access

The SQLite database file is stored in `./budget-app/database/budget.db`. You can access it using any SQLite browser or command-line tool:

```bash
# Access database from host (if sqlite3 is installed)
sqlite3 budget-app/database/budget.db

# Or from within the container
docker-compose exec budget-app sqlite3 /var/www/html/database/budget.db
```

## Production Deployment

For production deployment, consider:

1. Using environment-specific docker-compose files
2. Setting up proper SSL/TLS certificates
3. Configuring backup strategies for the database
4. Setting up monitoring and logging
5. Using a reverse proxy (nginx) in front of the application

## File Structure

```
budget-app/
├── Dockerfile              # Docker image definition
├── docker-compose.yml      # Docker Compose configuration
├── .dockerignore          # Files to exclude from Docker build
├── database/              # SQLite database files (volume)
├── uploads/               # CSV import files (volume)
├── storage/               # Logs and cache (volume)
├── public/                # Web root directory
├── src/                   # PHP application source
├── views/                 # Template files
└── composer.json          # PHP dependencies
```

## Support

If you encounter issues:

1. Check the logs using `docker-compose logs`
2. Verify your Docker and Docker Compose versions
3. Ensure ports 8080 are not in use by other applications
4. Review the environment variables in `docker-compose.yml`
