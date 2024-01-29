
# This file is licensed under the Affero General Public License version 3 or
# later. See the COPYING file.
# @author Bernhard Posselt <dev@bernhard-posselt.com>
# @copyright Bernhard Posselt 2016

# Generic Makefile for building and packaging a Nextcloud app which uses npm and
# Composer.
#
# Dependencies:
# * make
# * which
# * curl: used if phpunit and composer are not installed to fetch them from the web
# * tar: for building the archive
# * npm: for building and testing everything JS
#
# If no composer.json is in the app root directory, the Composer step
# will be skipped. The same goes for the package.json which can be located in
# the app root or the js/ directory.
#
# The npm command by launches the npm build script:
#
#    npm run build
#
# The npm test command launches the npm test script:
#
#    npm run test
#
# The idea behind this is to be completely testing and build tool agnostic. All
# build tools and additional package managers should be installed locally in
# your project, since this won't pollute people's global namespace.


app_name=$(notdir $(CURDIR))
build_tools_directory=$(CURDIR)/build/tools
source_build_directory=$(CURDIR)/build/artifacts/source
source_package_name=$(source_build_directory)/$(app_name)
appstore_build_directory:=$(CURDIR)/build/appstore/$(app_name)
appstore_artifact_directory:=$(CURDIR)/build/artifacts/appstore
appstore_package_name:=$(appstore_artifact_directory)/$(app_name)
appstore_sign_dir=$(appstore_build_directory)/sign
cert_dir=$(HOME)/.nextcloud/certificates
npm=$(shell which npm 2> /dev/null)
composer=$(shell which composer 2> /dev/null)

all: build

# Fetches the PHP and JS dependencies and compiles the JS. If no composer.json
# is present, the composer step is skipped, if no package.json or js/package.json
# is present, the npm step is skipped
.PHONY: build
build:
ifneq (,$(wildcard $(CURDIR)/composer.json))
	make composer
endif
ifneq (,$(wildcard $(CURDIR)/package.json))
	make npm
endif
ifneq (,$(wildcard $(CURDIR)/js/package.json))
	make npm
endif

# Installs and updates the composer dependencies. If composer is not installed
# a copy is fetched from the web
.PHONY: composer
composer:npm
ifeq (, $(composer))
	@echo "No composer command available, downloading a copy from the web"
	mkdir -p $(build_tools_directory)
	curl -sS https://getcomposer.org/installer | php
	mv composer.phar $(build_tools_directory)
	php $(build_tools_directory)/composer.phar install --prefer-dist
else
	composer install --prefer-dist
endif

# Installs npm dependencies
.PHONY: npm
npm:
ifeq (,$(wildcard $(CURDIR)/package.json))
	cd js && $(npm) run build
else
	$(npm) ci
	$(npm) run build
endif

# Removes the appstore build
.PHONY: clean
clean:
	rm -rf ./build

# Same as clean but also removes dependencies installed by composer, bower and
# npm
.PHONY: distclean
distclean: clean
	rm -rf vendor
	rm -rf node_modules
	rm -rf js/vendor
	rm -rf js/node_modules

# Builds the source and appstore package
.PHONY: dist
dist:
	make source
	make appstore

# Builds the source package
.PHONY: source
source:
	rm -rf $(source_build_directory)
	mkdir -p $(source_build_directory)
	tar cvzf $(source_package_name).tar.gz ../$(app_name) \
	--exclude-vcs \
	--exclude="../$(app_name)/build" \
	--exclude="../$(app_name)/js/node_modules" \
	--exclude="../$(app_name)/node_modules" \
	--exclude="../$(app_name)/*.log" \
	--exclude="../$(app_name)/js/*.log" \

# Builds the source package for the app store, ignores php and js tests
.PHONY: appstore
appstore:
	rm -rf $(appstore_build_directory) $(appstore_sign_dir) $(appstore_artifact_directory)
	install -d $(appstore_sign_dir)/$(app_name)
	cp -r \
	"appinfo" \
	"img" \
	"lib" \
	"templates" \
	"vendor" \
	$(appstore_sign_dir)/$(app_name)

	# remove composer binaries, those aren't needed
	rm -rf $(appstore_sign_dir)/$(app_name)/vendor/bin
	# the App Store doesn't like .git
	rm -rf $(appstore_sign_dir)/$(app_name)/vendor/arthurhoaro/favicon/.git
	# remove large test files
	rm -rf $(appstore_sign_dir)/$(app_name)/vendor/fivefilters/readability.php/test

	install "CHANGELOG.md" $(appstore_sign_dir)/$(app_name)

	#remove stray .htaccess files since they are filtered by nextcloud
	find $(appstore_sign_dir) -name .htaccess -exec rm {} \;

	# on macOS there is no option "--parents" for the "cp" command
	mkdir -p $(appstore_sign_dir)/$(app_name)/js
	cp js/* $(appstore_sign_dir)/$(app_name)/js/

	# export the key and cert to a file
	@if [ ! -f $(cert_dir)/$(app_name).key ] || [ ! -f $(cert_dir)/$(app_name).crt ]; then \
		echo "Key and cert do not exist"; \
		mkdir -p $(cert_dir); \
		php ./bin/tools/file_from_env.php "app_private_key" "$(cert_dir)/$(app_name).key"; \
		php ./bin/tools/file_from_env.php "app_public_crt" "$(cert_dir)/$(app_name).crt"; \
	fi

	@if [ -f $(cert_dir)/$(app_name).key ]; then \
		echo "Signing app files…"; \
		php ../../occ integrity:sign-app \
			--privateKey=$(cert_dir)/$(app_name).key\
			--certificate=$(cert_dir)/$(app_name).crt\
			--path=$(appstore_sign_dir)/$(app_name); \
		echo "Signing app files ... done"; \
	fi
	mkdir -p $(appstore_artifact_directory)
	tar -czf $(appstore_package_name).tar.gz -C $(appstore_sign_dir) $(app_name)

.PHONY: test
test: composer
	$(CURDIR)/vendor/phpunit/phpunit/phpunit -c phpunit.xml
	$(CURDIR)/vendor/phpunit/phpunit/phpunit -c phpunit.integration.xml
