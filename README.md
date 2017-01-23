Moodle Direct v1
================

Please be aware that the **Develop** branch should not be considered production ready, although it contains the latest fixes and features it may contain bugs. It should be avoided in favour of the **Master** branch which is the latest available branch that has been through the QA process. Please make any pull requests you would like to make to the develop branch.

To see what has changed in recent versions of Moodle Direct V1, see the [CHANGELOG](https://github.com/turnitin/moodle-mod_turnitintool/blob/master/CHANGELOG.md).

If you would like to contribute to the plugin please see our [CONTRIBUTIONS](https://github.com/turnitin/moodle-mod_turnitintool/blob/master/CONTRIBUTIONS.md) page.

Installation
------------

Before installing these plugins firstly make sure you are logged in as an Administrator and that you are using Moodle 2.6 or higher. The latest Moodle version the plugin currently supports is Moodle 3.1 and its subsequent minor point releases.

To install, all you need to do is copy all the files into the mod/turnitintooltwo directory on your moodle installation. You should then go to `"Site Administration" > "Notifications"` and follow the on screen instructions.

To configure the plugin go to `"Site Administration" > "Plugins" > "Activity Modules" > "Turnitin Assignment"` and enter your Turnitin account Id, shared key and API URL.

**Note:** *The API connection URL for this package should be https://api.turnitin.com/api.asp, https://api.turnitinuk.com/api.asp. or https://sandbox.turnitin.com/api.asp.*
