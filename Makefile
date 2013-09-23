all: composer-install vendor-install assets

assets:
	php app/console assets:install web --symlink --relative

composer-install:
	curl -s https://getcomposer.org/installer | php -- --install-dir=bin

composer-update:
	php bin/composer.phar selfupdate

doc:
	rm -rf docs/phpdoc
	mkdir docs/phpdoc
	phpdoc -d src -t docs/phpdoc 

vendor-install:
	php bin/composer.phar install
