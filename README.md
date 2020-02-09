# RedirectPost
----------------------------
## Requirements

cakePHP 3

## Installation
```
$ composer require adrianodemoura/redirect-post
```

## Usage

### In `src/Application.php`:

```
parent::bootstrap();
$this->addPlugin('RedirectPost');
```

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
'RedirectPost\\Test\\' => array($vendorDir . '/adrianodemoura/redirect-post/tests'),
'RedirectPost\\' => array($vendorDir . '/adrianodemoura/redirect-post/src'),
```





