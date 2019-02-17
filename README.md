# silverstripe-minigridfield

A field that preconfigures and gently themes a `GridField` to result in a "mini" inline grid field.

Two field types are included:

* `MiniGridField`
* `HasOneMiniGridField` - use this to manage an object in a has_one relation

Auto-magically handles (preconfigures, with a few flags available to devs) orderable rows, add new multi class, versioning.

## Requirements

* [silverstripe-framework](https://github.com/silverstripe/silverstripe-framework) ^4.2
* [symbiote/silverstripe-gridfieldextensions](https://github.com/symbiote/silverstripe-gridfieldextensions) ^3.0
* [fromholdio/silverstripe-gridfield-limiter](https://github.com/fromholdio/silverstripe-gridfield-limiter) ^1.0

## Installation

`composer require fromholdio/silverstripe-minigridfield`

## Detail

Detail and usage examples to come.

## Screenshots

![Empty](docs/en/_images/00-empty.png)

![MiniGrid](docs/en/_images/01-minigrid.png)

![MiniGrid limited](docs/en/_images/02-minigrid-limit.png)

![HasOneMiniGrid](docs/en/_images/03-hasoneminigrid.png)



## Thanks & Acknowledgements

* https://github.com/silvershop/silverstripe-hasonefield
* https://github.com/satrun77/silverstripe-hasoneselector
* https://github.com/gorriecoe/silverstripe-linkfield

There are a few more, can't find them right now, will list them here soon. I've worked hard on getting this working how I want it, but took direction from some of the wonderful peeps and their code above, thanks.
