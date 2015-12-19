# GitHook'd

[![Build Status](https://travis-ci.org/stevegrunwell/githookd.svg?branch=master)](https://travis-ci.org/stevegrunwell/githookd)
[![GitHub release](https://img.shields.io/github/release/stevegrunwell/githookd.svg)](https://github.com/stevegrunwell/githookd/releases/latest)
[![GitHub license](https://img.shields.io/github/license/stevegrunwell/githookd.svg)](https://github.com/stevegrunwell/githookd/blob/master/LICENSE.txt)

GitHook'd is a PHP-CLI library to make the automatic installation of [Git hooks](https://git-scm.com/book/en/v2/Customizing-Git-Git-Hooks) easier in your projects.

## Why Git hooks?

[Git hooks](https://git-scm.com/book/en/v2/Customizing-Git-Git-Hooks) allow you to execute scripts at different points throughout the Git workflow. A popular use is checking code against coding standards before it gets committed (see [WP Enforcer](https://github.com/stevegrunwell/wp-enforcer) for a practical example), ensuring that all code in the repository conforms to your projects' standards.
