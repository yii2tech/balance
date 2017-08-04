<p align="center">
    <a href="https://github.com/yii2tech" target="_blank">
        <img src="https://avatars2.githubusercontent.com/u/12951949" height="100px">
    </a>
    <h1 align="center">Balance Accounting System extension for Yii2</h1>
    <br>
</p>

This extension provides basic support for balance accounting (bookkeeping) system based on [debit and credit](https://en.wikipedia.org/wiki/Debits_and_credits) principle.

For license information check the [LICENSE](LICENSE.md)-file.

[![Latest Stable Version](https://poser.pugx.org/yii2tech/balance/v/stable.png)](https://packagist.org/packages/yii2tech/balance)
[![Total Downloads](https://poser.pugx.org/yii2tech/balance/downloads.png)](https://packagist.org/packages/yii2tech/balance)
[![Build Status](https://travis-ci.org/yii2tech/balance.svg?branch=master)](https://travis-ci.org/yii2tech/balance)


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist yii2tech/balance
```

or add

```json
"yii2tech/balance": "*"
```

to the require section of your composer.json.


Usage
-----

This extension provides basic support for balance accounting (bookkeeping) system based on [debit and credit](https://en.wikipedia.org/wiki/Debits_and_credits) principle.
Balance system is usually used for the accounting (bookkeeping) and money operations. However, it may also be used for any
resource transferring from one location to another. For example: transferring goods from storehouse to the shop and so on.

There 2 main terms related to the balance system:

 - account - virtual storage of the resources, which have some logical meaning.
 - transaction - represents actual transfer of the resources to or from particular account.

Lets assume we have a system, which provides virtual money balance for the user. Money on the balance can be used for the
goods purchasing, user can top up his balance via some payment gateway. In such example, each user should have 3 virtual
balance accounts: 'virtual-money', 'payment-gateway' and 'purchases'. When user tops up his virtual balance, our system
should remove money from 'payment-gateway' and add them to 'virtual-money'. When user purchases an item, our system should
remove money from 'virtual-money' and add them to 'purchases'.
The trick is: if you sum current amount over all user related accounts ('payment-gateway' + 'virtual-money' + 'purchases'),
it will always be equal to zero. Such check allows you to verify is something went wrong any time.

This extension introduces term 'balance manager' as a Yii application component, which should handle all balance transactions.
Several implementations of such component are provided:

 - [[yii2tech\balance\ManagerDb]] - uses a relational database as a data storage.
 - [[yii2tech\balance\ManagerMongoDb]] - uses MongoDB as a data storage.
 - [[yii2tech\balance\ManagerActiveRecord]] - uses ActiveRecord classes for the data storage.

Please refer to the particular manager class for more details.

You can use balance manager as standalone object or configure it as application component.
Application configuration example:

```php
return [
    'components' => [
        'balanceManager' => [
            'class' => 'yii2tech\balance\ManagerDb',
            'accountTable' => '{{%BalanceAccount}}',
            'transactionTable' => '{{%BalanceTransaction}}',
            'accountLinkAttribute' => 'accountId',
            'amountAttribute' => 'amount',
            'dataAttribute' => 'data',
        ],
    ],
    ...
];
```

In order to increase (debit) balance at particular account, [[\yii2tech\balance\ManagerInterface::increase()]] method is used:

```php
Yii::$app->balanceManager->increase($accountId, 500); // add 500 credits to account
```

In order to decrease (credit) balance at particular account, [[\yii2tech\balance\ManagerInterface::decrease()]] method is used:

```php
Yii::$app->balanceManager->decrease($accountId, 100); // remove 100 credits from account
```

> Tip: actually, method `decrease()` is redundant, you can call `increase()` with negative amount in order to achieve same result.

It is unlikely you will use plain `increase()` and `decrease()` methods in your application. In most cases there is a need
to **transfer** money from one account to another at once. Method [[\yii2tech\balance\ManagerInterface::transfer()]] can be
used for this:

```php
$fromId = 1;
$toId = 2;
Yii::$app->balanceManager->transfer($fromId, $to, 100); // remove 100 credits from account 1 and add 100 credits to account 2
```

Note that method `transfer()` creates 2 separated transactions: one per each affected account. Thus you can easily fetch
all money transfer history for particular account, simply selecting all transactions linked to it. 'Debit' transactions
will have positive amount, while 'credit' ones - negative.

> Note: If you wish each transaction created by `transfer()` remember another account involved in the process, you'll need
  to setup [[\yii2tech\balance\Manager::$extraAccountLinkAttribute]].

You may revert particular transaction using [[\yii2tech\balance\ManagerInterface::revert()]] method:

```php
Yii::$app->balanceManager->revert($transactionId);
```

This method will not remove original transaction, but create new one, which compensates it.


## Querying accounts <span id="querying-accounts"></span>

Using account IDs for the balance manager is not very practical. In our above example, each system user have 3 virtual
accounts, each of which has its own unique ID. However, while performing purchase we operating user ID and account type,
so we need to query actual account ID before using balance manager.
Thus there is an ability to specify account for the balance manager methods using their attributes set. For example:

```php
Yii::$app->balanceManager->transfer(
    [
        'userId' => Yii::$app->user->id,
        'type' => 'virtual-money',
    ],
    [
        'userId' => Yii::$app->user->id,
        'type' => 'purchases',
    ],
    500
);
```

In this example balance manager will find ID of the affected accounts automatically, using provided attributes as a filter.

You may enable [[yii2tech\balance\Manager::$autoCreateAccount]], allowing automatic creation of the missing accounts, if they
are specified as attributes set. This allows accounts creation on the fly, by demand only, eliminating necessity of their
pre-creation.

**Heads up!** Actually 'account' entity is redundant at balance system, and its usage can be avoided. However, its presence
provides more flexibility and saves performance. Storing of account data is not mandatory for this extension, you can
configure your balance manager in the way it is not used.


## Finding account current balance <span id="finding-account-current-balance"></span>

Current money amount at particular account can always be calculated as a sum of amounts over related transactions.
You can use [[\yii2tech\balance\ManagerInterface::calculateBalance()]] method for that:

```php
Yii::$app->balanceManager->transfer($fromAccount, $toAccount, 100); // assume this is first time accounts are affected

echo Yii::$app->balanceManager->calculateBalance($fromAccount); // outputs: -100
echo Yii::$app->balanceManager->calculateBalance($toAccount); // outputs: 100
```

However, calculating current balance each time you need it, is not efficient. Thus you can specify an attribute of account
entity, which will be used to store current account balance. This can be done via [[\yii2tech\balance\Manager::$accountBalanceAttribute]].
Each time balance manager performs a transaction it will update this attribute accordingly:

```php
use yii\db\Query;

Yii::$app->balanceManager->transfer($fromAccountId, $toAccountId, 100); // assume this is first time accounts are affected

$currentBalance = (new Query())
    ->select(['balance'])
    ->from('BalanceAccount')
    ->andWhere(['id' => $fromAccountId])
    ->scalar();

echo $currentBalance; // outputs: -100
```


## Saving extra transaction data <span id="saving-extra-transaction-data"></span>

Usually there is a necessity to save extra information along with the transaction. For example: we may need to save
payment ID received from payment gateway. This can be achieved in following way:

```php
// simple increase :
Yii::$app->balanceManager->increase(
    [
        'userId' => Yii::$app->user->id,
        'type' => 'virtual-money',
    ],
    100,
    // extra data associated with transaction :
    [
        'paymentGateway' => 'PayPal',
        'paymentId' => 'abcxyzerft',
    ]
);

// transfer :
Yii::$app->balanceManager->transfer(
    [
        'userId' => Yii::$app->user->id,
        'type' => 'payment-gateway',
    ],
    [
        'userId' => Yii::$app->user->id,
        'type' => 'virtual-money',
    ],
    100,
    // extra data associated with transaction :
    [
        'paymentGateway' => 'PayPal',
        'paymentId' => 'abcxyzerft',
    ]
);
```

The way extra attributes are stored in the data storage depends on particular balance manager implementation.
For example: [[\yii2tech\balance\ManagerDb]] will try to store extra data inside transaction table columns, if their name
equals the parameter name. You may as well setup special data field via [[\yii2tech\balance\ManagerDb::$dataAttribute]],
which will store all extra parameters, which have no matching column, in serialized state.

> Note: watch for the keys you use in transaction data: make sure they do not conflict with columns, which are
  reserved for other purposes, like primary keys.


## Events <span id="events"></span>

[[\yii2tech\balance\Manager]] provide several events, which can be handled via event handler or behavior:

 - [[yii2tech\balance\Manager::EVENT_BEFORE_CREATE_TRANSACTION]] - raised before creating new transaction.
 - [[yii2tech\balance\Manager::EVENT_AFTER_CREATE_TRANSACTION]] - raised after creating new transaction.

For example:

```php
use yii2tech\balance\Manager;
use yii2tech\balance\ManagerDb;

$manager = new ManagerDb();

$manager->on(Manager::EVENT_BEFORE_CREATE_TRANSACTION, function ($event) {
    $event->transactionData['amount'] += 10; // you may adjust transaction data to be saved, including transaction amount
    $event->transactionData['comment'] = 'adjusted by event handler';
});

$manager->on(Manager::EVENT_AFTER_CREATE_TRANSACTION, function ($event) {
    echo 'new transaction: ' $event->transactionId; // you may get newly created transaction ID
});

$manager->increase(1, 100); // outputs: 'new transaction: 1'
echo Yii::$app->balanceManager->calculateBalance(1); // outputs: 110
```
