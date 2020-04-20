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
Example
- Get product In Stock
```
curl -X GET -v http://localhost/bs-task/public/index.php/api/products
curl -X GET -v http://localhost/bs-task/public/index.php/api/products/in-stock
```
- Get product Out Stock
```
curl -X GET -v http://localhost/bs-task/public/index.php/api/products/out-stock
```
- Get product with amount is greater than 5
```
curl -X GET -v http://localhost/bs-task/public/index.php/api/products-more-than-five
```
- Delete product
```
curl -X DELETE http://localhost/bs-task/public/index.php/api/product/{id}
```
- Add product
```
curl -X POST -d "{\"amount\":12,\"name\":\"Test1\"}" -H "Content-Type: application/json" http://localhost/bs-task/public/index.php/api/product
```
- Update product
```
curl -X PATCH -d "{\"amount\":99,\"name\":\"TestUpdate12\"}" -H "Content-Type: application/json" http://localhost/bs-task/public/index.php/api/product/1
```