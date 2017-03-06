=== WP-Paybox: Payments via Paybox the traditional way ===
Contributors: drzraf
Requires at least: 4.7
Tested up to: 4.7.2
Stable tag: 0.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A WordPress plugin (but rather a library) to ease Paybox integration.

== Description ==

**[Paybox](http://www1.paybox.com/)** is an online payment gateway.

This plugin intends to define a couple of PHP objects (abstract class, interface and procedure) to
ease the integration between (any) WordPress plugin and the Paybox payment system.

It currently only supports the common "pre-persist" workflow
 (where a "still unpaid" transaction is fully stored using an immutable unique ID **before**
 the redirection and the (possibly successful) payment are done at the paybox.com website.

This plugin provides sugar when it comes to generate the redirection form and/or manage errors/auth/currency codes
 and could be a source of inspiration for Paybox configuration.
