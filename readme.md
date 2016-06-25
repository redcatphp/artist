###installer
install global artist
```
curl https://raw.githubusercontent.com/redcatphp/artist/master/bin/installer | php
```

###install-project
for now it's only supporting zip url
install a project in your current working dir
```
curl -A "artist" "http://redcatphp.com/install-project?0=https://github.com/redcatphp/redcatphp/archive/master.zip" | php
```

if provided url is no absolute, it will use github
```
curl -A "artist" "http://redcatphp.com/install-project?redcatphp/redcatphp" | php
```

with a specified version
```
curl -A "artist" "http://redcatphp.com/install-project?redcatphp/redcatphp&master" | php
curl -A "artist" "http://redcatphp.com/install-project?redcatphp/redcatphp&v6.2.3" | php

with auth
```
curl -A "artist" "http://redcatphp.com/install-project?user:password@repository/project&v1.0.0" | php
```