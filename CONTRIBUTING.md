# Contributing

Contributions are welcome! Here's how to get started.

## Development Setup

```bash
git clone https://github.com/selcukmart/smart-autoload-converter.git
cd smart-autoload-converter
make build
make test
```

## Pull Request Process

1. Fork the repo and create a feature branch
2. Write tests for new functionality
3. Ensure all tests pass: `make test`
4. Submit a PR with a clear description

## Coding Standards

- PHP 8.5, strict types everywhere
- PSR-4 autoloading under `SmartAutoloadConverter\`
- `readonly class` for value objects
- No static state or singletons
- Every domain service is injectable and testable in isolation

## Reporting Bugs

Open an issue with:
- Steps to reproduce
- Expected vs actual behavior
- PHP version and OS
