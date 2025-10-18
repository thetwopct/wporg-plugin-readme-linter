# Contributing to WP.org Plugin Readme Linter

Thank you for your interest in contributing! This document provides guidelines for contributing to the project.

## Code of Conduct

Be respectful and considerate of others. We aim to maintain a welcoming and inclusive environment.

## How to Contribute

### Reporting Bugs

Before creating a bug report, please:
1. Check the [existing issues](https://github.com/thetwopct/wporg-plugin-readme-linter/issues) to see if it's already reported
2. Ensure you're using the latest version
3. Collect information about the bug (readme content, error messages, PHP version, etc.)

When reporting a bug, include:
- Clear description of the issue
- Steps to reproduce
- Expected vs. actual behavior
- Sample readme.txt that triggers the issue
- PHP version and OS
- Full error output

### Suggesting Enhancements

Enhancement suggestions are welcome! Please:
1. Check existing issues and discussions
2. Describe the enhancement in detail
3. Explain the use case
4. Consider backward compatibility

### Submitting Pull Requests

1. **Fork the repository** and create your branch from `main`
2. **Install dependencies**: `composer install`
3. **Make your changes**:
   - Follow PSR-12 coding standards
   - Add/update tests for your changes
   - Update documentation as needed
4. **Run tests and checks**:
   ```bash
   composer test          # Run tests
   vendor/bin/phpstan analyse  # Static analysis
   vendor/bin/phpcs       # Code style
   ```
5. **Commit your changes** with clear, descriptive messages
6. **Push to your fork** and submit a pull request

### Pull Request Guidelines

- Keep changes focused - one feature/fix per PR
- Include tests that cover your changes
- Ensure all tests pass and there are no style violations
- Update CHANGELOG.md with your changes
- Update documentation if needed
- Reference any related issues

## Development Setup

### Requirements

- PHP 8.0 or higher
- Composer
- Git

### Installation

```bash
git clone https://github.com/thetwopct/wporg-plugin-readme-linter.git
cd wporg-plugin-readme-linter
composer install
```

### Running Tests

```bash
# All tests
composer test

# Unit tests
vendor/bin/phpunit --testsuite=Unit

# Integration tests
vendor/bin/phpunit --testsuite=Integration

# With coverage (requires Xdebug)
vendor/bin/phpunit --coverage-html coverage
```

### Code Quality Tools

```bash
# Static analysis
vendor/bin/phpstan analyse

# Check code style
vendor/bin/phpcs

# Fix code style automatically
vendor/bin/phpcbf
```

### Testing the CLI

```bash
# Test with fixtures
./bin/wporg-plugin-readme-linter tests/fixtures/valid-readme.txt
./bin/wporg-plugin-readme-linter tests/fixtures/invalid-readme.txt

# Test different formats
./bin/wporg-plugin-readme-linter tests/fixtures/invalid-readme.txt --format=json
./bin/wporg-plugin-readme-linter tests/fixtures/invalid-readme.txt --format=sarif
```

### Testing the GitHub Action Locally

You can test the Docker container locally:

```bash
# Build the image
docker build -t wporg-readme-linter .

# Run with a test readme
docker run -v $(pwd):/workspace -e GITHUB_WORKSPACE=/workspace wporg-readme-linter readme.txt
```

## Adding New Rules

To add a new linting rule:

1. Create a new rule class in `src/Rule/` that implements `RuleInterface`
2. Extend `AbstractRule` for common functionality
3. Implement the `check()` method to examine readme content
4. Add tests in `tests/Unit/Rule/YourRuleTest.php`
5. Add the rule to `LintCommand.php`
6. Document the rule in README.md
7. Update CHANGELOG.md

Example rule structure:

```php
<?php

namespace WPOrg\Plugin\ReadmeLinter\Rule;

use WPOrg\Plugin\ReadmeLinter\Issue;

class MyNewRule extends AbstractRule
{
    public function getRuleId(): string
    {
        return 'my-new-rule';
    }

    public function getDescription(): string
    {
        return 'Description of what this rule checks';
    }

    public function check(array $parsedData, string $rawContent): array
    {
        $issues = [];
        
        // Your validation logic here
        
        return $issues;
    }
}
```

## Project Structure

```
.
├── bin/                    # CLI entry point
├── src/
│   ├── Config/            # Configuration handling
│   ├── Console/           # CLI commands
│   ├── Parser/            # Readme parsing
│   ├── Reporter/          # Output formatters
│   └── Rule/              # Linting rules
├── tests/
│   ├── Unit/              # Unit tests
│   ├── Integration/       # Integration tests
│   └── fixtures/          # Test readme files
├── action.yml             # GitHub Action definition
├── Dockerfile             # Container action image
└── entrypoint.sh          # Action entrypoint
```

## Release Process

1. Update version in relevant files
2. Update CHANGELOG.md
3. Create a git tag: `git tag -a v1.x.x -m "Release v1.x.x"`
4. Push tag: `git push origin v1.x.x`
5. Create GitHub Release from tag
6. Packagist will auto-update

## Questions?

Feel free to open a [Discussion](https://github.com/thetwopct/wporg-plugin-readme-linter/discussions) if you have questions!
