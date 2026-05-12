.PHONY: help test analyse audit check

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

test: ## Run tests
	@php vendor/bin/phpunit --no-coverage

test-coverage: ## Run tests with coverage
	@php -d zend_extension=xdebug vendor/bin/phpunit --coverage-html coverage/html

analyse: ## Run PHPStan
	@php -d memory_limit=512M vendor/bin/phpstan analyse --no-progress --memory-limit=512M

audit: ## Composer security audit
	@composer audit --format=table || true

check: audit analyse test ## Run all quality checks

clean: ## Clean cache and coverage
	@rm -rf coverage/ .phpunit.cache
	@echo "Cleaned."
