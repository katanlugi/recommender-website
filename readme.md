# Recommender Demo-website
![Alt text](resources/assets/AppScreenshot.png?raw=true "General Architecture")

Demo Website using the [item based recommender](https://github.com/katanlugi/recommender). This is a demo website that allows you to test the recommender system using the MovieLens dataset.

You can create an admin account and import the dataset and manage the different option of the recommender.

> This code is a demo and is NOT production ready.

Using a MySQL database running on an SSD we were able to provide a usable experience running the full MovieLens dataset (20M ratings for 27k movies)


## Installation
Make sure to configure correctly the `.env ` file with the correct database info.
You can add admin user in this file under the `ADMIN_USER` variable using a comma separated list of email addresses.

Download this repo and run the following command:
```bash
composer install
php artisan migrate
yarn # or install using npm
npm run dev
```

### Add movies to the database
In order to import all the 20M ratings we need to have a queue running it in the background. On the Laravel side we make use of [Laravel/Horizon](https://laravel.com/docs/5.5/horizon) in order to have a view of the different queues. Horizon makes use of redis/predis.

> Using an sqlite database should be supported but was not tested and may not work correclty. Generally, with this amount of data, sqlite is not recommended.

> You need to have redis up and running on your machine in order to be able to import data.

In order to add initial data to the database, you first need to register an account using the email address listen in the `.env` file as ADMIN_USER.

Once done, click on your username (upper right corner) and then select [import new data]. Here you can import `.csv` dataset files ([MovieLens dataset](https://grouplens.org/datasets/movielens/100k/))

In order to get the recommendations, make sur to also download the [recommender](https://github.com/katanlugi/recommender).

## Import Ratings
In order to be able to import a virtually unlimited number of ratings (the 20 million MovieLens items Set), we must use Queues. Laravel comes with a [queue dashboard](https://horizon.laravel.com/) which allows to have an overview of the different running jobs. Horizon uses [Redis](https://redis.io/) in the background which is now installed on the server.

In order to process the 20M ratings, we first need to make sure that we have userIds for the ratings. That's why we first create "fake" users with ids between 1 and 1000000 for the 20M ratings. This is done using Laravel job chaining as shown in the snippet below.

```js
ImportNewUsersFromCSVFile::withChain([
    new PrepareImportRatingsJobs($file)
])->dispatch($file);
```

This will make sure that we first have userIds for the ratings that we want to import. We use the id range 1-1000000 in the database for those user imports. (Those ranges are more than big enough for this demo website).

| range             | description     |
| ------------------|-----------------|
| 0 - 999999        | auto-import     |
| 1000000 - 1999999 | logged-in users |
| 2000000 - ∞       | anonymous users |



## Manually resetting the database

```sql
SET FOREIGN_KEY_CHECKS = 0;
truncate ratings;
truncate users;
ALTER TABLE users AUTO_INCREMENT = 1000000;
SET FOREIGN_KEY_CHECKS = 1;
```

## GENERAL ARCHITECTURE

![Alt text](resources/assets/Diagram.png?raw=true "General Architecture")

### Database main structure

![Alt test](resources/assets/db_structure.png?raw=true "Database structure")