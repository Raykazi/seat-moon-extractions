# Changelog

All notable changes to the SeAT Moon Extractions Plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial release of SeAT Moon Extractions Plugin
- REST API endpoints for accessing moon extraction data
- Background synchronization with EVE's ESI API
- Support for multiple corporations
- Comprehensive filtering and pagination
- Moon extraction statistics
- Rate limiting and caching
- Artisan commands for manual synchronization
- Database migrations for storing extraction data
- API resources for consistent JSON formatting
- Example usage scripts

### Features
- **API Endpoints:**
  - `GET /api/v1/moon-extractions/` - List all extractions
  - `GET /api/v1/moon-extractions/corporation/{id}` - Corporation extractions
  - `GET /api/v1/moon-extractions/system/{id}` - System extractions
  - `GET /api/v1/moon-extractions/structure/{id}` - Specific extraction
  - `GET /api/v1/moon-extractions/upcoming` - Upcoming extractions
  - `GET /api/v1/moon-extractions/statistics` - Statistics

- **Filtering Options:**
  - By corporation ID
  - By system ID
  - By region ID
  - By extraction status
  - By time range
  - Pagination support

- **Background Jobs:**
  - Automatic syncing from ESI API
  - Intelligent caching to reduce API load
  - Status tracking for extractions

- **Management Commands:**
  - `moon-extractions:sync` - Manual synchronization
  - Support for corporation-specific syncing
  - Force sync option

### Technical Details
- PHP 8.1+ compatibility
- SeAT 5.0+ integration
- Laravel framework patterns
- PSR-4 autoloading
- Comprehensive error handling
- Logging integration

## [1.0.0] - TBD

### Added
- Initial stable release

---

## Release Guidelines

### Version Numbers
- **Major** (X.0.0): Breaking changes, major new features
- **Minor** (0.X.0): New features, backward compatible
- **Patch** (0.0.X): Bug fixes, security updates

### Release Process
1. Update version in `composer.json`
2. Update `CHANGELOG.md`
3. Create git tag: `git tag -a v1.0.0 -m "Release v1.0.0"`
4. Push changes: `git push origin main --tags`
5. Create GitHub release with changelog notes

### Breaking Changes
Breaking changes will be clearly documented and include migration guides where applicable.
