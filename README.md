# gabriel-peleskei/oxid-skeleton

Create OXID eShop module or component skeletons.  

_Module skeleton v7 coming soon..._


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

### V6 for OXID 6.2+

Helper to create skeleton for smarty template

```bash
vendor/bin/oe-console gp:skeletion:v6 -h
```

#### Structure

    - migration/
        - data/
    - out/
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
        - admin/
            - de/
                - <lang_file>.php
            - en/
                - <lang_file>.php
        - blocks/
        - tpl/
            - admin/
    - composer.json
    - metadata.json
    - README.md

#### Notice

If you choose non-interactive mode you do not get to confirm your entered data.  
You are responsible for the path u choose.