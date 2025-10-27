# WordPress.org Plugin Readme Linter - Development Guide

## Repository Overview

This is a **PHP library and CLI tool** that validates WordPress.org plugin `readme.txt` files against WordPress.org Plugin Directory standards. The project can be used as a standalone Composer package or as a GitHub Action (Docker container).

**Repository Size:** ~950KB (excluding vendor/)  
**Languages:** PHP 7.4+ / 8.x  
**Framework:** Symfony Console  
**Type:** Library with CLI binary and Docker-based GitHub Action

## Project Structure

```
├── .github/
│   └── workflows/          # CI/CD workflows (tests.yml, test-action.yml)
├── bin/
│   └── wporg-plugin-readme-linter  # CLI entry point
├── src/
│   ├── Config/             # Configuration handling
│   ├── Console/            # CLI commands (LintCommand.php)
│   ├── Parser/             # Readme parsing logic
│   ├── Reporter/           # Output formatters (Text, Annotations, JSON, SARIF)
│   ├── Rule/               # Linting rules (18 rule files)
│   ├── Issue.php           # Issue data structure
│   └── Linter.php          # Main linter logic
├── tests/
│   ├── Unit/               # Unit tests (organized by src/ structure)
│   ├── Integration/        # Integration tests
│   └── fixtures/           # Test readme.txt files (valid/invalid examples)
├── examples/               # Usage examples and custom configs
├── action.yml              # GitHub Action definition
├── Dockerfile              # Container action (PHP 8.3 Alpine)
├── entrypoint.sh           # GitHub Action entrypoint script
├── composer.json           # PHP dependencies and scripts
├── phpunit.xml.dist        # PHPUnit configuration (Unit & Integration suites)
├── phpcs.xml.dist          # PHP_CodeSniffer config (PSR-12)
└── phpstan.neon            # PHPStan static analysis config (level 8)
```

## Build & Development Setup

### Prerequisites
- **PHP 7.4 or higher** (PHP 8.2+ recommended, PHP 8.3 in CI)
- **Composer 2.x**
- **Git**

### Initial Setup

**ALWAYS run these commands in sequence:**

```bash
# 1. Validate composer.json (optional but recommended)
composer validate --strict

# 2. Install dependencies (REQUIRED before any other command)
composer install --no-interaction --no-progress
```

**IMPORTANT:** `composer install` must complete successfully before running any tests, static analysis, or code style checks. If you encounter authentication issues with GitHub during installation, try:
- `composer install --prefer-source --no-interaction` (downloads via git instead of zip)
- Wait a few moments and retry if you see SSL timeouts
- The command may take 2-3 minutes on first run

### Running Tests

**ALWAYS install dependencies first with `composer install` before running tests.**

```bash
# Run all tests (Unit + Integration) - Takes ~5-10 seconds
composer test
# OR
vendor/bin/phpunit

# Run only unit tests
composer test:unit
# OR
vendor/bin/phpunit --testsuite=Unit

# Run only integration tests
composer test:integration
# OR
vendor/bin/phpunit --testsuite=Integration

# Run tests with coverage (requires Xdebug)
vendor/bin/phpunit --coverage-html coverage
```

**Test Organization:**
- Unit tests: `tests/Unit/` - mirrors `src/` structure
- Integration tests: `tests/Integration/` - end-to-end CLI tests
- Test fixtures: `tests/fixtures/*.txt` - sample readme files for testing

### Code Quality Checks

**Run these in order before committing:**

```bash
# 1. Static analysis (PHPStan level 8) - Takes ~10-15 seconds
composer analyse
# OR
vendor/bin/phpstan analyse

# 2. Code style check (PSR-12) - Takes ~2-3 seconds
composer cs:check
# OR
vendor/bin/phpcs

# 3. Auto-fix code style issues
composer cs:fix
# OR
vendor/bin/phpcbf
```

### Testing the CLI Tool

```bash
# Test with valid readme
./bin/wporg-plugin-readme-linter tests/fixtures/valid-readme.txt

# Test with invalid readme (should exit with code 1)
./bin/wporg-plugin-readme-linter tests/fixtures/invalid-readme.txt

# Test different output formats
./bin/wporg-plugin-readme-linter tests/fixtures/invalid-readme.txt --format=json
./bin/wporg-plugin-readme-linter tests/fixtures/invalid-readme.txt --format=sarif --output=test.sarif
./bin/wporg-plugin-readme-linter tests/fixtures/invalid-readme.txt --format=annotations

# Test with custom config
./bin/wporg-plugin-readme-linter readme.txt --config=examples/custom-config/.wporg-readme-lint.json
```

### Testing the Docker Action

