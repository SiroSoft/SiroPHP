.PHONY: help test test-coverage analyse audit sbom loadtest check production-check clean

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

sbom: ## Generate CycloneDX SBOM
	@php scripts/generate-sbom.php

loadtest: ## Run basic load test (requires Apache Bench)
	@php scripts/loadtest.php

check: audit sbom analyse test ## Run all quality checks

production-check: audit sbom analyse test test-coverage loadtest ## Full production readiness check

clean: ## Clean cache and coverage
	@rm -rf coverage/ .phpunit.cache
	@echo "Cleaned."
