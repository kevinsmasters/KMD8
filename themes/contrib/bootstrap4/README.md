# Bootstrap 4 theme

## Features:

* Includes Bootstrap 4 CDN (4.0 to 4.3)
* Includes Bootstrap 4 breakpoints
* SASS compilation within user interface (no NPM dependencies).
* Bootstrap controls within user interface
* No subtheme mode (unless template override required)

## SASS compilation:

* Head to theme settings to enter SASS
* OR use [sass command](https://sass-lang.com/install)

## Installation

### Using composer

`composer require drupal/bootstrap4`

### Not using composer

If you can not use composer download [Ludwig](https://www.drupal.org/project/ludwig).

## Subtheme

* If you require subtheme (usually if you want to override templates), 
    see [subtheme docs](_SUBTHEME/README.md).

* You can create subtheme by running `bash bin/subtheme.sh [name] [path]`,
    e.g. `bash bin/subtheme.sh b4subtheme ..`