###installer
install global artist
```
curl https://raw.githubusercontent.com/redcatphp/artist/master/bin/installer | php
```

###install-project
for now it's only supporting zip url
install a project in your current working dir
```
php -r "eval(file_get_contents('http://redcatphp.com/install-project?q=https://github.com/redcatphp/redcatphp/archive/master.zip'));"
```

if provided url is no absolute, it will use github
```
php -r "eval(file_get_contents('http://redcatphp.com/install-project?q=redcatphp/redcatphp'));"
```

with a specified version
```
php -r "eval(file_get_contents('http://redcatphp.com/install-project?q=redcatphp/redcatphp+master'));"
php -r "eval(file_get_contents('http://redcatphp.com/install-project?q=redcatphp/redcatphp+v6.2.3'));"

with auth
```
php -r "eval(file_get_contents('http://redcatphp.com/install-project?q=user:password@repository/project+v1.0.0'));"
```