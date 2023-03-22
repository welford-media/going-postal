# Going Postal

This Craft CMS 4 plugin provides a mail adapter to enable the use of a Postal servers HTTP API.

***Please note, email attachments are only supported when the RAW setting is enabled.***

## Requirements

This plugin requires Craft CMS 4.3.5 or later, and PHP 8.0.2 or later and access to a Postal mail relay server.

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “Going Postal”. Then press “Install”.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project

# tell Composer to load the plugin
composer require welford-media/going-postal

# tell Craft to install the plugin
./craft plugin/install going-postal
```

## Usage

Edit the Email configuration of your Craft install and select Postal as the Transport Type. Enter your Postal DNS name or IP address, along with your servers API key. For more inforamtion about API keys please visit the Postal documentation.