## extSkel

a modern alternative way to generate PHP Extension skeleton.  
**Remember**, *it's just a skeleton generator, it will not write an entire PHP
extension for you.*

### Installation:-

```sh
git clone git@github.com:7snovic/extSkel.git
```

then dump the autoload files

```sh
comoposer dump-autoload
```

### Usage:-

```sh
php extSkel --proto="path/to/file" [--extension=extname] [--dest-dir=path]
            [--credits="author name"] [--dump-header] [--fast-zpp] [--php-arg="with|enable"]

  --proto=file              File contains prototypes of functions to create.
  --extension=extname       Module is the name of your extension.
  --dest-dir=path           Path to the extension directory.
  --credits=author          Credits string to be added to headers.
  --php-arg=enable          If your extension references something external, use with
                            Otherwise use enable.
  --help                    This message.
  --no-header               Dont Append header string to your extension.
  --fast-zpp                Use FastZPP API instead of zpp functions.
  --opt-file=path           Use an options file instead of command-line args.
```

---

### Proto Files & Classes:-

Proto file is a php file contains a Classes definitions, all proto classes **Must**
lives under `extSkel\Extension` namespace, so you will need to define it at the
top of your proto file.

Currently there are two supported properties that you will need to declare in
proto class:-

```php
/**
 * Declare the extension namespace.
 *
 * @var $namespace
 */
public $namespace = 'namespace';

/**
 * Specify the proto class type.
 *
 * @var $protoType
 */
public $protoType = 'functions';
```

The `$namespace` property used to define a namespace for your extension.

The `$protoType` property is used to define your proto class type,
which **Must** be declared in each proto class.

The current available proto types are : `[functions, ini, class]`

#### - Functions:-
When you define a proto class with a `$protoType` with a `functions` as a value,
`extSkel` will generate an extension skeleton that contains all of the class
defined methods.

No matters the concrete declaration of those methods, `extSkel` will only parse
the function name and it's parameters, the function parameters can be
*Optional* and *type-hinted*

if the parameter has no type-hint the extension by default will compile this
parameter as [ZVAL](http://www.phpinternalsbook.com/php7/internal_types/zvals.html)

The available parameters types :-

- string
- bool
- float
- int
- if empty the parameter will compiled as zval

#### - Objects/Classes :-
As same as functions, it's a proto class that used to write a proto functions that
will be stubbed into PHP extensions skeleton.

[An example for class proto file](/examples/ext_class.php)

The additional property that the class proto file accepts is the `$className` property,
that accepts a string value, in case if this property is not defined, the class name will be
inhereted from the proto class name, in the previous example will be : `extClass`.


#####  :warning: `extSkel` does not support defining constructors and magic methods


#### - INI Entries:-
When `extSkel` parses a proto class with a `ini` as a value of `$protoType`,
will consider only the value of `$entries` property, regardless of any other
defined methods.

The `$entries` property is an associated array with key as an INI name, value
as a INI value.

---

### Opt-file:-

now you can provide a JSON file as an options file instead of using the command-line options.


you may set your opt-file as follows:
```json
{
    "proto": "/path/to/proto/file",
    "extension": "extname",
    "credits": "author name",
    "php-arg": "enable|with",
    "dump-header": true,
    "fast-zpp": true
}
```

and pass this opt-file to `extSkel` using the `--opt-file` option.

for example:

```bash
php extSkel --opt-file='opts.json'
```

Note: this option will disable the command-line options entirely.

---

### Example:-

The package comes with a simple
[example](https://github.com/7snovic/extSkel/blob/master/examples/hello.php) ,
you can run this example to generate
 a simple skeleton with a three functions .

```sh
php extSkel --proto="examples/hello.php" --extension="helloWorld" --fast-zpp
```

this will create a directory with the extension name, and the following files :-
- extension file : helloWorld.c .
- extension header file : php_helloWorld.h .
- config.m4 file.
- config.w32 file.

---

### TODO:-

- [x] Lint the output files.
- [x] Support objects and classes.
- [x] Support INI directives.
- [ ] Support phpinfo handling.
- [ ] Additional options.
    - [x] PHP Arg [enable - with ]
    - [x] Provide options as a JSON file instead of command-line options
- [x] Support Namespaces.
- [ ] Release the `.phar` version.
