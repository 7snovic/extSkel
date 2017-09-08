## extSkel

a modern alternative way to generate PHP Extension skeleton.  
**Remember**, *it's just a skeleton generator, it will not write an entire PHP
extension for you.*

### Installation :-

```sh
git clone git@github.com:7snovic/extSkel.git
```

then dump the autoload files

```sh
comoposer dump-autoload
```

### Usage

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
  --dump-header             Append header string to your extension.
  --fast-zpp                Use FastZPP API instead of zpp functions.
```

---

### Proto File

Proto file is a php file contains a functions signatures, no need to write the
full concrete function as the extension will take care only of the function
name and it's parameters .

the functions **MUST** be `namespaced` with the following
namespace `hassan\extSkel\Extension`

the function parameters can be *Optional* and *type-hinted*

if the parameter has no type-hint the extension by default will compile this parameter as [ZVAL](http://www.phpinternalsbook.com/php7/internal_types/zvals.html)

the available types :-

- string
- bool
- float
- int
- if empty the parameter will compiled as zval

---

### Example

The package comes with a simple
[example](https://github.com/7snovic/extSkel/blob/master/examples/hello.php) ,
you can run this example to generate
 a simple skeleton with a three functions .

```sh
php extSkel --proto="examples/hello.php" --extension="helloWorld" --fast-zpp
```

this will create the required extension files :-
- extension file : helloWorld.c .
- extension header file : php_helloWorld.h .
- config.m4 file.
- config.w32 file.

---

### TODO

- Lint the output files.
- Support objects and classes.
- Support INI directives.
- Support phpinfo handling.
- Additional options.
- Support Namespaces.
- Release the `.phar` version.
