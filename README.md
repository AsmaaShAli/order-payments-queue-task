### Queue task setup guide

- pull the repo to your local computer
- open terminal and ```cd``` to the cloned repo
- run ```cp .env .env.example```
- in the ```.env``` file, leave the ```DB_DATABASE``` empty
- run ```php artisan tinker```
- run ```DB::statement("CREATE DATABASE order_payments");```
- now connect the application to this created db (```order_payments```) in the .env file in ```DB_DATABASE``` parameter
- run ```php artisan migrate```

Now, after migrations are run successfully, everything is done, the remaining part is just to run the job and the unit tests.

- please make sure that ```QUEUE_CONNECTION=database``` or ```redis``` in ```.env``` file
- I ran the job also using tinker command ```ProcessOrderPayment::dispatch(1);``` after creating a demo order and a demo user record.
- you can just try the job through the unit test with this command ```php artisan test --filter=ProcessOrderPaymentTest```
- you can monitor the log file for the records being failed or completed during the test.
- you can check the failed jobs through this command ```php artisan queue:failed```

### packegs used

- [timacdonald/log-fake](https://packagist.org/packages/timacdonald/log-fake)
- [Horizon](https://packagist.org/packages/laravel/horizon)

### Optimization Recap

- The job should be assigned to high priority queue instead of default queue.
- Use exponential backoff time for smarter retries [10, 30, 60].
- We should retrieve the Order model with its relations to avoid N+1 problem.

#### PS
- I had a problem with ```Sail``` on my local machine and due to time restrictions, I couldn't fix it.
- it kept showing (```Docker is not running```) despite it's running and I made sure several times.

Hope this guide helps, Thanks for reading

