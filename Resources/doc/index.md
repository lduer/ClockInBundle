Installation
============

### Step 1: Add the Bundle via composer

Open a command console, enter your project directory and execute:

```console
$ composer require lduer/kimai-clock-in
```

>This command executes Composer from a global installation, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
<?php
// config/bundles.php

return [
    // ...
    LDuer\KimaiClockInBundle\KimaiClockInBundle::class => ['all' => true],
];

```

### Step 3: Modify configuration files in kimai

#### 1. Add routes

```yaml 

# config/routes/annotations.yaml

clock_in_bundle:
    resource: '../vendor/lduer/kimai-clock-in-bundle/Controller/'
    type: annotation
    prefix: /{_locale}
    requirements:
        _locale: '%app_locales%'
    defaults:
        _locale: '%locale%'
    name_prefix: 'clock_in_'
```
>**Caution:** It is strongly suggested that you add the clock-in features at the "/" path of your kimai installation.

Only in this case, the default controllers from kimai timesheet actions (`create`, `start`, `stop`, `edit`) can be catched.

This is required to make sure all actions from the bundle work properly and the Entity `LatestActivities` is noticed about all changes. 

#### 2. Set parameter for default clock-in activity
```yaml
# config/packages/local.yaml

parameters:
    clock-in.activity: 1

```

> **Note:** This activity is used to create the timesheet when the user starts working (clock in).
> The activity must be available in the database, but can be set to "visible: no" to hide all entries which were started without specific project/activity selected.

#### 3. Add additional fields to the API Response

These are used in the project list for detailed project information.

```yaml
# config/serializer/App/Entity.Project.yml

App\Entity\Project:
    # ...
    virtual_properties:
        # add this section: (after "getCustomer" block)
        getCustomerName:
            serialized_name: customer_name
            exp: "object.getCustomer() === null ? null : object.getCustomer().getName()"
            type: string
            groups: [Default]
```
 
 #### 4. Clear the cache
 
 ```bash
 $ bin/console cache:clear
 ```
 
 #### 5. Update your Database
 
 ```bash
 $ bin/console doctrine:migrations:diff
 
 $ bin/console doctrine:migrations:migrate
 ```
 
 #### 6. Ready to clock in
 
 Open your browser, login to your kimai installation and use the new features. 
 