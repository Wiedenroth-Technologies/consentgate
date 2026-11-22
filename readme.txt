=== ConsentGate – Cookie Banner and Cookie Consent (GDPR / DSGVO) ===
Contributors: wiedenrothtechnologies
Tags: cookie consent, cookie banner, cookie notice, GDPR, dsgvo
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Cookie banner and cookie consent for GDPR and DSGVO. Tracking stays off until a visitor agrees. Includes Google Consent Mode v2.

== Description ==

**Cookie banner and cookie consent for WordPress.** Visitors decide before tracking starts. Scripts for statistics and marketing stay off until they agree. Necessary cookies keep working, including the WooCommerce cart and checkout.

ConsentGate is a cookie consent plugin by Wiedenroth Technologies. It scans the site for known services, blocks them until consent, and stores each choice in a log you can export. IP addresses are not stored.

Learn more on the product page: [wiedenroth.tech/software/consentgate](https://wiedenroth.tech/software/consentgate)

= What the plugin does =

* A cookie banner with three equal choices: accept all, reject all, or a custom selection
* Script and embed blocking until consent. Google Maps, YouTube and Vimeo show a placeholder until marketing is allowed
* Categories: Necessary, Statistics, Marketing and Preferences
* Google Consent Mode v2 starts on denied and updates after the choice. Advanced Consent Mode is optional and off until you turn it on
* A site scan for known services, unknown hosts, and services you add yourself
* A weekly check for new or missing services, with an optional email. It does not change your settings
* A check whether detected services appear in your privacy policy, plus a draft paragraph for that policy
* A consent log on your server, with export. IP addresses are not stored. Retention is 365 days by default and can be changed
* Banner at the bottom, in a corner, or as a modal in the middle. Colors and logo can be adjusted
* Banner text in German or English, following the site language or a language you set. The admin is in English
* A cookie icon or a “Cookie settings” menu link, so visitors can change their choice later
* WooCommerce: cart, checkout and session stay necessary. Shop trackers such as Meta Pixel or Google Ads follow consent
* Works with the WP Consent API when that plugin is active
* Optional: show the banner only in the EU, EEA, UK and Switzerland, if your host already sends a country header (Cloudflare or CloudFront)

= Services the scan can recognize =

Google Ads, Google Analytics, Google Tag Manager, Google Maps, Google Fonts, Google reCAPTCHA, Meta Pixel, TikTok, Pinterest, Microsoft Advertising, Microsoft Clarity, LinkedIn, Snapchat, Klaviyo, Mailchimp, HubSpot, Hotjar, Matomo, YouTube, Vimeo, Adcell, Apple Maps, Linexo and Stripe, plus essential WordPress and WooCommerce cookies. You can add other hosts by hand.

Service names and logos are only there so you can tell the tools apart. They belong to their owners.

= What ConsentGate is not =

The plugin runs the banner and the blocking. It does not replace your privacy policy, and it is not legal advice. Whether the text, the policy and the services on the site are enough is for you or your lawyer to decide. IAB TCF is not included.

A small ConsentGate note in the banner stays off until you turn it on.

== Installation ==

1. In WordPress, go to Plugins → Add New, search for “ConsentGate”, then install and activate the plugin.
2. Finish the first-run setup, including the site scan. Then adjust the banner if you want to.

== Frequently Asked Questions ==

= Does the banner block WooCommerce? =

No. Cart, checkout, and session cookies stay necessary. Tracking such as Meta Pixel or Google Ads loads only after a visitor agrees.

= Do I need another consent plugin? =

No. ConsentGate is the cookie banner and the blocker. If the WP Consent API plugin is already active, ConsentGate uses the same categories and registers as the consent tool.

= Does it support Google Consent Mode v2? =

Yes. ConsentGate sets Google Consent Mode v2 to denied before a choice and updates it afterwards (ad storage, ad user data, ad personalization and analytics storage). Advanced Consent Mode is off unless you turn it on. With it on, Google tags that are already on the page are not blocked, and Google can receive a cookieless signal before a choice.

= Which languages does the banner support? =

German and English. The banner can follow the site language, or you can lock it to German or English. The admin is in English.

= Can visitors change their choice later? =

Yes. A cookie icon at the bottom of the screen reopens the banner. You can turn the icon off and add “Cookie settings” to a WordPress menu instead, or use a link to #consentgate.

= Which services does the scan recognize? =

Known tools include Google Ads, Analytics, Tag Manager, Maps, Fonts and reCAPTCHA, Meta Pixel, TikTok, Pinterest, Microsoft Advertising, Clarity, LinkedIn, Snapchat, Klaviyo, Mailchimp, HubSpot, Hotjar, Matomo, YouTube, Vimeo, Adcell, Apple Maps, Linexo and Stripe, as well as essential WordPress and WooCommerce cookies. Unknown hosts appear in the results, and you can add a service by name and host.

= Is IAB TCF included? =

No. IAB TCF is not part of this version.

= Can the banner show the name ConsentGate? =

Only if you turn it on. During setup you can show the note or continue without it. Later you find the switch under Banner. It stays off until you choose it.

= How do I run setup again? =

Open Settings and choose “Restart setup”. The scan results and your banner texts stay as they are.

= Where are the choices stored? =

In a log on your server. You can export it. IP addresses are not stored. The log keeps a random visitor id from the browser, the choice, the banner version, and a country only when your host already provides one.

= Is this legal advice? =

No. ConsentGate runs the banner and the blocking. Whether the text, the privacy policy, and the services on the site are enough is for you or your lawyer to decide.

== External services ==

= Privacy scan =

On the Services screen, “Scan this site from the outside” opens the Wiedenroth privacy checker in a new tab. The public address of the site is passed along, so the checker knows which site to look at. Nothing is sent until an administrator clicks the link. The checker only reads pages that are already public. It is not legal advice.

* Service: https://wiedenroth.tech/tools/dsgvo-checker
* Privacy policy: https://wiedenroth.tech/software/consentgate/datenschutz

= Google tags =

ConsentGate can recognize Google tags. Finding them does not contact Google.

If the scan has stored Google measurement IDs, the plugin loads the Google tag script after the visitor allows statistics or marketing. Google Consent Mode v2 is set in the page. Advanced Consent Mode is off unless you turn it on. With it on, Google tags that are already on the page are not blocked, and Google can receive a cookieless signal before a choice.

* Service: https://tagmanager.google.com/
* Privacy policy: https://policies.google.com/privacy
* Terms: https://policies.google.com/terms

== Changelog ==

= 1.0.2 =
* Secured the site scan so blocking cannot be bypassed with a forged scanner identity, and scan responses are not cached.
* Known catalog services are blocked even when they were not present during the last scan.
* Hardened CSV log export against spreadsheet formula injection.
* Limited plugin asset detection to this plugin's own URLs.
* Used a safer HTTP request helper when reading the privacy policy.
* Aligned consent log date boundaries with the WordPress timezone.
* Aligned the plugin header name with the readme title for WordPress.org.

= 1.0.1 =
* The ConsentGate note in the banner links to https://wiedenroth.tech/software/consentgate.

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.2 =
Security and privacy hardening for the site scan and catalog blocking. Please update promptly.
