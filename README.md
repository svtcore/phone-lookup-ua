# Phone lookup
PHP class which help to get data about requested phone number from the site [telefonnyjdovidnyk.com.ua](https://telefonnyjdovidnyk.com.ua) in array or json format

Include data:
 - local address (region)
 - dangerous rate
 - amount of comments
 - last rate date
 - total views
 - last view date
 - list of comments


# Installation

Install dependencies
```
composer install
```

Install html parser from [paquettg/php-html-parser](https://github.com/paquettg/php-html-parser)

```
composer require paquettg/php-html-parser
```

# Using
```
require "vendor/autoload.php";
require "classes/PhoneLookUp.php";

$phone_number = "0800300466";

$look_obj = new PhoneLookUp($phone_number);
print_r($look_obj->lookup());
```

# Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

# License
[MIT](https://github.com/svtcore/phone-lookup-ua/blob/main/LICENSE)
