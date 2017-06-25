![alt text][logomoodle] ![alt text][logocomposer]

# moodle-composer

Manage Moodle LMS and plugins using Composer at a root directory level (example ROOT/moodle)

## How use

### Install (only first time)
```
git clone https://github.com/michaelmeneses/moodle-composer.git myproject  
cd myproject  
composer install
```

### Add new moodle plugin

#### SATIS
If the plugin has listed in https://satis.middag.com.br use:

_Edit composer.json_

Add require
```
"markn86/moodle-mod_customcert": "dev-MOODLE_33_STABLE"  
```

#### Another source  

_Edit composer.json_

Add repository
```
{  
  "type": "vcs",  
  "url": "https://github.com/markn86/moodle-mod_customcert.git"  
}  
```

Add require (**The plugin should contain composer.json file with type set to "moodle-_type_"**)
```
"markn86/moodle-mod_customcert": "dev-MOODLE_33_STABLE"  
```

#### Update
```
composer update
```

### Remove moodle plugin

#### Remove entry in require
_Edit composer.json_
```
"drachels/moodle-mod_hotquestion": "dev-MOODLE_33_STABLE"  
```
#### Update
```
composer update
```


## Moodle upgrade

### Set new version
**Edit composer.json**
```
"moodle/moodle": "dev-MOODLE_33_STABLE"  
```
or  
```
"moodle/moodle": "dev-v3.3.0"  
```

### Update  
**Moodle upgrade** (ROOT/moodle/ folder is deleted completely)  
```
composer update  
```

**Reinstall plugins**  
```
composer update  
```

[logomoodle]: https://tracker.moodle.org/secure/attachment/32118/m-logo-square-new.png
[logocomposer]: https://getcomposer.org/img/logo-composer-transparent.png
