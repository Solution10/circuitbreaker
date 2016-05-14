all: test-unit-coverage

test-unit:
	vendor/bin/phpunit

test-unit-coverage:
	vendor/bin/phpunit --coverage-html report
	open ./report/index.html
