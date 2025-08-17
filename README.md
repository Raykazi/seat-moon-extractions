# SeAT Moon Extractions Plugin

This is a SeAT plugin for EVE Online that exposes corporation moon extraction times through a RESTful API.

## Project Structure

- Follows SeAT's plugin architecture using Laravel patterns
- PHP 8.1+ with Laravel framework
- Uses SeAT's EVE API integration and authentication
- PSR-4 autoloading standards
- Main components located in `src/`:
  - `MoonExtractionsServiceProvider.php`: Registers plugin services and routes
  - `Commands/`: Artisan commands for manual synchronization
  - `Http/Controllers/Api/V2/`: API controllers for moon extraction data
  - `Http/routes.php`: API route definitions
  - `Http/Resources/`: API resource and collection classes

## Key Components

- **Models**: `MoonExtraction` model represents moon mining extractions
- **Controllers**: API controllers for exposing extraction data
- **Jobs**: Background jobs for syncing data from EVE's ESI API
- **Commands**: Artisan commands for manual synchronization
- **Routes**: API routes following RESTful conventions

## SeAT Integration

- Extends SeAT's `AbstractAuthCorporationJob` for ESI API calls
- Uses SeAT's authentication and token management
- Integrates with SeAT's universe data models (systems, regions)
- Follows SeAT's plugin service provider pattern

## API Endpoints

- `GET /api/v1/moon-extractions/` - List all extractions with filtering
- `GET /api/v1/moon-extractions/corporation/{id}` - Corporation-specific extractions
- `GET /api/v1/moon-extractions/system/{id}` - System-specific extractions
- `GET /api/v1/moon-extractions/upcoming` - Upcoming extractions
- `GET /api/v1/moon-extractions/statistics` - Extraction statistics

## Features

- Real-time extraction data sync from EVE's ESI API
- RESTful endpoints for accessing extraction data
- Multi-corporation support
- Advanced filtering by corporation, system, region, and time ranges
- Extraction statistics and summaries
- Caching for frequently accessed data
- Automated background syncing via scheduled jobs

## Development Guidelines

- Follow Laravel coding standards and conventions
- Use SeAT's existing patterns for ESI API integration
- Implement proper error handling and logging
- Use caching for frequently accessed data
- Follow semantic versioning for releases
- Write comprehensive tests for API endpoints

## Installation

1. **Install via Composer**:
   ```bash
   composer require seat/moon-extractions
   ```
2. **Register the plugin in SeAT** (see SeAT documentation for plugin registration).
3. **Run migrations and publish resources as needed**.

## License

This project is open-source and available under the MIT License.