```bash
# Build the Docker image - Takes ~60-90 seconds
docker build -t wporg-readme-linter:test .

# Test the container
docker run -v $(pwd):/workspace \
  -e GITHUB_WORKSPACE=/workspace \
  -e GITHUB_OUTPUT=/dev/null \
  wporg-readme-linter:test tests/fixtures/valid-readme.txt annotations error .wporg-readme-lint.json "" false
```

## CI/CD Validation

The repository uses GitHub Actions for continuous integration. **Two workflows run on every push/PR:**

### 1. `tests.yml` - Main Test Suite
**Jobs:**
- **test:** PHP 8.2, runs composer validate, composer install, phpunit, phpstan, phpcs (~2-3 min)
- **integration:** PHP 8.2, tests CLI with valid/invalid files, JSON/SARIF output (~1-2 min)
- **docker:** Builds Docker image and tests container execution (~2-3 min)

### 2. `test-action.yml` - GitHub Action Testing  
**Jobs:**
- **test-action:** Tests the action with valid and invalid readme files (~2-3 min)

**To replicate CI locally:**
```bash
# Full CI simulation
composer validate --strict && \
composer install --no-interaction --no-progress && \
vendor/bin/phpunit && \
vendor/bin/phpstan analyse && \
vendor/bin/phpcs

# Then test CLI
./bin/wporg-plugin-readme-linter tests/fixtures/valid-readme.txt
./bin/wporg-plugin-readme-linter tests/fixtures/invalid-readme.txt || echo "Expected failure"
```

## Common Issues & Workarounds

### Composer Install Failures
**Issue:** `Could not authenticate against github.com` or SSL timeouts  
**Solution:** 
```bash
composer install --prefer-source --no-interaction
# OR wait and retry after a few moments
```

### Missing vendor/bin/ Directory
**Issue:** `vendor/bin/phpunit: No such file or directory`  
**Cause:** Incomplete composer install  
**Solution:** Remove vendor/ and reinstall:
```bash
rm -rf vendor/ composer.lock
composer install --no-interaction
```

### PHPStan/PHPCS Not Found
**Issue:** Cannot find analysis tools  
**Solution:** Ensure composer install completed with dev dependencies:
```bash
composer install --no-interaction
ls -la vendor/phpstan vendor/squizlabs  # Verify installation
```

## Configuration Files

- **`.wporg-readme-lint.json`** - Custom linter configuration (optional, see `.wporg-readme-lint.json.example`)
- **`phpunit.xml.dist`** - PHPUnit test configuration with Unit/Integration suites
- **`phpcs.xml.dist`** - PHP_CodeSniffer PSR-12 ruleset, excludes vendor/ and fixtures/
- **`phpstan.neon`** - PHPStan level 8, analyzes src/ and tests/, excludes fixtures/
- **`composer.json`** - Defines scripts: test, test:unit, test:integration, analyse, cs:check, cs:fix
- **`action.yml`** - GitHub Action inputs/outputs definition

## Adding New Rules

1. Create rule class in `src/Rule/` implementing `RuleInterface` (extend `AbstractRule`)
2. Implement `getRuleId()`, `getDescription()`, and `check()` methods
3. Add rule instantiation in `src/Console/LintCommand.php` (around line 100+)
4. Create unit test in `tests/Unit/Rule/YourRuleTest.php`
5. Add test fixture in `tests/fixtures/` if needed
6. Update README.md rules table
7. Run tests and code quality checks before committing

## Key Dependencies

- **afragen/wordpress-plugin-readme-parser** - Parses WordPress readme.txt format
- **symfony/console** - CLI framework
- **symfony/yaml** - YAML configuration support
- **phpunit/phpunit** - Testing framework
- **phpstan/phpstan** - Static analysis
- **squizlabs/php_codesniffer** - Code style checking (PSR-12)

## Important Notes

- **ALWAYS run `composer install` first** - Nothing works without dependencies installed
- **Test exit codes matter** - CLI exits with code 1 on linting failures, 0 on success, 2 on usage errors
- **GitHub Actions auto-detects format** - Defaults to "annotations" in CI, "text" locally
- **Docker uses PHP 8.3 Alpine** - Local dev can use PHP 7.4-8.3
- **PSR-12 coding standards** - All code must follow PSR-12, auto-fixable with `composer cs:fix`
- **PHPStan level 8** - Strict static analysis required for all code changes
- **Test fixtures are excluded** - Don't apply linting/analysis to `tests/fixtures/*.txt` files

## Quick Command Reference

```bash
composer install               # Install dependencies (REQUIRED FIRST)
composer test                  # Run all tests
composer analyse               # Run PHPStan static analysis
composer cs:check              # Check code style (PSR-12)
composer cs:fix                # Auto-fix code style issues
./bin/wporg-plugin-readme-linter FILE  # Run CLI linter
docker build -t test .         # Build Docker image
```

**Trust these instructions.** Only search for additional information if something is unclear or doesn't work as documented.
