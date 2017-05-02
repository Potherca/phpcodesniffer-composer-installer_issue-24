#!/usr/bin/env bash

# ==============================================================================
# MIT License - Copyright (c) 2017 Dealerdirect B.V.
# ==============================================================================

# ==============================================================================
set -o errexit
set -o errtrace
set -o nounset
set -o pipefail
#-------------------------------------------------------------------------------
readonly EX_OK=0
readonly EX_NOT_ENOUGH_PARAMETERS=65
readonly EX_UNSUPPORTED_PHP_VERSION=66
readonly EX_COULD_NOT_CREATE_SYMLINK=67
# ==============================================================================

# ==============================================================================
createPhpunitSymlink() {

    local sTarget
    local sLinkName
    local sVersion
    local iExitCode

    iExitCode="${EX_OK}"

    readonly sLinkName='phpunit.phar'

    if [[ "$#" != 1 ]];then

        iExitCode="${EX_NOT_ENOUGH_PARAMETERS}"

        echo ' !     ERROR: Not enough parameters given' >&2
        echo '              One parameter expected: the PHP version to create a PhpUnit symlink for' >&2

    elif [[ "$1" = '--help' ]];then

        echo 'One parameter expected: the PHP version to create a PhpUnit symlink for'

    else
        readonly sVersion="${1}"
        case "${sVersion}" in
            5.4*|5.5*)
                sTarget='phpunit-4.8.35.phar'
            ;;

            5.6*)
                sTarget='phpunit-5.7.19.phar'
            ;;

            7.0*|7.1*)
                sTarget='phpunit-6.1.3.phar'
            ;;

            *)
                iExitCode="${EX_UNSUPPORTED_PHP_VERSION}"

                echo " !     ERROR: Given PHP version '${sVersion}' is not supported" >&2
                echo '              Use one of 5.4, 5.5, 5.6, 7.0 or 7.1' >&2
            ;;
        esac

        if [[ "${iExitCode}" = "${EX_OK}" ]];then
            echo "-----> Creating symlink for '${sTarget}'"
            ln --symbolic --force "${sTarget}" "${sLinkName}" || {

                iExitCode="${EX_COULD_NOT_CREATE_SYMLINK}"

                echo ' !     ERROR: Could not create symlink' >&2
            }
        fi
    fi

    return "${iExitCode}"
}
# ==============================================================================

# ==============================================================================
#                               RUN LOGIC
# ------------------------------------------------------------------------------
if [[ "${BASH_SOURCE[0]}" != "${0}" ]]; then
  export -f createPhpunitSymlink
else
  createPhpunitSymlink "${@}"
  exit $?
fi
# ==============================================================================

#EOF
