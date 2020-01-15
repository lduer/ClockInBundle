# ClockInBundle

This Kimai 2 plugin provides additional features for a simpler ClockIn usage on mobile. 

## Features

- Clock in and out with default activity
- Select project/activity for next/ongoing task with mobile-optimized select-list
- Catch default timesheet actions from Kimai to notice the new entity `LatestActivity`for any timesheet changes.

## Installation

First clone it to your Kimai installation `plugins` directory:

```
cd /kimai/var/plugins/
git clone https://github.com/lduer/ClockInBundle.git
```

Set parameter for default clock-in activity

```yaml
# config/packages/local.yaml

parameters:
    clock-in.activity: 1

```

> **Note:** This activity is used to create the timesheet when the user starts working (clock in).
> The activity must be available in the database, but can be set to "visible: no" to hide all entries which were started without specific project/activity selected.

> **Info:** Planned to be replaced by [bundle configuration](https://www.kimai.org/documentation/developers.html#adding-system-configuration) in future


Update the database with doctrine migrations:

```
 $ bin/console doctrine:migrations:diff
 $ bin/console doctrine:migrations:migrate
 ```

And then rebuild the cache: 
```
cd /kimai/
bin/console cache:clear
bin/console cache:warmup
```

Ready to clock in: Open your browser, login to your kimai installation and use the new features. 

## Permissions

There are no new permissions shipped with this bundle. The existing one `create_own_timesheet` is used.

## Storage

Currently this Bundle stores the `LatestActivity` repository in the database. This means that a database update is required in the install actions. 
Maybe this will change in future versions.

## Documentation

[See Manual](Resources/doc/index.md)

## Screenshot

![Screenshot](https://raw.githubusercontent.com/lduer/ClockInBundle/master/screenshot.jpg)

## Additional Infos

> **Note** that this Bundle was created as composer package before kimai was able to handle plugins. 
> The functionality is not yet complete. E.g. the configuration will be updated in the future.  