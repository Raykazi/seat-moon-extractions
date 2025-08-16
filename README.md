# SeAT Moon Extractions Plugin

A SeAT plugin for EVE Online that exposes corporation moon extraction times and details through a comprehensive API.

## Features

- **Real-time Extraction Data**: Sync moon extraction schedules from EVE's ESI API
- **Comprehensive API**: RESTful endpoints for accessing extraction data
- **Multi-Corporation Support**: Handle extractions for multiple corporations
- **Advanced Filtering**: Filter by corporation, system, region, and time ranges
- **Statistics**: Get extraction statistics and summaries
- **Caching**: Intelligent caching to reduce API load
- **Background Sync**: Automated syncing via scheduled jobs

## Installation

1. **Install via Composer**:
   ```bash
   composer require your-namespace/seat-moon-extractions
   ```

2. **Publish Configuration** (optional):
   ```bash
   php artisan vendor:publish --provider="MrMajestic\Seat\MoonExtractions\MoonExtractionsServiceProvider" --tag="config"
   ```

3. **Run Migrations**:
   ```bash
   php artisan migrate
   ```

4. **Set up Scheduled Task** (add to your crontab or task scheduler):
   ```bash
   php artisan moon-extractions:sync
   ```

## Configuration

The plugin can be configured via environment variables:

```env
# Cache duration for extraction data (seconds)
MOON_EXTRACTIONS_CACHE_DURATION=3600

# API rate limiting (requests per minute)
MOON_EXTRACTIONS_API_RATE_LIMIT=60

# Sync interval to prevent too frequent updates (seconds)
MOON_EXTRACTIONS_SYNC_INTERVAL=900

# Whether to include completed extractions in API responses
MOON_EXTRACTIONS_INCLUDE_COMPLETED=false

# Maximum number of results per API request
MOON_EXTRACTIONS_MAX_RESULTS=1000
```

## API Endpoints

### Authentication

All API endpoints require authentication using SeAT's API authentication system. Include your API token in the request headers:

```
Authorization: Bearer YOUR_API_TOKEN
```

### Endpoints

#### Get All Extractions
```
GET /api/v1/moon-extractions/
```

**Query Parameters:**
- `corporation_id` - Filter by corporation ID
- `system_id` - Filter by system ID  
- `region_id` - Filter by region ID
- `status` - Filter by status (scheduled, active, completed, cancelled)
- `start_time` - Filter by start time (ISO 8601 format)
- `end_time` - Filter by end time (ISO 8601 format)
- `per_page` - Results per page (default: 50, max: 1000)

#### Get Corporation Extractions
```
GET /api/v1/moon-extractions/corporation/{corporationId}
```

#### Get System Extractions  
```
GET /api/v1/moon-extractions/system/{systemId}
```

#### Get Specific Extraction
```
GET /api/v1/moon-extractions/structure/{structureId}
```

#### Get Upcoming Extractions
```
GET /api/v1/moon-extractions/upcoming
```

**Query Parameters:**
- `hours` - Number of hours ahead to look (default: 24)

#### Get Statistics
```
GET /api/v1/moon-extractions/statistics
```

### Response Format

```json
{
  "data": [
    {
      "id": 1,
      "structure_id": 1000000000001,
      "structure_name": "Mining Platform Alpha",
      "corporation": {
        "id": 98000001,
        "name": "Example Corporation"
      },
      "location": {
        "system": {
          "id": 30000142,
          "name": "Jita"
        },
        "region": {
          "id": 10000002,
          "name": "The Forge"
        }
      },
      "extraction": {
        "start_time": "2024-01-15T12:00:00Z",
        "chunk_arrival_time": "2024-01-16T12:00:00Z",
        "natural_decay_time": "2024-01-19T12:00:00Z",
        "status": "scheduled",
        "is_active": false,
        "time_to_arrival_seconds": 86400
      },
      "moon_materials": [
        {
          "type_id": 16634,
          "type_name": "Pyerite",
          "quantity": 1000000
        }
      ],
      "estimated_value": {
        "amount": 50000000.00,
        "currency": "ISK"
      },
      "timestamps": {
        "created_at": "2024-01-15T10:00:00Z",
        "updated_at": "2024-01-15T10:00:00Z"
      }
    }
  ],
  "meta": {
    "total": 150,
    "count": 50,
    "per_page": 50,
    "current_page": 1,
    "last_page": 3,
    "from": 1,
    "to": 50
  },
  "links": {
    "first": "/api/v1/moon-extractions/?page=1",
    "last": "/api/v1/moon-extractions/?page=3",
    "prev": null,
    "next": "/api/v1/moon-extractions/?page=2"
  }
}
```

## Commands

### Sync Moon Extractions

```bash
# Sync all corporations
php artisan moon-extractions:sync

# Sync specific corporation
php artisan moon-extractions:sync --corporation-id=98000001

# Force sync (ignore cache)
php artisan moon-extractions:sync --force
```

## Requirements

- **PHP**: 8.1 or higher
- **SeAT**: 5.0 or higher
- **Laravel**: 9.x or 10.x (as required by SeAT)
- **Database**: MySQL/MariaDB or PostgreSQL

## Permissions

This plugin requires the following EVE Online ESI scopes:
- `esi-industry.read_corporation_mining.v1` - Required to read corporation mining extractions
- `esi-universe.read_structures.v1` - Required to get structure information

## Development

### Setting up Development Environment

1. Clone the repository
2. Install dependencies: `composer install`
3. Copy `.env.example` to `.env` and configure
4. Run migrations: `php artisan migrate`
5. Run tests: `composer test`

### Testing

```bash
# Run all tests
composer test

# Run specific test suite  
composer test -- --testsuite=Feature

# Run with coverage
composer test-coverage
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

## Security

If you discover any security-related issues, please email [your.email@example.com] instead of using the issue tracker.

## License

This plugin is licensed under the [MIT License](LICENSE).

## Support

- **Issues**: Report bugs and feature requests on [GitHub Issues](https://github.com/your-namespace/seat-moon-extractions/issues)
- **Documentation**: Full API documentation available at [your-docs-url]
- **SeAT Discord**: Get help in the SeAT community Discord server

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and changes.
