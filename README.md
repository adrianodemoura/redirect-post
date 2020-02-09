# RedirectPost

## Considerations
Plugin to redirect and save post.

Use the force, read the code.

## Requirements

cakePHP 3

## Installation
```
$ composer require adrianodemoura/redirect-post

```
### In `src/Application.php`:

```
parent::bootstrap();
$this->addPlugin('RedirectPost');
```

## Usage

### In Controller:

```
public function initialize()
{
    parent::initialize();
    $this->loadComponent('RedirectPost.Redirect');
}
```

### to save:
```
$data = $this->request->getData();
$this->Redirect->save( ['action'=>'acton_target'], $data);
```

### to read:
``` 
$data = $this->Redirect->read();
```

### to delete:
```
$this->Redirect->delete();
```

## Check

### In `vendor/cakephp-plugins.php`:
```
'RedirectPost' => $baseDir . '/vendor/adrianodemoura/redirect-post/',
```

### In `vendor/composer/autoload_psr4.php`:
```
'RedirectPost\\' => array($vendorDir . '/adrianodemoura/redirect-post/src'),
'RedirectPost\\Test\\' => array($vendorDir . '/adrianodemoura/redirect-post/tests'),
```

## Test

access http://localhost/youcake3/redirect-post/painel
