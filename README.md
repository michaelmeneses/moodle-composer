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
"markn86/moodle-mod_customcert": "dev-MOODLE_400_STABLE"  
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
"markn86/moodle-mod_customcert": "dev-MOODLE_400_STABLE"  
```

#### Update
```
composer update
```

### Remove moodle plugin

#### Remove entry in require
_Edit composer.json_
```
"middag/moodle-theme_middag": "*"  
```
#### Update
```
composer update
```


## Moodle upgrade

### Set new version
**Edit composer.json**
```
"moodle/moodle": "4.1.*"  
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

## Public directory (Moodle 5.1+)

Since Moodle 5.1 the document root is `moodle/public/`. Composer resolves the
`{$public}` token in plugin install paths through
`Moodle\Composer\MoodleInstaller::getPublicPath()`, which reads
`extra.haspublicdir` from the **root** package first, and only then from the
installed packages whose type is `moodle-core`.

The core package published by satis is declared inline with `type: library`
(on purpose: it must land in `vendor/moodle/moodle` so that
`MoodleComposer::moveMoodle()` can copy it into `moodle/`). A `library` package
is never inspected for `haspublicdir`, so the root setting is the only reliable
source of truth:

```json
{
  "extra": {
    "haspublicdir": true
  }
}
```

Without it `{$public}` resolves to an empty string and plugins are installed in
`moodle/mod`, `moodle/local`, `moodle/auth` — outside the document root, so the
site comes up with no plugins at all.

Projects that depend on this package must declare `extra.haspublicdir`
themselves: Composer only reads `extra` from the root package. Use `true` for
Moodle 5.1 and above, and omit it for 5.0 and below.

### composer/installers

`composer/installers` is pulled in as a transitive dependency: the `moodle/moodle`
package published by satis declares `"composer/installers": "*"`. Its own Moodle
installer claims the same `moodle-*` package types as `moodle/composer-installer`,
and `Composer\Installer\InstallationManager` resolves the conflict by activation
order alone (`array_unshift`, first match wins) — not by any declared precedence.

Its paths are relative to the project root (`mod/{$name}/`, `auth/{$name}/`), with
no `moodle/` prefix and no `{$public}` token, so if it ever won the race the plugins
would be installed outside the Moodle tree. It is therefore kept deactivated:

```json
"config": {
  "allow-plugins": {
    "moodle/composer-installer": true,
    "composer/installers": false
  }
}
```

The package is still installed, it is only never activated as a Composer plugin.
`Moodle\Composer\MoodleInstaller` covers every `moodle-*` type on its own.
