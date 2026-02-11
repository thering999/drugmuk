# Contributing to Drugmuk

First off, thank you for considering contributing to Drugmuk! It's people like you that make Drugmuk such a great tool.

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues as you might find out that you don't need to create one. When you are creating a bug report, please include as many details as possible:

- **Use a clear and descriptive title**
- **Describe the exact steps to reproduce the problem**
- **Provide specific examples to demonstrate the steps**
- **Describe the behavior you observed after following the steps**
- **Explain which behavior you expected to see instead and why**
- **Include screenshots if possible**
- **Include your environment details** (OS, PHP version, MySQL version, etc.)

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, please include:

- **Use a clear and descriptive title**
- **Provide a step-by-step description of the suggested enhancement**
- **Provide specific examples to demonstrate the steps**
- **Describe the current behavior and explain which behavior you expected to see instead**
- **Explain why this enhancement would be useful**

### Pull Requests

- Fill in the required template
- Do not include issue numbers in the PR title
- Follow the PHP coding standards (PSR-12)
- Include thoughtfully-worded, well-structured tests
- Document new code based on the Documentation Styleguide
- End all files with a newline

## Development Process

### Setting Up Development Environment

1. Fork the repository
2. Clone your fork:
   ```bash
   git clone https://github.com/YOUR_USERNAME/drugmuk.git
   cd drugmuk
   ```

3. Install dependencies:
   ```bash
   composer install
   ```

4. Set up your `.env` file:
   ```bash
   cp .env.example .env
   # Edit .env with your local settings
   ```

5. Create a new branch:
   ```bash
   git checkout -b feature/your-feature-name
   ```

### Coding Standards

We follow PSR-12 coding standards. Please ensure your code adheres to these standards:

```php
<?php

namespace App\Controllers;

use App\Core\BaseController;

class ExampleController extends BaseController
{
    /**
     * Example method with proper documentation
     *
     * @param int $id The ID parameter
     * @return array The result array
     */
    public function exampleMethod(int $id): array
    {
        // Your code here
        return [];
    }
}
```

### Commit Messages

- Use the present tense ("Add feature" not "Added feature")
- Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
- Limit the first line to 72 characters or less
- Reference issues and pull requests liberally after the first line

Examples:
```
Add AI voice command feature

- Implement voice activity detection
- Add Thai language support
- Update UI for microphone button

Fixes #123
```

### Testing

- Write unit tests for new features
- Ensure all tests pass before submitting PR:
  ```bash
  composer test
  ```

- Check code coverage:
  ```bash
  composer test:coverage
  ```

### Documentation

- Update README.md if you change functionality
- Add comments for complex logic
- Update API documentation if you add/modify endpoints
- Add examples for new features

## Project Structure

```
src/
├── Controllers/    # HTTP Controllers
├── Models/         # Data Models
├── Services/       # Business Logic
├── Views/          # View Templates
├── Core/           # Core Framework
├── Middleware/     # HTTP Middleware
└── Exceptions/     # Custom Exceptions
```

## Branch Naming Convention

- `feature/` - New features
- `bugfix/` - Bug fixes
- `hotfix/` - Urgent fixes
- `refactor/` - Code refactoring
- `docs/` - Documentation updates

Examples:
- `feature/ai-voice-commands`
- `bugfix/csrf-validation`
- `hotfix/security-patch`

## Pull Request Process

1. Update the README.md with details of changes if applicable
2. Update the CHANGELOG.md with your changes
3. Ensure all tests pass
4. Update documentation
5. The PR will be merged once you have the sign-off of at least one maintainer

## Code Review Process

- All submissions require review
- We may suggest changes, improvements, or alternatives
- Please respond to review comments in a timely manner
- Once approved, a maintainer will merge your PR

## Community

- Be respectful and inclusive
- Help others when you can
- Share your knowledge
- Be patient with newcomers

## Questions?

Feel free to open an issue with your question or contact the maintainers directly.

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

Thank you for contributing to Drugmuk! 🙏
