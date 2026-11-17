# Status Feed


## Introduction

The module will generate a json feed, containing information about the version of the core and modules currently installed on the website.

Upon installation this module will create a monitoring user with permission to access the feed.
A uer roles is created with the name "Monitor" and a user is created with username "Status feed" and assigned to the "Monitor" role.

You should use this module when:

* You want to access your Drupal sites core and modules information remotely.

The module currently allows you to access information about the following:

* core
* modules

To submit bug reports and feature suggestions, or to track changes, please visit the following:
  [https://bitbucket.org/novicell/module-status-feed](https://bitbucket.org/novicell/module-status-feed)

## Installation

1. To install the module run `composer require drupal/status_feed` or copy the "status_feed" folder to your
   *modules/contrib* directory.
2. Run `drush en status_feed` or go to *admin/modules*. Filter for ‘Status Feed’ and enable the module. Read
   more about installing modules at [http://drupal.org/node/70151](http://drupal.org/node/70151)

## Configuration

1. Go to *admin/config/status_feed/collect*. Choose source: All, Core, Modules or Status.
2. Go to *admin/config/status_feed/settings*. Set cachetime and Activate cron.
3. Go to *admin/people*
4. Locate the 'Status Feed' user and click the 'edit' link.
5. Set password for the user.
6. Click the 'Save' button.

## Outbound webhooks for sites behind a firewall

In Drools, enable **Receive status feeds by webhook** on the website's
**Status webhook** tab. Copy its URL and secret token. Each website has its own
credentials.

At `/admin/config/status_feed/settings`, enable **Send feeds on cron**, enter
the HTTPS webhook URL and token, and choose **Save and send now**. Drupal cron
then sends the normal feed at the configured interval (one hour by default).
This is independent of the existing **Activate cron** collection checkbox.
The webhook needs no monitor user login. Drools processes accepted uploads on
its next surveillance import or when **Update website data** is selected.

Failed sends retry on a subsequent cron run, after at most five minutes of
backoff. HTTP requests have a 30-second timeout, retain TLS certificate
verification, and do not follow redirects. Settings and credentials are kept in
Drupal state, outside configuration exports. Database clones retain them, so
disable sending on cloned environments. Leave the token field blank when saving
to retain the current token; paste a replacement after rotating it in Drools.

## Troubleshooting

1. Go to the module issue queue at
   [https://bitbucket.org/novicell/module-status-feed/jira](https://bitbucket.org/novicell/module-status-feed/jira)
2. Check if your particular issue exists.  If not, click on the Create issue link.
3. Fill the form.


## Updating

Read more at [http://drupal.org/node/250790](http://drupal.org/node/250790)
