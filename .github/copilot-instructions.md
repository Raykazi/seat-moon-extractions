<!-- Use this file to provide workspace-specific custom instructions to Copilot. For more details, visit https://code.visualstudio.com/docs/copilot/copilot-customization#_use-a-githubcopilotinstructionsmd-file -->

# SeAT Moon Extractions Plugin Instructions

This is a SeAT plugin for EVE Online that exposes corporation moon extraction times through an API.

## Project Structure
- This follows SeAT's plugin architecture using Laravel patterns
- PHP 8.1+ with Laravel framework
- Uses SeAT's existing EVE API integration and authentication
- Follows PSR-4 autoloading standards

## Key Components
- **Models**: `MoonExtraction` model represents moon mining extractions
- **Controllers**: API controllers for exposing extraction data
- **Jobs**: Background jobs for syncing data from EVE's ESI API
- **Commands**: Artisan commands for manual synchronization
- **Routes**: API routes following RESTful conventions

## SeAT Integration
- Extends SeAT's AbstractAuthCorporationJob for ESI API calls
- Uses SeAT's existing authentication and token management
- Integrates with SeAT's universe data models (systems, regions)
- Follows SeAT's plugin service provider pattern

## API Endpoints
- GET `/api/v1/moon-extractions/` - List all extractions with filtering
- GET `/api/v1/moon-extractions/corporation/{id}` - Corporation-specific extractions
- GET `/api/v1/moon-extractions/system/{id}` - System-specific extractions  
- GET `/api/v1/moon-extractions/upcoming` - Upcoming extractions
- GET `/api/v1/moon-extractions/statistics` - Extraction statistics

## Development Guidelines
- Follow Laravel coding standards and conventions
- Use SeAT's existing patterns for ESI API integration
- Implement proper error handling and logging
- Use caching for frequently accessed data
- Follow semantic versioning for releases
- Write comprehensive tests for API endpoints
