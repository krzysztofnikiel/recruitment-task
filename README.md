KrzysztofNikielRecruitmentTaskBundle
==========================

How to install:

Step 1 Composer
 - add to composer.json
```
{
      "repositories": [
          {"type": "composer", "url": "https://repo.packagist.com/trakers/"},
          {"packagist.org": false}
      ]
  }
```
 - run 
```
composer require krzysztofnikiel/recruitment-task
```
Step 2 Application
 - add to routes/annotations.yaml
```
api:
    resource: '@KrzysztofNikielRecruitmentTaskBundle/Controller/'
    type: annotation
```
Step 3 Database
 - run
```
php bin/console doctrine:database:create
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```