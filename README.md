Opauth-Amazon
================
[Opauth][1] strategy for Amazon authentication.

Implemented based on http://login.amazon.com

Opauth is a multi-provider authentication framework for PHP.

Getting started
----------------
1. Install Opauth-Amazon:
   ```bash
   cd path_to_opauth/Strategy
   git clone git://github.com/a-yasui/opauth-amazon.git Amazon
   ```

2. Create a Amazon application at App Console https://login.amazon.com/manageApps
   - Make sure that redirect URI is set to actual OAuth 2.0 callback URL, usually `https://path_to_opauth/auth/amazon/oauth2callback`

3. Configure Opauth-Amazon strategy.

4. Direct user to `http://path_to_opauth/auth/amazon` to authenticate


Strategy configuration
----------------------

Required parameters:

```php
<?php
'Amazon' => array(
  'client_id' => 'YOUR CLIENT ID',
  'client_secret' => 'YOUR CLIENT SECRET'
) ?>
```

Optional parameters:
`scope`, `redirect_uri`  
For `scope`, maybe amazon can look only a 'profile'.


References
----------
- [Login with Amazon Developer Documentation](https://login.amazon.com/documentation)

License
---------
Opauth-Amazon is MIT Licensed  
Copyright © 2012 a.yasui ([@a_yasui][2])

[1]: https://github.com/uzyn/opauth
[2]: http://twitter.com/a_yasui
