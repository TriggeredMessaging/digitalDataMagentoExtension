W3C digitalData Layer Magento Extension
=======================================

Magento Extension to implement the W3C digitalData spec


How to install
--------------

To manually install the extension:

1. Log in to the Admin Panel
2. Navigate through System -> Magento Connect -> Magento Connect Manager
3. Enter username and password for Magento Connect Manager
4. Under "Direct package file upload" click "Choose file" and select the extension file
5. Click on 'Upload'
6. The extension should now begin installing
7. After it has been installed, click on 'Refresh' to see the changes

Note: you may need to log out and log back in to be able to access the configuration panel of this extension.


How to enable the Digital Data layer
------------------------------------

The Digital Data Layer is enabled by default. If it has been disabled, follow these steps to re-enable it:

1. Log in to the Admin Panel
2. Navigate through System -> Configuration
3. On the left pane under "W3C Digital Data Layer", click on "Configuration"
4. On the right area, make sure that the box with heading "Digital Data Layer Configuration" is expanded
4. For "Enable Digital Data Layer" select "Yes"
5. The Digital Data Layer should now be enabled


Notes for extending the Extension
---------------------------------

Most changes to implement new features will be made to the following files:

Observer.php

* Location: /app/code/community/TriggeredMessaging/DigitalDataLayer/Model/Page/
* Info: Contains all of the code that extracts data from Magento's backend. The method `setDigitalDataLayer()`
  initialises all data objects.


digital_data_layer.phtml

* Location: /app/design/frontend/base/default/template/triggeredmessaging/
* Info: Template file that outputs the digitalData layer as a JavaScript object. Also includes the Triggered Messaging
  Script if enabled from the admin panel.


digital_data_layer_after_content.phtml

* Location: /app/design/frontend/base/default/template/triggeredmessaging/
* Info: Template file that adds product data for category, tag and search pages to the digitalData object.


system.xml

* Location: /app/code/community/TriggeredMessaging/DigitalDataLayer/etc/
* Info: To make changes to the configuration section in the Admin Panel.


Authors
-------
Muhammed Onu Miah
Blake Finney
David Henderson


License
-------
Copyright 2014 Triggered Messaging

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.


