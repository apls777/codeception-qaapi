# QaApi module for Codeception

## What is QaApi

QaApi is an API for a QA department. This API can get or change an internal state of a system during manual or automated testing.

## Example of Test Case

> You created a new feature for your webshop. It sends a reminder to the customer email in 1 day if he gave up his cart.

How does tester have to test this case? Right, he has to create a cart and wait 1 day.

But if we have no that time, tester will go to developer and ask him to change a creation time of the cart in database back on 23 hours 55 minutes. Then the feature will be tested in 5 minutes, and we can release it.

At this time we solved our problem, but:

- tester will interrupt a developer every time when he needs to change something in database

- tester can't write automated tests for such cases (unless he has a knowledge of the project source code and has an access to it)

## QaApi solves the both problems

Developer just needs to write one API method which can change a creation time of a cart. Then tester can use it for manual and automated testing:

- PHP client for API will be automatically generated to use it for automated tests

- if your company use [QAdept](http://qadept.com) service, tester can use it manually from **Operations** tab

## What benefits do you have using this approach?

1. Testing of difficult test cases is much easier.
2. It helps testers with manual and automated testing.
3. It increases productivity by splitting up testers' and developers' work.
4. Testers don't need to know anything about the project source code.
5. It allows company to don't share the project source code with testers.

## How to install

```bash
composer require qadept/codeception-qaapi
```
