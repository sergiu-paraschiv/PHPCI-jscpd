# PHPCI-jscpd

### JSCPD reporter for [PHPCI](https://www.phptesting.org/)


Add this to `composer.json`:

```
composer require sergiu-paraschiv/phpci-jscpd
```

Then the task to `phpci.yml`:
```
\SergiuParaschiv\PHPCI\Plugin\JSCPD:
    directory: "frontend"
    command: "npm run -s mess:ci"
    data_offset: 2
```

`mess:ci` in `package.json` should be `"jscpd --path app/ --languages javascript,jsx --exclude test/mocks/* --reporter xml"`
