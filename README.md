# gabriel-peleskei/oxid-skeleton

Create OXID eShop module or component skeletons.  



## Installation
```bash
composer require --dev gabriel-peleskei/oxid-skeleton
```

## Component

### Help
```bash
vendor/bin/oe-console gp:skeleton:component -h
```

### Non-interactive mode
This mode takes the defaults or the options given. Use [Help](#help) to list all options.
```bash
vendor/bin/oe-console gp:skeleton:component -n
```


## Module

Helper to create module skeleton 

```bash
vendor/bin/oe-console gp:skeletion:module -h
```

### Structure
Some folders may depending on the template you choose.

    - migration/
        - data/
            migrations.yml
    - assets/
        - css/
        - img/
        - js/
        - logo.png
    - src/
        - Application/
            - Compoent/
                - Widget/
            - Controller/
                - Admin/
            - Model/
        - Core/
            - Module.php
        - Service/
        - Smarty/
            - Plugin/
    - views/
        - admin_smarty/ 
            - de/
                - <lang_file>.php
            - en/
                - <lang_file>.php
        - admin_twig/
            - de/
                - <lang_file>.php
            - en/
                - <lang_file>.php
        - smarty/
            - blocks/
            - tpl/
        - twig/
            - admin/
    - composer.json
    - metadata.json
    - CHANGELOG.md
    - README.md

### Notice

If you choose non-interactive mode you do not get to confirm your entered data.  
You are responsible for the path u choose.