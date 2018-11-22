#!/usr/bin/env bash

echo "usage: $0 [wp-version] [db-name] [db-user] [db-pass] [db-host]"

WP_VERSION=${1-stable}

DB_NAME=${2-wordpress_test}
DB_USER=${3-root}
DB_PASS=${4-}
DB_HOST=${5-localhost}

export WP_DEVELOP_DIR=${WP_DEVELOP_DIR-/tmp/wordpress/}

PROJECT_DIR=$(pwd)
plugin_slug=$(basename ${PROJECT_DIR})

set -ex

install_wp() {
	rm -rf ${WP_DEVELOP_DIR}
	mkdir -p ${WP_DEVELOP_DIR}

	git clone --depth=1 --quiet git://develop.git.wordpress.org/ ${WP_DEVELOP_DIR}/
	cd ${WP_DEVELOP_DIR}

	if [[ ${WP_VERSION} == 'latest' ]] || [[ ${WP_VERSION} == 'develop' ]]; then
		export WP_VERSION='master'
	elif [[ ${WP_VERSION} == 'stable' ]]; then
		git fetch --tags --depth=1 --quiet
		export WP_VERSION=$(git tag | sort -n | tail -1)
	fi

	git checkout ${WP_VERSION} --quiet
}

create_db() {
	# parse DB_HOST for port or socket references
	local PARTS=(${DB_HOST//\:/ })
	local DB_HOSTNAME=${PARTS[0]};
	local DB_SOCK_OR_PORT=${PARTS[1]};
	local EXTRA=""

	if ! [[ -z ${DB_HOSTNAME} ]] ; then
		if [[ $(echo ${DB_SOCK_OR_PORT} | grep -e '^[0-9]\{1,\}$') ]]; then
			EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT --protocol=tcp"
		elif ! [[ -z ${DB_SOCK_OR_PORT} ]] ; then
			EXTRA=" --socket=$DB_SOCK_OR_PORT"
		elif ! [[ -z ${DB_HOSTNAME} ]] ; then
			EXTRA=" --host=$DB_HOSTNAME --protocol=tcp"
		fi
	fi

	# create database
	mysql -e "DROP DATABASE IF EXISTS $DB_NAME; CREATE DATABASE $DB_NAME;" --user="$DB_USER" --password="$DB_PASS"${EXTRA}
}

config_test_suite() {
	local opts='-i'

	# portable in-place argument for both GNU sed and Mac OSX sed
	if [[ $(uname -s) == 'Darwin' ]]; then
		local opts='-i .bak'
	fi

    cp wp-tests-config-sample.php wp-tests-config.php

	sed ${opts} "s/youremptytestdbnamehere/$DB_NAME/" wp-tests-config.php
	sed ${opts} "s/yourusernamehere/$DB_USER/" wp-tests-config.php
	sed ${opts} "s/yourpasswordhere/$DB_PASS/" wp-tests-config.php
	sed ${opts} "s|localhost|${DB_HOST}|" wp-tests-config.php
}

install_plugin () {
	if [[ -d ${PROJECT_DIR}/dist ]]
	then
        mv ${PROJECT_DIR}/dist ${WP_DEVELOP_DIR}/src/wp-content/plugins/${plugin_slug}
    fi
}

install_wp
create_db
config_test_suite
install_plugin
