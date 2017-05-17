# moodle-composer

Manage Moodle LMS and plugins using Composer at a root directory level (example ROOT/moodle)

## How use

### Install (only first time)
> git clone https://github.com/michaelmeneses/moodle-composer.git myproject  
> cd myproject  
> composer install

### Add new moodle plugin
**Edit composer.json**

Add repository
>{  
>  "type": "vcs",  
>  "url": "https://github.com/markn86/moodle-mod_customcert.git"  
>}  

Add require
> "markn86/moodle-mod_customcert": "dev-MOODLE_33_STABLE"  

### Update
> composer update
